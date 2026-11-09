/* Public site runtime. The HTML is complete without JavaScript; this file adds the cinematic layer:
   smooth scrolling, reveals, the 3D hero, cursor, drawer, page curtain and the contact form.
   Everything is created inside one gsap.context + AbortController and torn down on pagehide, so
   back/forward navigation or a language switch never doubles listeners or animation loops. */
import gsap from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'
import Lenis from 'lenis'
import { createCtx, type Ctx } from './runtime'
import { sections } from './sections'

gsap.registerPlugin(ScrollTrigger)

declare global {
  interface Window { __site?: { boots: number; active: boolean } }
}

const html = document.documentElement
const debug = (window.__site ??= { boots: 0, active: false })

let teardown: (() => void) | null = null

function boot() {
  if (debug.active) teardown?.() // never run twice on the same document
  debug.boots++
  debug.active = true

  const motionOff = html.dataset.motion === 'off' || matchMedia('(prefers-reduced-motion: reduce)').matches
  const fine = matchMedia('(hover: hover) and (pointer: fine)').matches
  let lenis: Lenis | null = null
  let tick: ((t: number) => void) | null = null
  if (!motionOff && html.dataset.smooth === '1' && fine) {
    lenis = new Lenis({ duration: 1.15, smoothWheel: true })
    lenis.on('scroll', ScrollTrigger.update)
    tick = (time: number) => lenis!.raf(time * 1000)
    gsap.ticker.add(tick)
    gsap.ticker.lagSmoothing(0)
  }

  const { ctx, dispose } = createCtx(lenis)
  const gctx = gsap.context(() => {
    loader(ctx)
    document.querySelectorAll<HTMLElement>('[data-module]').forEach(el => {
      const init = sections[el.dataset.module || '']
      if (init) { try { init(el, ctx) } catch (e) { console.warn('section init failed', el.dataset.module, e) } }
    })
    chrome(ctx)
  })

  const refresh = () => ScrollTrigger.refresh()
  requestAnimationFrame(refresh)
  document.fonts?.ready.then(refresh)
  ctx.on(window, 'load', refresh, { once: true })

  teardown = () => {
    gctx.revert()
    ScrollTrigger.getAll().forEach(t => t.kill())
    dispose()
    if (tick) gsap.ticker.remove(tick)
    lenis?.destroy()
    debug.active = false
    teardown = null
  }
}

/* ------------------------------------------------------------ page chrome */
function chrome(ctx: Ctx) {
  clocks(ctx)
  drawer(ctx)
  anchors(ctx)
  languageSwitch(ctx)
  curtain(ctx)
  fab(ctx)
  if (ctx.fine && !ctx.reduced && !ctx.calm && html.dataset.cursor === '1') cursor(ctx)
  footer(ctx)
}

function clocks(ctx: Ctx) {
  const nodes = document.querySelectorAll<HTMLElement>('[data-clock]')
  if (!nodes.length) return
  const tz = html.dataset.tz || 'Asia/Riyadh', label = html.dataset.tzLabel || ''
  const fmt = new Intl.DateTimeFormat(ctx.lang === 'ar' ? 'ar-SA-u-nu-latn' : 'en-GB', { timeZone: tz, hour: '2-digit', minute: '2-digit', hour12: false })
  const tick = () => { const s = `${fmt.format(new Date())}${label ? ' ' + label : ''}`; nodes.forEach(n => (n.textContent = s)) }
  tick(); ctx.timer(tick, 15000, true)
}

