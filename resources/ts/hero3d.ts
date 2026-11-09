import * as THREE from 'three'

/* Hero scene from the original design: a noise-displaced sphere with a brick → turquoise fresnel skin,
   a stone wireframe shell, an orbit ring and drifting particles. Reacts to the pointer and to scroll.
   Changes for production: geometry detail and pixel ratio follow the device (the original 96-detail
   icosahedron is ~188k triangles — 56 on desktop and 28 on phones look the same at this size), rendering
   stops when the canvas is off-screen or the tab is hidden, and destroy() releases every GPU resource. */
const noise = /* glsl */`
vec3 mod289(vec3 x){return x-floor(x*(1./289.))*289.;}vec4 mod289(vec4 x){return x-floor(x*(1./289.))*289.;}
vec4 permute(vec4 x){return mod289(((x*34.)+1.)*x);}vec4 taylorInvSqrt(vec4 r){return 1.79284291400159-.85373472095314*r;}
float snoise(vec3 v){const vec2 C=vec2(1./6.,1./3.);const vec4 D=vec4(0.,.5,1.,2.);vec3 i=floor(v+dot(v,C.yyy));vec3 x0=v-i+dot(i,C.xxx);
vec3 g=step(x0.yzx,x0.xyz);vec3 l=1.-g;vec3 i1=min(g.xyz,l.zxy);vec3 i2=max(g.xyz,l.zxy);vec3 x1=x0-i1+C.xxx;vec3 x2=x0-i2+C.yyy;vec3 x3=x0-D.yyy;
i=mod289(i);vec4 p=permute(permute(permute(i.z+vec4(0.,i1.z,i2.z,1.))+i.y+vec4(0.,i1.y,i2.y,1.))+i.x+vec4(0.,i1.x,i2.x,1.));
float n_=.142857142857;vec3 ns=n_*D.wyz-D.xzx;vec4 j=p-49.*floor(p*ns.z*ns.z);vec4 x_=floor(j*ns.z);vec4 y_=floor(j-7.*x_);
vec4 x=x_*ns.x+ns.yyyy;vec4 y=y_*ns.x+ns.yyyy;vec4 h=1.-abs(x)-abs(y);vec4 b0=vec4(x.xy,y.xy);vec4 b1=vec4(x.zw,y.zw);
vec4 s0=floor(b0)*2.+1.;vec4 s1=floor(b1)*2.+1.;vec4 sh=-step(h,vec4(0.));vec4 a0=b0.xzyw+s0.xzyw*sh.xxyy;vec4 a1=b1.xzyw+s1.xzyw*sh.zzww;
vec3 p0=vec3(a0.xy,h.x);vec3 p1=vec3(a0.zw,h.y);vec3 p2=vec3(a1.xy,h.z);vec3 p3=vec3(a1.zw,h.w);
vec4 norm=taylorInvSqrt(vec4(dot(p0,p0),dot(p1,p1),dot(p2,p2),dot(p3,p3)));p0*=norm.x;p1*=norm.y;p2*=norm.z;p3*=norm.w;
vec4 m=max(.6-vec4(dot(x0,x0),dot(x1,x1),dot(x2,x2),dot(x3,x3)),0.);m=m*m;return 42.*dot(m*m,vec4(dot(p0,x0),dot(p1,x1),dot(p2,x2),dot(p3,x3)));}`

export interface HeroScene {
  setScroll(progress: number): void
  destroy(): void
}

export interface HeroOptions {
  brick: string
  signal: string
  /** low-power profile: phones and tablets */
  light: boolean
}

