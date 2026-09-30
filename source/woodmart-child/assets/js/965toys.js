/* ==========================================================================
   965TOYS — MOTION LAYER v2
   Ported from "965Toys Store v2.dc.html" (componentDidMount / wbApply /
   wbFrame / balloons / burst).

   Vanilla, no dependencies, deferred. Animates only transform and opacity.

   Contents
     1. Wonder Box  — the signature 5-second load scene
     2. Balloons    — intro flight + add-to-cart celebration
     3. Reveals     — IntersectionObserver with a force-reveal fallback
     4. Parallax    — shelf banners, rAF-throttled
     5. Tilt        — ±8deg, fine pointers only
     6. Confetti    — on WooCommerce added_to_cart
   ========================================================================== */

(function () {
  'use strict';

  var reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  var coarse  = matchMedia('(pointer: coarse)').matches;

  var PALETTE = ['#FF6B6B', '#FFC93C', '#17BEBB', '#6C5CE7', '#FF9F1C'];

  /* ======================================================================
     1. WONDER BOX — 5 second scene, time-driven (not scroll)
     ====================================================================== */

  /**
   * Apply the scene at progress p (0..1).
   *
   * Ported directly from the prototype's wbApply so the timing curve is
   * identical:
   *   - lid crossfade runs over p 0.12 -> 0.28
   *   - each toy starts at 0.18 + its data-s stagger, over data-d * 0.9
   *   - easeOutCubic, travelling FROM the box mouth to its final slot
   *   - scale .3 -> 1, rotate -22deg -> its data-r resting tilt
   */
  function wbApply(p) {

    var scene = document.getElementById('t965-wb');
    if (!scene) return false;

    var closed = document.getElementById('t965-wb-closed');
    var open   = document.getElementById('t965-wb-open');
    var glow   = document.getElementById('t965-wb-glow');
    var mouth  = document.getElementById('t965-wb-mouth');
    if (!closed || !open || !glow || !mouth) return false;

    var lid = Math.min(1, Math.max(0, (p - 0.12) / 0.16));

    closed.style.opacity   = (1 - lid).toFixed(2);
    closed.style.transform = 'rotate(' + (lid * -3).toFixed(1) + 'deg)';
    open.style.opacity     = lid.toFixed(2);
    glow.style.opacity     = (lid * 0.9 * (1 - p * 0.35)).toFixed(2);
    glow.style.transform   = 'scale(' + (0.35 + lid * 0.75).toFixed(2) + ')';

    var mx = mouth.offsetLeft, my = mouth.offsetTop;

    scene.querySelectorAll('[data-t965-toy]').forEach(function (el) {
      var s  = parseFloat(el.dataset.s) || 0;
      var d  = parseFloat(el.dataset.d) || 0.36;
      var r  = parseFloat(el.dataset.r) || 0;

      var tp = Math.min(1, Math.max(0, (p - (0.18 + s)) / (d * 0.9)));
      var e  = 1 - Math.pow(1 - tp, 3);            // easeOutCubic

      var dx = (mx - (el.offsetLeft + el.offsetWidth  / 2)) * (1 - e);
      var dy = (my - (el.offsetTop  + el.offsetHeight / 2)) * (1 - e);

      el.style.opacity = Math.min(1, tp * 3).toFixed(2);
      el.style.transform =
        'translate(' + dx.toFixed(1) + 'px,' + dy.toFixed(1) + 'px) ' +
        'rotate(' + ((1 - e) * -22 + r * e).toFixed(1) + 'deg) ' +
        'scale(' + (0.3 + 0.7 * e).toFixed(3) + ')';
    });

    return true;
  }

  var wbStarted = false, wbRaf = 0, wbT0 = 0;

  function wbStart() {

    if (wbStarted) return;
    wbStarted = true;

    // Reduced motion: render the finished open scene statically, no animation.
    if (reduced) {
      if (!wbApply(1)) wbStarted = false;
      return;
    }

    var waits = 0;

    function tick() {
      // The scene may not be in the DOM yet on a slow first paint.
      if (!document.getElementById('t965-wb')) {
        if (++waits < 600) wbRaf = requestAnimationFrame(tick);
        return;
      }
      if (!wbT0) wbT0 = performance.now();
      var t = (performance.now() - wbT0) / 1000;
      wbApply(Math.min(1, t / 5));
      if (t < 5.1) wbRaf = requestAnimationFrame(tick);
      else {
        // Hand the toys over to their CSS float loops without a jump: the
        // inline transform is what the float animation would otherwise fight.
        document.querySelectorAll('#t965-wb [data-t965-toy]').forEach(function (el) {
          el.classList.add('is-settled');
        });
      }
    }

    tick();
  }

  /* ======================================================================
     2. BALLOONS
     ====================================================================== */

  function launchBalloons(o) {

    var host = o.host, owned = false;

    if (!host) {
      host = document.createElement('div');
      host.className = 't965-balloons';
      host.setAttribute('aria-hidden', 'true');
      document.body.appendChild(host);
      owned = true;
    }

    for (var i = 0; i < o.count; i++) {
      var b     = document.createElement('div');
      var left  = 4 + Math.random() * 92;
      var size  = 46 + Math.random() * 34;
      var delay = Math.random() * 0.6;
      var dur   = o.minDur + Math.random() * (o.maxDur - o.minDur);
      var c     = PALETTE[i % PALETTE.length];

      b.style.cssText =
        'position:absolute;left:' + left + '%;bottom:-140px;width:' + size + 'px;' +
        'height:' + (size * 1.2) + 'px;' +
        'border-radius:50% 50% 50% 50%/58% 58% 42% 42%;' +
        'background:radial-gradient(circle at 30% 25%,' + c + 'dd,' + c + ');' +
        'box-shadow:0 10px 24px rgba(0,0,0,.12);' +
        'animation:t965-rise ' + dur + 's ease-in ' + delay + 's forwards;';

      var str = document.createElement('div');
      str.style.cssText =
        'position:absolute;left:50%;top:100%;width:1px;height:26px;background:rgba(45,42,69,.35)';
      b.appendChild(str);
      host.appendChild(b);
    }

    setTimeout(function () {
      host.style.transition = 'opacity .5s ease';
      host.style.opacity = '0';
      // Never leave animated nodes compositing forever.
      setTimeout(function () {
        if (owned) host.remove();
        else { host.remove(); }
      }, 500);
    }, o.life);
  }

  /** Homepage intro — plays on every homepage load, by the owner's decision. */
  function introBalloons() {
    var host = document.getElementById('t965-balloons');
    if (!host || reduced) { if (host) host.remove(); return; }
    launchBalloons({ host: host, count: 14, minDur: 3, maxDur: 4.4, life: 3000 });
  }

  /** Add-to-cart celebration — shorter, and guarded against stacking. */
  var cartBusy = false;
  function cartBalloons() {
    if (reduced || cartBusy) return;
    cartBusy = true;
    launchBalloons({ host: null, count: 8, minDur: 2, maxDur: 2.8, life: 2200 });
    setTimeout(function () { cartBusy = false; }, 2700);
  }

  /* ======================================================================
     3. SCROLL REVEALS
     ====================================================================== */

  function reveals() {

    var els = [].slice.call(document.querySelectorAll('[data-t965-reveal]'));
    if (!els.length) return;

    var show = function (el, i) {
      // 75ms stagger within a viewport batch, as the brief specifies.
      el.style.transitionDelay = Math.min(i, 6) * 75 + 'ms';
      el.style.opacity = '1';
      el.style.transform = 'none';
    };

    if (reduced || !('IntersectionObserver' in window)) {
      els.forEach(function (el) { show(el, 0); });
      return;
    }

    els.forEach(function (el) {
      el.style.opacity = '0';
      el.style.transform = 'translateY(30px)';
      el.style.transition = 'opacity .7s ease, transform .7s cubic-bezier(.2,.7,.3,1)';
    });

    var io = new IntersectionObserver(function (entries) {
      var batch = entries.filter(function (e) { return e.isIntersecting; });
      batch.forEach(function (e, i) { show(e.target, i); io.unobserve(e.target); });
    }, { threshold: 0, rootMargin: '0px 0px -8% 0px' });

    els.forEach(function (el) { io.observe(el); });

    // Force-reveal fallback: content must never be stuck invisible.
    setTimeout(function () {
      els.forEach(function (el) {
        if (el.style.opacity === '0' && el.getBoundingClientRect().top < innerHeight) show(el, 0);
      });
    }, 1200);
  }

  /* ======================================================================
     4. BANNER PARALLAX
     ====================================================================== */

  function parallax() {

    if (reduced) return;
    var frames = [].slice.call(document.querySelectorAll('[data-t965-parallax] img'));
    if (!frames.length) return;

    var raf = 0;

    function update() {
      raf = 0;
      var vh = innerHeight;
      frames.forEach(function (img) {
        var r = img.parentElement.getBoundingClientRect();
        if (r.bottom < -200 || r.top > vh + 200) return;   // offscreen: skip
        // -1 .. 1 across the viewport, scaled small. The image is already
        // scale(1.12) so there is room to move without exposing an edge.
        var mid = (r.top + r.height / 2 - vh / 2) / vh;
        img.style.transform = 'scale(1.12) translateY(' + (mid * -18).toFixed(1) + 'px)';
      });
    }

    addEventListener('scroll', function () {
      if (!raf) raf = requestAnimationFrame(update);
    }, { passive: true });

    addEventListener('resize', function () {
      if (!raf) raf = requestAnimationFrame(update);
    }, { passive: true });

    update();
  }

  /* ======================================================================
     5. 3D TILT
     ====================================================================== */

  function tilt() {

    if (reduced || coarse) return;

    document.addEventListener('pointermove', function (ev) {
      var card = ev.target.closest && ev.target.closest('[data-t965-tilt]');
      if (!card) return;
      if (card._raf) return;
      card._raf = requestAnimationFrame(function () {
        card._raf = 0;
        var r = card.getBoundingClientRect();
        var x = (ev.clientX - r.left) / r.width  - 0.5;
        var y = (ev.clientY - r.top)  / r.height - 0.5;
        card.style.transform =
          'perspective(700px) rotateY(' + (x * 8).toFixed(2) + 'deg) rotateX(' +
          (-y * 8).toFixed(2) + 'deg) translateY(-6px)';
        card.style.boxShadow = '0 22px 44px rgba(45,42,69,.16)';
      });
    }, { passive: true });

    document.addEventListener('pointerleave', function (ev) {
      var card = ev.target.closest && ev.target.closest('[data-t965-tilt]');
      if (!card) return;
      card.style.transform = '';
      card.style.boxShadow = '';
    }, { passive: true, capture: true });
  }

  /* ======================================================================
     6. CONFETTI
     ====================================================================== */

  function burst(x, y) {
    if (reduced) return;
    for (var i = 0; i < 18; i++) {
      var d = document.createElement('div');
      var a = Math.random() * Math.PI * 2;
      var v = 60 + Math.random() * 90;
      d.style.cssText =
        'position:fixed;left:' + x + 'px;top:' + y + 'px;width:9px;height:9px;' +
        'border-radius:' + (Math.random() > 0.5 ? '50%' : '2px') + ';' +
        'background:' + PALETTE[i % PALETTE.length] + ';pointer-events:none;z-index:9999;' +
        'transition:transform .8s cubic-bezier(.2,.7,.3,1),opacity .8s ease;';
      document.body.appendChild(d);
      (function (node, ang, vel) {
        requestAnimationFrame(function () {
          node.style.transform =
            'translate(' + (Math.cos(ang) * vel) + 'px,' + (Math.sin(ang) * vel + 70) + 'px) ' +
            'rotate(' + (Math.random() * 360) + 'deg)';
          node.style.opacity = '0';
        });
        setTimeout(function () { node.remove(); }, 900);
      })(d, a, v);
    }
  }

  var lastPoint = { x: innerWidth / 2, y: innerHeight / 3 };

  function cartHooks() {

    document.addEventListener('click', function (ev) {
      var btn = ev.target.closest &&
        ev.target.closest('.add_to_cart_button, .single_add_to_cart_button, [data-t965-add]');
      if (!btn) return;
      lastPoint = { x: ev.clientX, y: ev.clientY };
      // Non-AJAX buttons post and reload, so they never fire added_to_cart —
      // celebrate on click for those or they get no feedback at all.
      if (!btn.classList.contains('ajax_add_to_cart')) { burst(ev.clientX, ev.clientY); cartBalloons(); }
    }, { passive: true });

    if (!window.jQuery) return;

    // The single-product button is a real form submit, so the page reloads and
    // the shopper stares at an unchanged button for the whole round trip. Mark
    // it busy on submit so the press is acknowledged immediately.
    var form = document.querySelector('.single-product-page form.cart');
    if (form) {
      form.addEventListener('submit', function () {
        var b = form.querySelector('.single_add_to_cart_button');
        if (!b || b.dataset.t965Busy) return;
        b.dataset.t965Busy = '1';
        b.dataset.t965Label = b.textContent;
        b.classList.add('t965-busy');
        b.setAttribute('aria-busy', 'true');
        b.textContent = 'جارٍ الإضافة…';
      });
    }

    // Fires only once the item is genuinely in the cart.
    jQuery(document.body).on('added_to_cart', function (e, fragments, hash, btn) {
      burst(lastPoint.x, lastPoint.y);
      cartBalloons();

      // Confirm on the button itself. Woodmart's own "view cart" link appears
      // elsewhere in the page and is easy to miss on a phone.
      var b = btn && btn.length ? btn[0] : null;
      if (b && !b.dataset.t965Done) {
        b.dataset.t965Done = '1';
        var label = b.textContent;
        b.classList.add('t965-added');
        b.textContent = 'تمت الإضافة ✓';
        setTimeout(function () {
          b.classList.remove('t965-added');
          b.textContent = label;
          delete b.dataset.t965Done;
        }, 1800);
      }

      // Header count comes from the fragment response; Woodmart replaces its
      // own markup, so nothing to do beyond letting it.
      if (fragments) { /* handled by WooCommerce */ }
    });
  }

  /* ======================================================================
     BOOT
     ====================================================================== */

  function init() {
    wbStart();        // must be first: it is the LCP-adjacent hero
    reveals();
    cartHooks();
    if (!reduced) { introBalloons(); parallax(); tilt(); }
  }

  if (document.readyState !== 'loading') init();
  else document.addEventListener('DOMContentLoaded', init, { once: true });

  // Expose for debugging.
  window.__t965 = { wbApply: wbApply, reduced: reduced, coarse: coarse };
})();