function drawer(ctx: Ctx) {
  const box = document.getElementById('drawer'), panel = box?.querySelector<HTMLElement>('.panel')
  if (!box || !panel) return
  const openers = document.querySelectorAll<HTMLElement>('[data-drawer-open]')
  let lastFocus: HTMLElement | null = null
  const off = ctx.rtl ? '-105%' : '105%'
  gsap.set(panel, { x: off })
  const focusables = () => Array.from(panel.querySelectorAll<HTMLElement>('a[href], button:not([disabled])'))
  const open = () => {
    lastFocus = document.activeElement as HTMLElement
    box.classList.add('open'); box.removeAttribute('inert'); box.setAttribute('aria-hidden', 'false')
    openers.forEach(o => o.setAttribute('aria-expanded', 'true'))
    ctx.lenis?.stop()
    gsap.to(panel, { x: 0, duration: ctx.reduced ? 0 : 0.8, ease: 'expo.out' })
    gsap.to(box.querySelector('.scrim'), { opacity: 1, duration: ctx.reduced ? 0 : 0.5 })
    if (!ctx.reduced) gsap.from(panel.querySelectorAll('a.big'), { y: 40, opacity: 0, stagger: 0.06, duration: 0.8, ease: 'expo.out', delay: 0.1 })
    requestAnimationFrame(() => focusables()[0]?.focus())
  }
  const close = (restore = true) => {
    if (!box.classList.contains('open')) return
    box.classList.remove('open'); box.setAttribute('aria-hidden', 'true'); box.setAttribute('inert', '')
    openers.forEach(o => o.setAttribute('aria-expanded', 'false'))
    ctx.lenis?.start()
    gsap.to(panel, { x: off, duration: ctx.reduced ? 0 : 0.7, ease: 'expo.inOut' })
    gsap.to(box.querySelector('.scrim'), { opacity: 0, duration: ctx.reduced ? 0 : 0.4 })
    if (restore) lastFocus?.focus()
  }
  openers.forEach(o => ctx.on(o, 'click', open))
  box.querySelectorAll('[data-drawer-close]').forEach(c => ctx.on(c, 'click', () => close()))
  box.querySelectorAll('[data-drawer-link]').forEach(a => ctx.on(a, 'click', () => close(false)))
  ctx.on(document, 'keydown', (e: KeyboardEvent) => {
    if (!box.classList.contains('open')) return
    if (e.key === 'Escape') { e.preventDefault(); close() }
    if (e.key === 'Tab') { // keep focus inside the open menu
      const f = focusables(); if (!f.length) return
      const first = f[0], last = f[f.length - 1]
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus() } else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus() }
    }
  })
}

/** in-page links (#about or /ar#about while on that page) scroll smoothly; focus moves to the target */
function anchors(ctx: Ctx) {
  ctx.on(document, 'click', (e: MouseEvent) => {
    const a = (e.target as HTMLElement).closest<HTMLAnchorElement>('a[href]')
    if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return
    const url = new URL(a.href, location.href)
    if (url.origin !== location.origin || url.pathname !== location.pathname || url.search !== location.search || !url.hash) return
    const target = url.hash === '#main' ? document.body : document.getElementById(decodeURIComponent(url.hash.slice(1)))
    if (!target) return
    e.preventDefault()
    history.pushState(null, '', url.hash)
    if (ctx.lenis) ctx.lenis.scrollTo(target, { offset: 0, duration: 1.6 })
    else target.scrollIntoView({ behavior: ctx.reduced ? 'auto' : 'smooth' })
    const focusTarget = url.hash === '#main' ? document.getElementById('main') : target
    focusTarget?.setAttribute('tabindex', focusTarget.getAttribute('tabindex') ?? '-1')
    focusTarget?.focus({ preventScroll: true })
  })
  // arriving with a hash: Lenis needs a nudge once layout (pins) is ready
  if (location.hash && ctx.lenis) {
    const target = document.getElementById(decodeURIComponent(location.hash.slice(1)))
    if (target) ctx.timer(() => ctx.lenis?.scrollTo(target, { immediate: true }), 120)
  }
}

/** keeps the reader on the same chapter when switching language */
function languageSwitch(ctx: Ctx) {
  document.querySelectorAll<HTMLAnchorElement>('[data-lang-switch]').forEach(a => ctx.on(a, 'click', () => {
    if (location.hash) a.href = a.href.split('#')[0] + location.hash
  }))
}

