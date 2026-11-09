/* One initialiser per section type (data-module="…"). Each runs inside the page's gsap.context,
   so every tween and ScrollTrigger it creates is reverted when the page is torn down. */
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import type { Ctx } from './runtime'
import type { HeroScene } from './hero3d'

type Init = (el: HTMLElement, ctx: Ctx) => void

const $ = <T extends Element = HTMLElement>(root: ParentNode, sel: string) => root.querySelector<T>(sel as string) as T | null
const $$ = <T extends Element = HTMLElement>(root: ParentNode, sel: string) => Array.from(root.querySelectorAll<T>(sel as string))
const masked = (el: Element, ctx: Ctx) => gsap.from(el.querySelectorAll('.w'), { yPercent: 110, rotate: 4 * -ctx.dir, duration: 1.1, stagger: 0.035, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 85%' } })

function webglAvailable(): boolean {
  try {
    const c = document.createElement('canvas')
    return !!(c.getContext('webgl2') || c.getContext('webgl'))
  } catch { return false }
}

const hero: Init = (el, ctx) => {
  const canvas = $<HTMLCanvasElement>(el, '[data-gl]')
  const me = $(el, '[data-me]')
  const sceneOn = el.dataset.sceneEnabled === '1' && document.documentElement.dataset.scene === '1'
  const saveData = (navigator as Navigator & { connection?: { saveData?: boolean } }).connection?.saveData === true
  let scene: HeroScene | null = null

  const fallback = () => { if (me && !$(me, '.fallback-orb')) me.insertAdjacentHTML('afterbegin', '<div class="fallback-orb" aria-hidden="true"></div>') }

  if (canvas && sceneOn && !ctx.reduced && !saveData && webglAvailable()) {
    // Loaded after the page is interactive so the 3D code never delays the first paint.
    const start = () => import('./hero3d').then(({ createHeroScene }) => {
      if (!canvas.isConnected) return
      const css = getComputedStyle(document.documentElement)
      scene = createHeroScene(canvas, { brick: css.getPropertyValue('--brick').trim() || '#C15B46', signal: css.getPropertyValue('--turq').trim() || '#49DDE4', light: !ctx.fine || innerWidth < 900 })
      if (!scene) { canvas.remove(); fallback() }
    }).catch(() => { canvas.remove(); fallback() })
    'requestIdleCallback' in window ? requestIdleCallback(() => start(), { timeout: 1500 }) : setTimeout(start, 300)
    ctx.cleanup(() => scene?.destroy())
  } else {
    canvas?.remove()
    fallback()
  }

  if (ctx.reduced) return
  const track = $(el, '[data-name-track]')
  if (track) gsap.to(track, { xPercent: 50 * ctx.dir, ease: 'none', duration: 28, repeat: -1 })
  gsap.to($(el, '.name'), { yPercent: 40, ease: 'none', scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: true } })
  gsap.to($$(el, '.hero-copy, .scrollcue'), { y: -140, opacity: 0, ease: 'none', scrollTrigger: { trigger: el, start: 'top top', end: '60% top', scrub: true } })
  if (me) gsap.to(me, { yPercent: 12, scale: 1.06, ease: 'none', scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: true } })
  if (!ctx.calm) gsap.to($$(el, '.leak'), { yPercent: -30, ease: 'none', scrollTrigger: { trigger: el, start: 'top top', end: 'bottom top', scrub: true } })
  ScrollTrigger.create({ trigger: el, start: 'top top', end: 'bottom top', onUpdate: s => scene?.setScroll(s.progress) })

  // intro, played once the loader (if any) has gone
  const intro = () => {
    gsap.from($(el, '.name .track'), { yPercent: 60, opacity: 0, duration: 1.6, ease: 'expo.out' })
    gsap.from($$(el, '.me img'), { yPercent: 18, opacity: 0, filter: 'brightness(0)', duration: 1.8, ease: 'expo.out', delay: 0.1, clearProps: 'filter' })
    gsap.from($(el, '.me-ring'), { scale: 0.4, opacity: 0, duration: 1.6, ease: 'expo.out', delay: 0.2 })
    gsap.from($$(el, '.hero-copy > *'), { y: 50, opacity: 0, duration: 1.3, stagger: 0.09, ease: 'expo.out', delay: 0.25 })
    gsap.from($$(el, '.hl'), { scaleX: 0, transformOrigin: ctx.rtl ? 'right' : 'left', duration: 1, ease: 'expo.inOut', delay: 0.9 })
  }
  document.documentElement.classList.contains('is-loading') ? ctx.on(document, 'site:loaded', intro, { once: true }) : intro()

  // the portrait leans toward the pointer
  if (ctx.fine && !ctx.calm) {
    const img = $(el, '.me img')
    if (img) {
      const mx = gsap.quickTo(img, 'x', { duration: 1.2, ease: 'power3' }), mr = gsap.quickTo(img, 'rotateY', { duration: 1.2, ease: 'power3' })
      ctx.on(window, 'pointermove', (e: PointerEvent) => { const k = e.clientX / innerWidth - 0.5; mx(k * 18); mr(k * 6) }, { passive: true })
    }
  }
}

