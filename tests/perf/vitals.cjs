// Lab Web Vitals with Playwright + Chrome DevTools Protocol (mobile emulation, slow 4G, 4x CPU slowdown).
// Usage: BASE=http://127.0.0.1:8020 node tests/perf/vitals.cjs
const { chromium, devices } = require('playwright')
const BASE = process.env.BASE || 'http://127.0.0.1:8020'

async function measure(browser, path, mobile) {
  const ctx = await browser.newContext(mobile ? { ...devices['Moto G4'] } : { viewport: { width: 1440, height: 900 } })
  const page = await ctx.newPage()
  const cdp = await ctx.newCDPSession(page)
  if (mobile) {
    await cdp.send('Network.enable')
    await cdp.send('Network.emulateNetworkConditions', { offline: false, latency: 150, downloadThroughput: 1.6 * 1024 * 1024 / 8, uploadThroughput: 750 * 1024 / 8 })
    await cdp.send('Emulation.setCPUThrottlingRate', { rate: 4 })
  }
  await cdp.send('Network.setCacheDisabled', { cacheDisabled: true })
  await page.addInitScript(() => {
    window.__v = { lcp: 0, cls: 0, inp: 0 }
    new PerformanceObserver(l => { for (const e of l.getEntries()) window.__v.lcp = e.startTime }).observe({ type: 'largest-contentful-paint', buffered: true })
    new PerformanceObserver(l => { for (const e of l.getEntries()) if (!e.hadRecentInput) window.__v.cls += e.value }).observe({ type: 'layout-shift', buffered: true })
    new PerformanceObserver(l => { for (const e of l.getEntries()) window.__v.inp = Math.max(window.__v.inp, e.duration) }).observe({ type: 'event', buffered: true, durationThreshold: 16 })
  })
  const t0 = Date.now()
  await page.goto(BASE + path, { waitUntil: 'load' })
  const loadMs = Date.now() - t0
  await page.waitForTimeout(2500)
  // interactions: open + close the menu, then scroll
  const opener = mobile ? page.locator('.menu-btn') : page.locator('[data-lang-switch]').first()
  if (mobile) { await opener.click(); await page.waitForTimeout(600); await page.keyboard.press('Escape') } else { await page.locator('nav.links a').first().hover() }
  await page.mouse.wheel(0, 1600)
  await page.waitForTimeout(1500)
  const v = await page.evaluate(() => window.__v)
  const bytes = await page.evaluate(() => performance.getEntriesByType('resource').reduce((s, r) => s + (r.transferSize || 0), 0) + (performance.getEntriesByType('navigation')[0]?.transferSize || 0))
  await ctx.close()
  return { path, mode: mobile ? 'mobile (Moto G4, slow 4G, 4x CPU)' : 'desktop 1440', lcpMs: Math.round(v.lcp), cls: +v.cls.toFixed(3), interactionMs: Math.round(v.inp), loadMs, transferKB: Math.round(bytes / 1024) }
}

;(async () => {
  const browser = await chromium.launch()
  const rows = []
  for (const mobile of [true, false]) for (const path of ['/ar', '/en', '/en/projects/creative-mark', '/ar/work']) rows.push(await measure(browser, path, mobile))
  await browser.close()
  console.table(rows)
  require('fs').writeFileSync('qa-output/vitals.json', JSON.stringify(rows, null, 2))
})()