/** a short dark curtain between pages (skipped for new tabs, downloads, anchors and reduced motion) */
function curtain(ctx: Ctx) {
  const el = document.getElementById('curtain')
  if (!el || ctx.reduced) return
  gsap.set(el, { autoAlpha: 0 })
  ctx.on(document, 'click', (e: MouseEvent) => {
    const a = (e.target as HTMLElement).closest<HTMLAnchorElement>('a[href]')
    if (!a || e.defaultPrevented || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey || e.button !== 0) return
    if (a.target === '_blank' || a.hasAttribute('download') || a.hasAttribute('data-cv-link')) return
    const url = new URL(a.href, location.href)
    if (url.origin !== location.origin || url.pathname.startsWith('/admin') || url.pathname.includes('/cv/download')) return
    if (url.pathname === location.pathname && url.search === location.search) return
    e.preventDefault()
    gsap.to(el, { autoAlpha: 1, duration: 0.35, ease: 'power2.out', onComplete: () => { location.href = url.href } })
    ctx.timer(() => { location.href = url.href }, 900) // safety net
  })
}

/** the header turns into a floating menu button once the opening section is passed */
function fab(ctx: Ctx) {
  const btn = document.getElementById('fab'), links = document.querySelector('header.top nav.links')
  if (!btn) return
  const first = document.querySelector<HTMLElement>('.hero, .page-head, .case-hero') || document.querySelector<HTMLElement>('main > *')
  if (!first) return
  ScrollTrigger.create({
    trigger: first, start: '80% top',
    onEnter: () => { gsap.to(btn, { scale: 1, duration: ctx.reduced ? 0 : 0.5, ease: 'back.out(1.8)' }); if (links) gsap.to(links, { autoAlpha: 0, y: -20, duration: 0.4 }) },
    onLeaveBack: () => { gsap.to(btn, { scale: 0, duration: ctx.reduced ? 0 : 0.3 }); if (links) gsap.to(links, { autoAlpha: 1, y: 0, duration: 0.4 }) },
  })
}

function cursor(ctx: Ctx) {
  const dot = document.getElementById('cdot'), ring = document.getElementById('cring'), spot = document.getElementById('spot')
  if (!dot || !ring) return
  const dx = gsap.quickTo(dot, 'x', { duration: 0.12 }), dy = gsap.quickTo(dot, 'y', { duration: 0.12 })
  const rx = gsap.quickTo(ring, 'x', { duration: 0.5, ease: 'power3' }), ry = gsap.quickTo(ring, 'y', { duration: 0.5, ease: 'power3' })
  const sx = spot ? gsap.quickTo(spot, 'x', { duration: 0.9, ease: 'power3' }) : null, sy = spot ? gsap.quickTo(spot, 'y', { duration: 0.9, ease: 'power3' }) : null
  ctx.on(window, 'pointermove', (e: PointerEvent) => { dx(e.clientX); dy(e.clientY); rx(e.clientX); ry(e.clientY); sx?.(e.clientX); sy?.(e.clientY) }, { passive: true })
  ctx.on(document, 'pointerover', (e: PointerEvent) => {
    const el = (e.target as HTMLElement).closest<HTMLElement>('[data-cursor]')
    if (el && el.dataset.cursor) { ring.textContent = el.dataset.cursor; gsap.to(ring, { scale: 1, duration: 0.4, ease: 'back.out(2)' }); gsap.to(dot, { scale: 0, duration: 0.2 }) }
  })
  ctx.on(document, 'pointerout', (e: PointerEvent) => {
    const el = (e.target as HTMLElement).closest('[data-cursor]')
    if (el && !el.contains(e.relatedTarget as Node)) { gsap.to(ring, { scale: 0, duration: 0.3 }); gsap.to(dot, { scale: 1, duration: 0.2 }) }
  })
  document.querySelectorAll<HTMLElement>('[data-magnetic]').forEach(el => {
    const s = Number(el.dataset.strength || 0.3)
    const qx = gsap.quickTo(el, 'x', { duration: 0.8, ease: 'elastic.out(1,.4)' }), qy = gsap.quickTo(el, 'y', { duration: 0.8, ease: 'elastic.out(1,.4)' })
    ctx.on(el, 'pointermove', (e: PointerEvent) => { const r = el.getBoundingClientRect(); qx((e.clientX - r.left - r.width / 2) * s); qy((e.clientY - r.top - r.height / 2) * s) })
    ctx.on(el, 'pointerleave', () => { qx(0); qy(0) })
  })
}