const statement: Init = (el, ctx) => {
  const words = $$(el, '.stmt .w')
  if (ctx.reduced) { gsap.set(words, { opacity: 1 }); return }
  gsap.to(words, { opacity: 1, stagger: 0.08, ease: 'none', scrollTrigger: { trigger: $(el, '.stmt'), start: 'top 80%', end: 'bottom 45%', scrub: true } })
}

const about: Init = (el, ctx) => {
  if (ctx.reduced) return
  $$(el, '.split').forEach(s => masked(s, ctx))
  gsap.from(el, { y: 120, ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'top 40%', scrub: true } })
}

const cube: Init = (el, ctx) => {
  let faces: Array<{ n: string; r: string }> = []
  try { faces = JSON.parse(el.dataset.faces || '[]') } catch { /* static cube */ }
  const box = $(el, '[data-cube]'), name = $(el, '[data-cube-name]'), role = $(el, '[data-cube-role]'), idx = $(el, '[data-cube-idx]')
  if (!box || ctx.reduced || faces.length === 0) return
  const turns = [[0, 0], [0, -90], [0, -180], [0, 90], [-90, 0], [90, 0]]
  const pad = (n: number) => String(n).padStart(2, '0')
  const tl = gsap.timeline({ repeat: -1, scrollTrigger: { trigger: el, start: 'top bottom', end: 'bottom top', toggleActions: 'play pause resume pause' } })
  faces.slice(0, 6).forEach((face, i) => {
    const [rx, ry] = turns[i]
    tl.to(box, { rotateX: rx - 14, rotateY: ry + 24, duration: 1.3, ease: 'expo.inOut' })
      .call(() => {
        if (!name || !role || !idx) return
        name.textContent = face.n; role.textContent = face.r; idx.textContent = `${pad(i + 1)} / ${pad(faces.length)}`
        gsap.fromTo([name, role], { yPercent: 60, opacity: 0 }, { yPercent: 0, opacity: 1, duration: 0.6, stagger: 0.06, ease: 'expo.out' })
      }, [], '-=.5')
      .to(box, { rotateY: '+=10', duration: 1.6, ease: 'sine.inOut' })
  })
  if (!ctx.calm) {
    gsap.to($(el, '.stage'), { y: -14, duration: 2.6, ease: 'sine.inOut', yoyo: true, repeat: -1 })
    gsap.to($(el, '.r1'), { rotate: 360, duration: 26, ease: 'none', repeat: -1 })
    gsap.to($(el, '.r2'), { rotate: -360, duration: 40, ease: 'none', repeat: -1 })
  }
  gsap.from(el, { scale: 0.7, opacity: 0, rotate: -8, duration: 1.6, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 85%' } })
}

const reel: Init = (el, ctx) => {
  const track = $(el, '[data-reel-track]'), bar = $(el, '[data-reel-bar]'), num = $(el, '[data-reel-num]')
  if (!track) return
  const cards = $$(el, '.card'), n = cards.length
  const pad = (v: number) => String(v).padStart(2, '0')
  // Phones, tablets and reduced motion: a native horizontal scroller (touch friendly, no pinning).
  const pinned = !ctx.reduced && innerWidth > 860 && ctx.fine
  if (!pinned) {
    el.classList.add('native')
    ctx.on(track, 'scroll', () => {
      const max = track.scrollWidth - track.clientWidth
      const p = max > 0 ? Math.abs(track.scrollLeft) / max : 0
      if (bar) bar.style.transform = `scaleX(${p})`
      if (num) num.textContent = pad(Math.min(n, Math.round(p * (n - 1)) + 1))
    }, { passive: true })
    return
  }
  const span = () => Math.max(0, track.scrollWidth - innerWidth)
  const slide = gsap.to(track, {
    x: () => span() * ctx.dir, ease: 'none',
    scrollTrigger: {
      trigger: el, start: 'top top', end: () => `+=${span()}`, pin: true, scrub: 1, anticipatePin: 1, invalidateOnRefresh: true,
      onUpdate: s => { if (bar) gsap.set(bar, { scaleX: s.progress }); if (num) num.textContent = pad(Math.min(n, Math.round(s.progress * (n - 1)) + 1)) },
    },
  })
  if (!ctx.calm) cards.forEach(c => {
    const img = $(c, 'img')
    if (img) gsap.fromTo(img, { xPercent: -7 * ctx.dir, scale: 1.16 }, { xPercent: 7 * ctx.dir, scale: 1.16, ease: 'none', scrollTrigger: { trigger: c, containerAnimation: slide, start: ctx.dir < 0 ? 'left right' : 'right left', end: ctx.dir < 0 ? 'right left' : 'left right', scrub: true } })
  })
  gsap.from(cards.slice(0, 3), { y: 90, opacity: 0, duration: 1.2, stagger: 0.1, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 70%' } })
  $$(el, '.split').forEach(s => masked(s, ctx))

  // 3D tilt + shine follow the pointer
  cards.forEach(c => {
    const m = $(c, '.media')
    if (!m) return
    ctx.on(c, 'pointermove', (e: PointerEvent) => { const r = m.getBoundingClientRect(), x = (e.clientX - r.left) / r.width, y = (e.clientY - r.top) / r.height; m.style.transform = `rotateY(${(x - 0.5) * 8}deg) rotateX(${(0.5 - y) * 8}deg) scale(1.01)`; m.style.setProperty('--mx', `${x * 100}%`); m.style.setProperty('--my', `${y * 100}%`) })
    ctx.on(c, 'pointerleave', () => { m.style.transform = '' })
  })
}

const index: Init = (el, ctx) => {
  const rows = $$<HTMLAnchorElement>(el, 'a.row')
  if (!ctx.reduced) rows.forEach(r => gsap.from(r, { y: 50, opacity: 0, duration: 1, ease: 'expo.out', scrollTrigger: { trigger: r, start: 'top 92%' } }))

  // category filter without reloading (the links still work without JavaScript)
  const filters = $(el, '[data-filters]')
  if (filters) ctx.on(filters, 'click', (e: MouseEvent) => {
    const a = (e.target as HTMLElement).closest<HTMLAnchorElement>('[data-filter]')
    if (!a) return
    e.preventDefault()
    const slug = a.dataset.filter || ''
    $$(filters, '[data-filter]').forEach(x => { x.classList.toggle('on', x === a); x === a ? x.setAttribute('aria-current', 'true') : x.removeAttribute('aria-current') })
    $$<HTMLLIElement>(el, '.rows li').forEach(li => { li.hidden = slug !== '' && !(li.dataset.cats || '').split(' ').includes(slug) })
    const url = new URL(location.href); slug ? url.searchParams.set('category', slug) : url.searchParams.delete('category'); history.replaceState(null, '', url)
    ScrollTrigger.refresh()
  })

  // the preview image follows the cursor — only while the pointer is over the list (mouse only)
  const prev = $(el, '[data-preview]'), strip = $(el, '[data-strip]')
  if (!ctx.fine || !prev || !strip) return
  const list = $(el, '.rows')!
  document.body.appendChild(prev) // fixed element must not be inside a transformed parent
  ctx.cleanup(() => prev.remove())
  const px = gsap.quickTo(prev, 'x', { duration: 0.55, ease: 'power3' }), py = gsap.quickTo(prev, 'y', { duration: 0.55, ease: 'power3' })
  const hide = () => gsap.to(prev, { scale: 0, duration: 0.25, ease: 'power3.in', overwrite: 'auto' })
  ctx.on(list, 'pointermove', (e: PointerEvent) => { px(e.clientX); py(e.clientY) }, { passive: true })
  ctx.on(list, 'pointerleave', hide)
  ctx.on(window, 'scroll', () => { const r = list.getBoundingClientRect(); if (r.bottom < 0 || r.top > innerHeight) hide() }, { passive: true })
  rows.forEach(r => {
    ctx.on(r, 'pointerenter', () => { strip.style.transform = `translateY(-${Number(r.dataset.i) * 100}%)`; gsap.to(prev, { scale: 1, duration: 0.45, ease: 'power3.out', overwrite: 'auto' }) })
    ctx.on(r, 'click', () => gsap.set(prev, { scale: 0 }))
  })
}

const services: Init = (el, ctx) => {
  if (ctx.reduced) return
  gsap.from($$(el, '.svc'), { y: 60, opacity: 0, stagger: 0.1, duration: 1.1, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 70%' } })
}

const process: Init = (el, ctx) => {
  if (ctx.reduced) { gsap.set($$(el, '.step .bar'), { scaleX: 1 }); return }
  gsap.to($$(el, '.step .bar'), { scaleX: 1, stagger: 0.2, duration: 1.2, ease: 'expo.inOut', scrollTrigger: { trigger: $(el, '.steps'), start: 'top 80%' } })
  gsap.from($$(el, '.step'), { y: 40, opacity: 0, stagger: 0.12, duration: 1, ease: 'expo.out', scrollTrigger: { trigger: $(el, '.steps'), start: 'top 80%' } })
}

const cv: Init = (el, ctx) => {
  const box = $(el, '[data-timeline]'), path = box ? $<SVGPathElement>(box, 'path') : null
  const draw = () => {
    if (!box || !path) return
    const h = box.offsetHeight, x = ctx.rtl ? box.offsetWidth - 16 : 16
    let d = `M${x} 0`
    for (let y = 0; y < h; y += 120) d += ` C ${x + 26} ${y + 40}, ${x - 26} ${y + 80}, ${x} ${Math.min(h, y + 120)}`
    path.setAttribute('d', d); (path.parentNode as SVGElement).setAttribute('viewBox', `0 0 ${box.offsetWidth} ${h}`)
  }
  draw()
  if (ctx.reduced) { gsap.set($$(el, '.doodle, [data-timeline] path'), { strokeDashoffset: 0 }); return }
  ScrollTrigger.addEventListener('refreshInit', draw)
  ctx.cleanup(() => ScrollTrigger.removeEventListener('refreshInit', draw))
  const dir = ctx.dir
  gsap.from($(el, '.cv-card'), { rotateY: 18 * dir, rotateX: 6, y: 80, opacity: 0, duration: 1.4, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 75%' } })
  if (path) gsap.fromTo(path, { strokeDashoffset: 1 }, { strokeDashoffset: 0, ease: 'none', scrollTrigger: { trigger: box, start: 'top 75%', end: 'bottom 55%', scrub: true } })
  $$(el, '.job').forEach(j => {
    gsap.from(j, { x: 40 * -dir, opacity: 0, duration: 1, ease: 'expo.out', scrollTrigger: { trigger: j, start: 'top 80%' } })
    gsap.from($(j, '.node'), { scale: 0, duration: 0.8, ease: 'back.out(3)', scrollTrigger: { trigger: j, start: 'top 72%' } })
  })
  const sticker = $(el, '.sticker')
  if (sticker) gsap.from(sticker, { scale: 0, rotate: -40, duration: 1, ease: 'back.out(2.4)', scrollTrigger: { trigger: sticker, start: 'top 85%' } })
  $$<SVGPathElement>(el, '.doodle').forEach(p => gsap.fromTo(p, { strokeDashoffset: 1 }, { strokeDashoffset: 0, duration: 1.4, ease: 'expo.inOut', scrollTrigger: { trigger: p.closest('svg'), start: 'top 85%' } }))
  const langs = $(el, '.langs')
  if (langs) gsap.from($$(langs, '.lg strong'), { yPercent: 100, opacity: 0, stagger: 0.15, duration: 1, ease: 'expo.out', scrollTrigger: { trigger: langs, start: 'top 85%' } })
  $$(el, '.split').forEach(s => masked(s, ctx))
}

const band: Init = (el, ctx) => {
  if (ctx.reduced) return
  $$(el, '.split').forEach(s => masked(s, ctx))
  $$(el, '.sb-row').forEach((row, i) => {
    const d = Number(row.dataset.dir) * (ctx.rtl ? -1 : 1)
    const tw = gsap.fromTo($(row, '.sb-track'), { xPercent: d < 0 ? 0 : -33.333 }, { xPercent: d < 0 ? -33.333 : 0, duration: 34 + i * 6, ease: 'none', repeat: -1 })
    ScrollTrigger.create({ trigger: row, start: 'top bottom', end: 'bottom top', onToggle: s => (s.isActive ? tw.play() : tw.pause()), onUpdate: s => {
      if (ctx.calm) return
      gsap.to(tw, { timeScale: 1 + Math.min(4, Math.abs(s.getVelocity()) / 400), duration: 0.3, overwrite: true })
      gsap.to(tw, { timeScale: 1, duration: 1.2, delay: 0.3 })
    } })
    ctx.on(row, 'pointerenter', () => gsap.to(tw, { timeScale: 0.15, duration: 0.5 }))
    ctx.on(row, 'pointerleave', () => gsap.to(tw, { timeScale: 1, duration: 0.5 }))
  })
  gsap.from($$(el, '.sb-row'), { x: (i: number) => (i % 2 ? 160 : -160), opacity: 0, duration: 1.4, stagger: 0.12, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 75%' } })
}

const contact: Init = (el, ctx) => {
  if (!ctx.reduced) {
    $$(el, '.split').forEach(s => masked(s, ctx))
    const circle = $(el, '.circle-btn')
    if (circle) gsap.from(circle, { scale: 0.4, rotate: -30 * ctx.dir, ease: 'none', scrollTrigger: { trigger: el, start: 'top bottom', end: 'center center', scrub: true } })
  }

  // copy e-mail
  $$<HTMLButtonElement>(el, '[data-copy]').forEach(btn => ctx.on(btn, 'click', async () => {
    const label = btn.textContent || ''
    try { await navigator.clipboard.writeText(btn.dataset.copy || ''); btn.textContent = btn.dataset.copied || label } catch {
      const code = $(el, '.email-line code'); if (code) { const r = document.createRange(); r.selectNodeContents(code); getSelection()?.removeAllRanges(); getSelection()?.addRange(r) }
    }
    ctx.timer(() => { btn.textContent = label }, 1800)
  }))

  // the form posts with fetch (no reload); without JavaScript it is a normal form post
  const form = $<HTMLFormElement>(el, '[data-contact-form]')
  if (!form) return
  const status = $(form, '[data-form-status]')!
  const button = $<HTMLButtonElement>(form, 'button[type=submit]')!
  const setStatus = (msg: string, ok: boolean | null) => { status.textContent = msg; status.className = 'f-status' + (ok === null ? '' : ok ? ' ok' : ' bad') }
  const clearErrors = () => { $$(form, '.err[data-js]').forEach(x => x.remove()); $$(form, '[aria-invalid]').forEach(x => x.removeAttribute('aria-invalid')) }
  const fieldError = (name: string, msg: string) => {
    const input = form.elements.namedItem(name) as HTMLInputElement | null
    if (!input) return
    input.setAttribute('aria-invalid', 'true')
    const span = document.createElement('span'); span.className = 'err'; span.dataset.js = '1'; span.id = `${input.id}-err`; span.textContent = msg
    input.setAttribute('aria-describedby', span.id); input.insertAdjacentElement('afterend', span)
  }
  let busy = false
  ctx.on(form, 'submit', async (e: SubmitEvent) => {
    e.preventDefault()
    if (busy) return // double clicks never send twice (the server also refuses duplicates)
    clearErrors()
    const name = (form.elements.namedItem('name') as HTMLInputElement).value.trim()
    const email = (form.elements.namedItem('email') as HTMLInputElement).value.trim()
    if (!name) { fieldError('name', ctx.data.required); (form.elements.namedItem('name') as HTMLInputElement).focus(); return }
    if (!/^\S+@\S+\.\S+$/.test(email)) { fieldError('email', email ? ctx.data.invalidEmail : ctx.data.required); (form.elements.namedItem('email') as HTMLInputElement).focus(); return }
    busy = true; button.setAttribute('aria-busy', 'true'); setStatus(ctx.data.sending, null)
    try {
      const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
      const json = await res.json().catch(() => ({}))
      if (res.ok && json.ok) {
        setStatus(json.message || form.dataset.success || '', true)
        form.reset()
        const key = form.elements.namedItem('submission_key') as HTMLInputElement
        key.value = crypto.randomUUID ? crypto.randomUUID() : key.value // the next message is a new one
      } else if (res.status === 422 && json.errors) {
        Object.entries(json.errors as Record<string, string[]>).forEach(([k, v]) => fieldError(k, v[0]))
        setStatus('', null)
        ;(form.querySelector('[aria-invalid="true"]') as HTMLElement | null)?.focus()
      } else if (res.status === 429) {
        setStatus(ctx.data.tooMany, false)
      } else if (res.status === 419) {
        form.submit() // session expired: fall back to a normal post, which renews the page
      } else {
        setStatus(json.message || form.dataset.error || ctx.data.failure, false)
      }
    } catch {
      setStatus(form.dataset.error || ctx.data.failure, false)
    } finally {
      busy = false; button.removeAttribute('aria-busy')
    }
  })
}

const caseStudy: Init = (el, ctx) => {
  if (ctx.reduced) return
  gsap.from($$(el, '.case-hero .w'), { yPercent: 110, duration: 1.2, stagger: 0.05, ease: 'expo.out', delay: 0.15 })
  const img = $(el, '.case-img img')
  if (img) gsap.from(img, { scale: 1.25, duration: 1.8, ease: 'expo.out', delay: 0.1 })
  $$(el, '.shots figure, .phones figure').forEach(f => gsap.from(f, { y: 60, opacity: 0, duration: 1.1, ease: 'expo.out', scrollTrigger: { trigger: f, start: 'top 90%' } }))
}

/** generic: masked title reveal + fade-in for simple sections */
const reveal: Init = (el, ctx) => {
  if (ctx.reduced) return
  $$(el, '.split').forEach(s => masked(s, ctx))
  gsap.from($$(el, '.prose p, .q, .skill-group, .stats > div, .it-media, .intro, .hero-cta'), { y: 40, opacity: 0, duration: 1, stagger: 0.08, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 80%' } })
}

export const sections: Record<string, Init> = { hero, statement, about, cube, reel, index, services, process, cv, band, contact, case: caseStudy, reveal }