export function createHeroScene(canvas: HTMLCanvasElement, opts: HeroOptions): HeroScene | null {
  let renderer: THREE.WebGLRenderer
  try {
    renderer = new THREE.WebGLRenderer({ canvas, antialias: !opts.light, alpha: true, powerPreference: opts.light ? 'low-power' : 'high-performance' })
  } catch {
    return null
  }
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, opts.light ? 1.25 : 1.5))
  const scene = new THREE.Scene()
  const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100)
  camera.position.set(0, 0, 7.2)

  const uniforms = {
    uTime: { value: 0 }, uAmp: { value: 0.2 }, uMouse: { value: new THREE.Vector2() },
    uBrick: { value: new THREE.Color(opts.brick) }, uTurq: { value: new THREE.Color(opts.signal) }, uStone: { value: new THREE.Color('#ABA6A3') },
  }
  const vert = /* glsl */`
    uniform float uTime; uniform float uAmp; uniform vec2 uMouse; varying vec3 vN; varying vec3 vView; varying float vD;
    ${noise}
    float disp(vec3 p){ return snoise(p*.9 + vec3(uTime*.16, uTime*.1, 0.)) * uAmp + snoise(p*2.4 - uTime*.2)*uAmp*.15; }
    void main(){
      vec3 p = position; float d = disp(normalize(p) + vec3(uMouse*.35, 0.)); vD = d;
      vec3 np = p + normal * d;
      float e = .02; vec3 t = normalize(cross(normal, vec3(0.,1.,0.)+.001)); vec3 bt = normalize(cross(normal, t));
      vec3 a = p + t*e; a += normal*disp(normalize(a)+vec3(uMouse*.35,0.)); vec3 bb = p + bt*e; bb += normal*disp(normalize(bb)+vec3(uMouse*.35,0.));
      vN = normalize(normalMatrix * normalize(cross(a-np, bb-np)));
      vec4 mv = modelViewMatrix * vec4(np, 1.); vView = normalize(-mv.xyz);
      gl_Position = projectionMatrix * mv;
    }`
  const frag = /* glsl */`
    uniform vec3 uBrick; uniform vec3 uTurq; uniform vec3 uStone; uniform float uTime; varying vec3 vN; varying vec3 vView; varying float vD;
    void main(){
      float fr = pow(1. - max(dot(vN, vView), 0.), 2.2);
      vec3 L = normalize(vec3(-.4, .7, .8)); float diff = max(dot(vN, L), 0.);
      float spec = pow(max(dot(reflect(-L, vN), vView), 0.), 40.);
      vec3 base = mix(vec3(.07,.05,.05), uBrick, .25 + diff*.75);
      base = mix(base, uStone*.55, smoothstep(.15, .45, vD)*.35);
      vec3 col = base + uTurq * fr * 1.15 + spec * .55;
      gl_FragColor = vec4(col, 1.);
    }`

  const disposables: Array<{ dispose(): void }> = []
  const keep = <T extends { dispose(): void }>(x: T) => { disposables.push(x); return x }

  const geo = keep(new THREE.IcosahedronGeometry(1.45, opts.light ? 28 : 56))
  const mat = keep(new THREE.ShaderMaterial({ uniforms, vertexShader: vert, fragmentShader: frag }))
  const blob = new THREE.Mesh(geo, mat)
  const group = new THREE.Group(); group.add(blob); scene.add(group)

  const shell = new THREE.Mesh(keep(new THREE.IcosahedronGeometry(2.05, 2)), keep(new THREE.MeshBasicMaterial({ color: '#ABA6A3', wireframe: true, transparent: true, opacity: 0.09 })))
  group.add(shell)
  const ring = new THREE.Mesh(keep(new THREE.TorusGeometry(2.55, 0.006, 8, 220)), keep(new THREE.MeshBasicMaterial({ color: opts.signal, transparent: true, opacity: 0.55 })))
  ring.rotation.set(1.2, 0.25, 0); group.add(ring)
  const moon = new THREE.Mesh(keep(new THREE.SphereGeometry(0.04, 16, 16)), keep(new THREE.MeshBasicMaterial({ color: opts.signal })))
  group.add(moon)

  const N = opts.light ? 500 : 900
  const pos = new Float32Array(N * 3)
  for (let i = 0; i < N; i++) {
    const r = 3.2 + Math.random() * 4.5, t = Math.random() * Math.PI * 2, u = Math.acos(2 * Math.random() - 1)
    pos[i * 3] = r * Math.sin(u) * Math.cos(t); pos[i * 3 + 1] = r * Math.sin(u) * Math.sin(t) * 0.6; pos[i * 3 + 2] = r * Math.cos(u) - 2
  }
  const pg = keep(new THREE.BufferGeometry()); pg.setAttribute('position', new THREE.BufferAttribute(pos, 3))
  const dots = new THREE.Points(pg, keep(new THREE.PointsMaterial({ color: '#ABA6A3', size: 0.018, transparent: true, opacity: 0.55, sizeAttenuation: true })))
  scene.add(dots)

  const mouse = new THREE.Vector2(), target = new THREE.Vector2()
  let scrollP = 0, inView = true, raf = 0, destroyed = false
  const start = performance.now()
  const onMove = (e: PointerEvent) => { target.set((e.clientX / innerWidth) * 2 - 1, -(e.clientY / innerHeight) * 2 + 1) }
  const resize = () => {
    const w = canvas.clientWidth, h = canvas.clientHeight
    if (!w || !h) return
    renderer.setSize(w, h, false); camera.aspect = w / h; camera.updateProjectionMatrix(); group.scale.setScalar(0.92)
  }
  const running = () => inView && document.visibilityState === 'visible' && !destroyed
  const loop = (now?: number) => {
    raf = 0
    if (!running()) return
    const t = ((now ?? performance.now()) - start) / 1000
    mouse.lerp(target, 0.05)
    uniforms.uTime.value = t; uniforms.uMouse.value.copy(mouse); uniforms.uAmp.value = 0.2 + scrollP * 0.3
    blob.rotation.y = t * 0.08 + mouse.x * 0.35; blob.rotation.x = mouse.y * 0.25
    shell.rotation.y = -t * 0.05; shell.rotation.x = t * 0.03
    ring.rotation.z = t * 0.12
    moon.position.set(Math.cos(t * 0.6) * 2.55, Math.sin(t * 0.6) * 2.55 * Math.cos(1.2), Math.sin(t * 0.6) * 2.55 * Math.sin(1.2) * -1)
    dots.rotation.y = t * 0.015 + mouse.x * 0.08
    camera.position.y = -scrollP * 1.2; camera.position.z = 7.2 + scrollP * 1.6
    renderer.render(scene, camera)
    raf = requestAnimationFrame(loop)
  }
  const wake = () => { if (running() && !raf) raf = requestAnimationFrame(loop) }

  addEventListener('pointermove', onMove, { passive: true })
  addEventListener('resize', resize)
  document.addEventListener('visibilitychange', wake)
  const io = new IntersectionObserver(([entry]) => { inView = entry.isIntersecting; wake() })
  io.observe(canvas)
  resize()
  wake()

  return {
    setScroll: p => { scrollP = p },
    destroy: () => {
      destroyed = true
      if (raf) cancelAnimationFrame(raf)
      io.disconnect()
      removeEventListener('pointermove', onMove)
      removeEventListener('resize', resize)
      document.removeEventListener('visibilitychange', wake)
      disposables.forEach(d => d.dispose())
      renderer.dispose()
      renderer.forceContextLoss()
    },
  }
}