function footer(ctx: Ctx) {
  if (ctx.reduced) return
  gsap.from('.foot-giant span', { yPercent: 100, duration: 1.6, ease: 'expo.out', scrollTrigger: { trigger: '.foot-giant', start: 'top 95%' } })
  gsap.from('.foot-cols > *', { y: 40, opacity: 0, stagger: 0.08, duration: 1, ease: 'expo.out', scrollTrigger: { trigger: '.foot-cols', start: 'top 90%' } })
  document.querySelectorAll<HTMLAnchorElement>('[data-scroll-top]').forEach(a => ctx.on(a, 'click', (e: MouseEvent) => {
    e.preventDefault()
    ctx.lenis ? ctx.lenis.scrollTo(0, { duration: 1.6 }) : scrollTo({ top: 0, behavior: ctx.reduced ? 'auto' : 'smooth' })
    document.getElementById('main')?.focus({ preventScroll: true })
  }))
}

/* --------------------------------------------------------------- loader */
/** Greeting screen on the first visit of the home page in a session. It is not a progress bar:
    it leaves as soon as fonts and the portrait are ready, and never stays longer than 1.6 s. */
function loader(ctx: Ctx) {
  let seen = false
  try { seen = sessionStorage.getItem('ma-loader') === '1' } catch { /* storage blocked */ }
  const tpl = document.getElementById('loader-tpl') as HTMLTemplateElement | null
  if (html.dataset.loader !== '1' || ctx.reduced || seen || !tpl || ctx.data.greetings.length === 0) return
  try { sessionStorage.setItem('ma-loader', '1') } catch { /* fine */ }
  html.classList.add('is-loading')
  document.body.appendChild(tpl.content.cloneNode(true))
  const el = document.getElementById('loader')!, greet = document.getElementById('greet')!
  let i = 0
  greet.textContent = ctx.data.greetings[0]
  ctx.timer(() => { i = Math.min(i + 1, ctx.data.greetings.length - 1); greet.textContent = ctx.data.greetings[i] }, 150, true)
  const portrait = document.querySelector<HTMLImageElement>('.hero .me img')
  const ready = Promise.all([
    document.fonts?.ready ?? Promise.resolve(),
    portrait && !portrait.complete ? new Promise(r => { portrait.addEventListener('load', r, { once: true }); portrait.addEventListener('error', r, { once: true }) }) : Promise.resolve(),
  ])
  let done = false
  const finish = () => {
    if (done) return; done = true
    gsap.to(el, { yPercent: -100, duration: 1, ease: 'expo.inOut', onComplete: () => el.remove() })
    ctx.timer(() => { html.classList.remove('is-loading'); document.dispatchEvent(new Event('site:loaded')) }, 250)
  }
  Promise.race([ready.then(() => new Promise(r => setTimeout(r, 650))), new Promise(r => setTimeout(r, 1600))]).then(finish)
  ctx.cleanup(() => { el.remove(); html.classList.remove('is-loading') })
}

/* ------------------------------------------------------------ lifecycle */
// Only pages kept in the back/forward cache need tearing down; other pages are discarded by the browser.
addEventListener('pagehide', e => { if ((e as PageTransitionEvent).persisted) teardown?.() })
addEventListener('pageshow', e => {
  if ((e as PageTransitionEvent).persisted) { // restored from the back/forward cache
    const c = document.getElementById('curtain'); if (c) gsap.set(c, { autoAlpha: 0 })
    boot()
  }
})
document.readyState === 'loading' ? addEventListener('DOMContentLoaded', boot, { once: true }) : boot()
