import { test, expect, type Page } from '@playwright/test'

const widths = [360, 390, 768, 1024, 1440, 1920]
const pages = ['', '/work', '/about', '/services', '/resume', '/contact', '/projects/creative-mark']

function watchErrors(page: Page) {
  const errors: string[] = []
  page.on('pageerror', e => errors.push(e.message))
  page.on('console', m => { if (m.type() === 'error') errors.push(m.text()) })
  return errors
}

async function noHorizontalOverflow(page: Page) {
  return page.evaluate(() => {
    const max = document.documentElement.clientWidth
    const offenders: string[] = []
    document.querySelectorAll<HTMLElement>('body *').forEach(el => {
      const r = el.getBoundingClientRect()
      // elements inside intentionally clipped / scrolling containers are fine
      if (r.width === 0 || el.closest('.track, .sb-rows, .name, .foot-giant, .leak, #drawer, .preview, .cursor, .spot, .grain, .sb-row, .me')) return
      if (r.right > max + 1 || r.left < -1) offenders.push(`${el.tagName.toLowerCase()}.${el.className}`.slice(0, 80))
    })
    return { scroll: document.documentElement.scrollWidth <= max + 1, offenders: offenders.slice(0, 5) }
  })
}

for (const locale of ['ar', 'en'] as const) {
  for (const width of widths) {
    test(`${locale} @${width}px: pages load without overflow or errors`, async ({ browser }) => {
      // Layout is measured at rest (reduced motion), so elements mid-animation are not counted as overflow.
      const context = await browser.newContext({ viewport: { width, height: width < 800 ? 820 : 900 }, reducedMotion: 'reduce' })
      const page = await context.newPage()
      const errors = watchErrors(page)
      for (const path of pages) {
        const res = await page.goto(`/${locale}${path}`, { waitUntil: 'load' })
        expect(res?.status(), path).toBe(200)
        await expect(page.locator('html')).toHaveAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr')
        await page.waitForTimeout(400)
        const o = await noHorizontalOverflow(page)
        expect(o.scroll, `${path} scrollWidth`).toBe(true)
        expect(o.offenders, `${path} overflowing elements`).toEqual([])
        if (path === '' && (width === 390 || width === 1440)) {
          await page.screenshot({ path: `qa-output/shots/${locale}-${width}-home.png` })
        }
        if (path === '/projects/creative-mark' && (width === 390 || width === 1440)) {
          await page.screenshot({ path: `qa-output/shots/${locale}-${width}-project.png` })
        }
      }
      expect(errors).toEqual([])
      await context.close()
    })
  }
}

for (const width of [390, 1440]) {
  test(`full motion @${width}px: no runtime errors while scrolling every page`, async ({ browser }) => {
    const context = await browser.newContext({ viewport: { width, height: 900 } })
    const page = await context.newPage()
    const errors = watchErrors(page)
    for (const locale of ['ar', 'en']) {
      for (const path of pages) {
        await page.goto(`/${locale}${path}`)
        for (let y = 0; y < 6; y++) { await page.mouse.wheel(0, 900); await page.waitForTimeout(150) }
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= document.documentElement.clientWidth + 1)).toBe(true)
      }
    }
    expect(errors).toEqual([])
    await context.close()
  })
}

test('reduced motion: everything readable, no 3D, no loader', async ({ browser }) => {
  const context = await browser.newContext({ reducedMotion: 'reduce', viewport: { width: 1440, height: 900 } })
  const page = await context.newPage()
  const errors = watchErrors(page)
  await page.goto('/en')
  await expect(page.locator('#loader')).toHaveCount(0)
  await expect(page.locator('.hero canvas')).toHaveCount(0)
  await expect(page.locator('.fallback-orb')).toHaveCount(1)
  const opacity = await page.locator('.stmt .w').first().evaluate(el => getComputedStyle(el).opacity)
  expect(Number(opacity)).toBe(1)
  expect(errors).toEqual([])
  await context.close()
})

test('without WebGL the hero shows the static orb', async ({ browser }) => {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } })
  await context.addInitScript(() => {
    const orig = HTMLCanvasElement.prototype.getContext
    // @ts-expect-error simulate a browser without WebGL
    HTMLCanvasElement.prototype.getContext = function (type: string, ...rest: unknown[]) { return /webgl/.test(type) ? null : orig.call(this, type, ...rest) }
  })
  const page = await context.newPage()
  const errors = watchErrors(page)
  await page.goto('/ar')
  await expect(page.locator('.fallback-orb')).toHaveCount(1)
  expect(errors).toEqual([])
  await context.close()
})

test('works without JavaScript', async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 820 } })
  const page = await context.newPage()
  await page.goto('/en')
  await expect(page.locator('h1.hero-h')).toBeVisible()
  await expect(page.locator('nav.links a[href$="#work"]')).toBeVisible()
  await page.goto('/en/work')
  await page.locator('a.row').first().click()
  await expect(page).toHaveURL(/\/en\/projects\//)
  await context.close()
})

test('keyboard: skip link, menu drawer with focus trap and Escape', async ({ browser }) => {
  const context = await browser.newContext({ viewport: { width: 390, height: 820 } })
  const page = await context.newPage()
  await page.goto('/en')
  await page.keyboard.press('Tab')
  await expect(page.locator('a.skip')).toBeFocused()
  const menuBtn = page.locator('.menu-btn')
  await menuBtn.focus()
  await page.keyboard.press('Enter')
  await expect(page.locator('#drawer')).toHaveAttribute('aria-hidden', 'false')
  await expect(menuBtn).toHaveAttribute('aria-expanded', 'true')
  for (let i = 0; i < 12; i++) await page.keyboard.press('Tab')
  expect(await page.evaluate(() => !!document.activeElement?.closest('#drawer'))).toBe(true)
  await page.keyboard.press('Escape')
  await expect(page.locator('#drawer')).toHaveAttribute('aria-hidden', 'true')
  await expect(menuBtn).toBeFocused()
  await context.close()
})

test('repeated navigation and language switching never double the runtime', async ({ browser }) => {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } })
  const page = await context.newPage()
  const errors = watchErrors(page)
  await page.goto('/en')
  for (let i = 0; i < 3; i++) {
    await page.goto('/en/projects/masar')
    await page.goBack()
    await page.waitForTimeout(300)
    await page.goForward()
    await page.waitForTimeout(300)
    await page.locator('[data-lang-switch]').first().click()
    await page.waitForLoadState('load')
  }
  const state = await page.evaluate(() => window.__site)
  expect(state?.active).toBe(true)
  expect(state?.boots).toBe(1) // one runtime per document, even after back/forward-cache restores
  expect(errors).toEqual([])
  await context.close()
})

test('contact form sends once and shows the result', async ({ browser }) => {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' })
  const page = await context.newPage()
  await page.goto('/en/contact')
  const form = page.locator('[data-contact-form]')
  await form.locator('input[name=name]').fill('QA Browser Test')
  await form.locator('input[name=email]').fill('qa@example.test')
  await form.locator('textarea[name=message]').fill('Automated check of the contact form (delete me).')
  const posts: string[] = []
  page.on('request', r => { if (r.method() === 'POST' && r.url().includes('contact-message')) posts.push(r.url()) })
  await form.locator('button[type=submit]').dblclick()
  await expect(form.locator('[data-form-status]')).toHaveText(/received/i)
  expect(posts.length).toBe(1)
  await context.close()
})
