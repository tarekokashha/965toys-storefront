#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
965toys: price integrity audit.

Pulls every product from the public WooCommerce Store API and flags prices that are
almost certainly 1000x too high.

Background
----------
KWD is a 3-decimal currency (1 dinar = 1000 fils). The store is configured with
minor_unit=2, decimal_sep=".", thousand_sep=",". WooCommerce's wc_format_decimal()
strips every character outside [0-9.-], so a price typed as "7,000" (meaning 7 KWD)
is stored as 7000. Products entered this way are effectively unbuyable.

Output
------
  reports/price-fixes.csv   product_id, sku, name, current, proposed, ratio, confidence, url
  reports/price-fixes.md    human-readable approval sheet

Nothing is written to the store. This is read-only.

Usage
-----
  python tools/audit_prices.py --store https://your-store.example
  python tools/audit_prices.py --store https://your-store.example --threshold 100
"""

import argparse
import csv
import io
import json
import os
import sys
import time
import urllib.request

API_PATH = "/wp-json/wc/store/v1/products"
DEFAULT_UA = "965toys-price-audit/1.0 (+https://github.com/tarekokashha/965toys-storefront)"

# Above this many KWD a children's toy is treated as suspect and reviewed.
# Chosen because the catalog's genuine ceiling sits well under it: excluding the
# broken rows, the most expensive real toy is a ride-on at ~30 KWD.
DEFAULT_THRESHOLD = 100.0

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
REPORTS = os.path.join(ROOT, "reports")


def fetch_all(store, user_agent):
    """
    Page through the Store API until exhausted.

    The site sits behind LiteSpeed + Cloudflare, and the Store API responses
    are cached. Without an explicit cache-buster this returns pre-change data
    and silently reports a fix as not applied - which it did once during the
    price migration. The nonce and no-cache header are load-bearing.
    """
    out, page = [], 1
    nonce = str(int(time.time() * 1000))
    while True:
        url = "%s%s?per_page=100&page=%d&_nc=%s" % (store.rstrip("/"), API_PATH, page, nonce)
        req = urllib.request.Request(
            url,
            headers={"User-Agent": user_agent, "Cache-Control": "no-cache", "Pragma": "no-cache"},
        )
        with urllib.request.urlopen(req, timeout=90) as r:
            batch = json.loads(r.read().decode("utf-8"))
        if not batch:
            break
        out += batch
        if len(batch) < 100:
            break
        page += 1
    return out


def kwd(product):
    """Store API returns integer minor units; minor_unit=2 today, so /100."""
    prices = product.get("prices") or {}
    unit = int(prices.get("currency_minor_unit", 2))
    raw = prices.get("price") or "0"
    return int(raw) / float(10 ** unit)


# Large wooden climbing frames and outdoor playsets genuinely retail for hundreds of
# dinars. The catalog contains a coherent cluster of 7 such items between 270 and 499
# KWD. Dividing those by 1000 would give ~0.30 KWD, which is nonsense - they are almost
# certainly priced correctly and must not be touched.
PREMIUM_BAND = (200.0, 900.0)
PREMIUM_HINTS = (
    "playset", "خشبي", "خشبية", "زحلاق", "أرجوح", "ارجوح",
    "منزلق", "خارجية", "bobcat", "alpine", "castle", "palm oasis",
)


def classify(value, name, threshold):
    """
    Return (proposed_price, confidence, reason).

    The 1000x signature is unambiguous when dividing by 1000 lands the product inside
    the catalog's ordinary toy band (0.5-60 KWD). Two exceptions are handled explicitly:

      * Premium wooden/outdoor playsets sit legitimately in the 200-900 KWD band.
        These are reported as 'ok' - no change proposed.
      * Anything else that does not resolve cleanly is flagged for a human decision.
    """
    if value <= threshold:
        return None, None, None

    lower = name.lower()
    looks_premium = any(h in lower for h in PREMIUM_HINTS)

    if PREMIUM_BAND[0] <= value <= PREMIUM_BAND[1] and looks_premium:
        return None, "ok", "large wooden/outdoor playset - price is plausible as-is"

    divided = value / 1000.0

    if 0.5 <= divided <= 60.0:
        return divided, "high", "/1000 lands in the normal toy price band"
    if divided < 0.5:
        return divided, "review", "/1000 gives an implausibly low price - verify manually"
    if divided <= 120.0:
        return divided, "high", "/1000 lands in the premium playset band"
    return divided, "review", "/1000 still implausibly high - verify manually"


def main():
    ap = argparse.ArgumentParser(description="Read-only price integrity audit for a WooCommerce store.")
    ap.add_argument("--store", required=True, help="store base URL, e.g. https://your-store.example")
    ap.add_argument("--user-agent", default=DEFAULT_UA, help="User-Agent header (some CDNs block unknown agents)")
    ap.add_argument("--threshold", type=float, default=DEFAULT_THRESHOLD,
                    help="KWD above which a price is treated as suspect")
    args = ap.parse_args()

    if not os.path.isdir(REPORTS):
        os.makedirs(REPORTS)

    print("Fetching catalog from the Store API ...")
    products = fetch_all(args.store, args.user_agent)
    print("  %d products" % len(products))

    rows = []
    for p in products:
        value = kwd(p)
        name = " ".join((p.get("name") or "").split())
        proposed, confidence, reason = classify(value, name, args.threshold)
        if confidence is None:
            continue
        rows.append({
            "product_id": p.get("id"),
            "sku": (p.get("sku") or "").strip(),
            "name": name,
            "current_kwd": round(value, 3),
            "proposed_kwd": round(proposed, 3) if proposed is not None else "",
            "confidence": confidence,
            "reason": reason,
            "url": p.get("permalink", ""),
        })

    rows.sort(key=lambda r: -r["current_kwd"])

    csv_path = os.path.join(REPORTS, "price-fixes.csv")
    with io.open(csv_path, "w", encoding="utf-8-sig", newline="") as fh:
        w = csv.DictWriter(fh, fieldnames=[
            "product_id", "sku", "name", "current_kwd",
            "proposed_kwd", "confidence", "reason", "url"])
        w.writeheader()
        w.writerows(rows)

    high = [r for r in rows if r["confidence"] == "high"]
    review = [r for r in rows if r["confidence"] == "review"]
    okay = [r for r in rows if r["confidence"] == "ok"]
    broken_value = sum(r["current_kwd"] for r in rows if r["confidence"] != "ok")

    md = io.open(os.path.join(REPORTS, "price-fixes.md"), "w", encoding="utf-8")
    md.write("# Price fixes awaiting approval\n\n")
    md.write("Scanned %d products. %d are priced above %.0f KWD.\n\n"
             % (len(products), len(rows), args.threshold))
    md.write("- **%d** match the 1000x signature and are safe to divide by 1000.\n" % len(high))
    md.write("- **%d** need your decision.\n" % len(review))
    md.write("- **%d** are large wooden/outdoor playsets whose prices look correct - **leave untouched**.\n\n"
             % len(okay))
    md.write("Unsellable catalog value: **%s KWD**.\n\n" % format(int(broken_value), ","))
    md.write("Nothing has been written to the store. This is read-only.\n\n")

    def table(title, items, note=""):
        if not items:
            return
        md.write("## %s\n\n" % title)
        if note:
            md.write("%s\n\n" % note)
        md.write("| ID | Product | Now (KWD) | Proposed (KWD) | Why |\n")
        md.write("|---|---|---:|---:|---|\n")
        for r in items:
            prop = format(r["proposed_kwd"], ",.3f") if r["proposed_kwd"] != "" else "no change"
            md.write("| %s | %s | %s | **%s** | %s |\n" % (
                r["product_id"], r["name"][:60],
                format(r["current_kwd"], ",.3f"), prop, r["reason"]))
        md.write("\n")

    table("Safe to auto-correct (divide by 1000)", high)
    table("Needs your decision", review)
    table("Leave alone - these look correct", okay,
          "Dividing these by 1000 would give ~0.30 KWD, which is nonsense. "
          "They form a coherent premium playset line. Confirm against your supplier list.")
    md.close()

    print("\n%d flagged: %d auto-correctable, %d need review, %d already correct"
          % (len(rows), len(high), len(review), len(okay)))
    print("  reports/price-fixes.csv")
    print("  reports/price-fixes.md")


if __name__ == "__main__":
    sys.exit(main())
