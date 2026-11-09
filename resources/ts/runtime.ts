/* Shared runtime context for every module: settings read from <html data-*>, and one place that
   tracks listeners, timers and animation frames so a page can be torn down without leaks
   (back/forward cache, language switch, navigation). */
import type Lenis from 'lenis'

export type Cleanup = () => void

export interface Ctx {
  lang: 'ar' | 'en'
  rtl: boolean
  /** +1 for Arabic (content travels right), -1 for English (travels left) — same convention as the original */
  dir: 1 | -1
  /** prefers-reduced-motion or motion set to "off" in the dashboard */
  reduced: boolean
  /** "calm" motion level: keep reveals, drop heavy effects */
  calm: boolean
  /** mouse / trackpad (hover + fine pointer) */
  fine: boolean
  lenis: Lenis | null
  data: SiteData
  /** listener helper bound to the page's AbortController */
  on: <K extends keyof WindowEventMap>(target: EventTarget, type: K | string, fn: (e: any) => void, opts?: AddEventListenerOptions) => void
  timer: (fn: () => void, ms: number, repeat?: boolean) => void
  cleanup: (fn: Cleanup) => void
}

export interface SiteData {
  greetings: string[]
  other: string
  sending: string
  tooMany: string
  failure: string
  required: string
  invalidEmail: string
}

export function createCtx(lenis: Lenis | null): { ctx: Ctx; dispose: () => void } {
  const html = document.documentElement
  const motion = html.dataset.motion || 'full'
  const prefersReduced = matchMedia('(prefers-reduced-motion: reduce)').matches
  const controller = new AbortController()
  const timers = new Set<number>()
  const cleanups: Cleanup[] = []
  let data: SiteData = { greetings: [], other: 'en', sending: '…', tooMany: '', failure: '', required: '', invalidEmail: '' }
  try { data = { ...data, ...JSON.parse(document.getElementById('site-data')?.textContent || '{}') } } catch { /* keep defaults */ }

  const lang = (html.lang === 'ar' ? 'ar' : 'en') as 'ar' | 'en'
  const ctx: Ctx = {
    lang,
    rtl: html.dir === 'rtl',
    dir: lang === 'ar' ? 1 : -1,
    reduced: prefersReduced || motion === 'off',
    calm: motion === 'calm',
    fine: matchMedia('(hover: hover) and (pointer: fine)').matches,
    lenis,
    data,
    on: (target, type, fn, opts = {}) => target.addEventListener(type as string, fn as EventListener, { ...opts, signal: controller.signal }),
    timer: (fn, ms, repeat = false) => {
      const id = repeat ? window.setInterval(fn, ms) : window.setTimeout(fn, ms)
      timers.add(id)
    },
    cleanup: fn => { cleanups.push(fn) },
  }

  return {
    ctx,
    dispose: () => {
      controller.abort()
      timers.forEach(id => { clearTimeout(id); clearInterval(id) })
      timers.clear()
      while (cleanups.length) { try { cleanups.pop()!() } catch { /* keep tearing down */ } }
    },
  }
}
