#!/usr/bin/env node
/**
 * Repository hygiene gate. Zero dependencies: `node scripts/check-repo.mjs`.
 *
 * Fails the build when the repository would leak something it should not, or ship a broken
 * link. Rules live in .repo-policy.json so they are reviewable next to the code:
 *
 *   1. required files exist
 *   2. no forbidden files (fonts, archives, key material, database dumps, env files)
 *   3. no credentials, private keys, or API tokens
 *   4. no personal email addresses or phone numbers (allow-listed demo values excepted)
 *   5. no third-party origins referenced from theme code (the "zero external requests" claim)
 *   6. every relative link and image in Markdown resolves
 *   7. every JSON file parses, every JavaScript source parses
 *   8. no CRLF line endings
 */
import { readFileSync, readdirSync, statSync, existsSync } from 'node:fs'
import { join, relative, extname, dirname, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { spawnSync } from 'node:child_process'

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..')
const policy = JSON.parse(readFileSync(join(ROOT, '.repo-policy.json'), 'utf8'))
const TEXT = new Set(['.md', '.php', '.js', '.mjs', '.css', '.json', '.yml', '.yaml', '.html', '.txt', '.sh', '.xml', '.svg', '.py'])

const files = []
const walk = (dir) => {
  for (const name of readdirSync(dir)) {
    if (name === '.git' || name === 'node_modules') continue
    const full = join(dir, name)
    if (statSync(full).isDirectory()) walk(full)
    else files.push(relative(ROOT, full).split('\\').join('/'))
  }
}
walk(ROOT)

const failures = []
const fail = (rule, file, detail) => failures.push(`  [${rule}] ${file}${detail ? ': ' + detail : ''}`)

// 1. required files
for (const f of policy.requiredFiles) if (!existsSync(join(ROOT, f))) fail('required', f, 'missing')

// 2. forbidden files
const forbidden = policy.forbiddenPaths.map((p) => new RegExp(p, 'i'))
for (const f of files) for (const re of forbidden) if (re.test(f)) fail('forbidden-file', f, `matches ${re}`)

const SECRETS = [
  ['private key', /-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----/],
  ['AWS access key', /\bAKIA[0-9A-Z]{16}\b/],
  ['GitHub token', /\bgh[pousr]_[A-Za-z0-9]{30,}\b/],
  ['Slack token', /\bxox[baprs]-[A-Za-z0-9-]{10,}\b/],
  ['Google API key', /\bAIza[0-9A-Za-z_-]{35}\b/],
  ['generic secret assignment', /\b(?:api[_-]?key|secret|passwd|password|token)\b\s*[:=]\s*['"][A-Za-z0-9_\-+/=]{16,}['"]/i],
  ['JWT', /\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\b/],
]
const EMAIL = /[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/g
const PHONE = /(?<![\d.])(?:\+?966\s?5\d[\s-]?\d{3}[\s-]?\d{4}|\+?965[\s-]?[2569]\d{7}|\+?20\s?1[0-25]\d[\s-]?\d{3}[\s-]?\d{4})(?![\d])/g
const allowedEmail = policy.allowedEmails.map((p) => new RegExp(p, 'i'))
const allowedPhone = new Set(policy.allowedPhones)
const urlRe = /https?:\/\/([A-Za-z0-9.-]+)[^\s'"<>)\]`]*/g
const hostOk = (host, list) => list.some((h) => host === h || host.endsWith('.' + h))
const noThird = policy.noThirdPartyDirs || []

for (const f of files) {
  const ext = extname(f).toLowerCase()
  if (!TEXT.has(ext) && !f.endsWith('CODEOWNERS')) continue
  const text = readFileSync(join(ROOT, f), 'utf8')
  const isMin = /\.min\.js$/.test(f)

  // 3. secrets (skip the generated bundle: it is minified third-party code)
  if (!isMin && f !== 'scripts/check-repo.mjs' && f !== 'LICENSE' && !f.startsWith('LICENSES/')) {
    for (const [name, re] of SECRETS) if (re.test(text)) fail('secret', f, name)
  }

  // 4. personal data
  if (!isMin && !f.startsWith('LICENSE') && !f.startsWith('LICENSES/')) {
    for (const m of text.matchAll(EMAIL)) {
      if (/\.(png|jpe?g|webp|gif|svg|css|js|woff2?)$/i.test(m[0])) continue
      if (!allowedEmail.some((re) => re.test(m[0]))) fail('email', f, m[0])
    }
    for (const m of text.matchAll(PHONE)) if (!allowedPhone.has(m[0].replace(/\D/g, ''))) fail('phone', f, m[0])
  }

  // 5. third-party origins from theme code
  if (noThird.some((d) => f.startsWith(d + '/')) && ['.php', '.js', '.mjs', '.css', '.json', '.html'].includes(ext)) {
    for (const m of text.matchAll(urlRe)) {
      if (!hostOk(m[1], policy.noThirdPartyHosts)) fail('third-party-origin', f, m[0].slice(0, 80))
    }
  }

  // 6. Markdown links and images
  if (ext === '.md') {
    const dir = dirname(f)
    const refs = [...text.matchAll(/\]\(([^)\s]+)(?:\s+"[^"]*")?\)/g)].map((m) => m[1])
    refs.push(...[...text.matchAll(/(?:src|href)="([^"]+)"/g)].map((m) => m[1]))
    for (const ref of refs) {
      if (/^(?:[a-z][a-z0-9+.-]*:|#|\/\/)/i.test(ref)) continue
      const target = decodeURIComponent(ref.split('#')[0].split('?')[0])
      if (!target) continue
      const full = target.startsWith('/') ? join(ROOT, target) : resolve(ROOT, dir, target)
      if (!existsSync(full)) fail('broken-link', f, ref)
    }
  }

  // 7. JSON and JavaScript syntax
  if (ext === '.json') {
    try { JSON.parse(text) } catch (e) { fail('json', f, e.message) }
  }
  if ((ext === '.js' || ext === '.mjs') && !isMin && (policy.jsSyntaxDirs || []).some((d) => f.startsWith(d))) {
    const r = spawnSync(process.execPath, ['--check', join(ROOT, f)], { encoding: 'utf8' })
    if (r.status !== 0) fail('js-syntax', f, (r.stderr || '').split('\n')[0])
  }

  // 8. line endings
  if (text.includes('\r\n')) fail('crlf', f, 'use LF line endings')
}

if (failures.length) {
  console.error(`\nREPOSITORY CHECK FAILED (${failures.length})\n` + failures.join('\n') + '\n')
  process.exit(1)
}
console.log(`\nREPOSITORY CHECK PASSED: ${files.length} files, 8 rules.\n`)
