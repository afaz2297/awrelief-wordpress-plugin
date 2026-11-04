/* AWRELIEF · WebGL smoke
   Lightweight fbm smoke drawn on a canvas. Two modes:
   - ambient: soft wisps drifting across the frame (hero, newsletter)
   - plume:   a single column rising from the bottom centre (candle wick) */
(function () {
  const VERT = 'attribute vec2 p;void main(){gl_Position=vec4(p,0.,1.);}';
  const FRAG = `
precision mediump float;
uniform vec2 uRes; uniform float uTime; uniform float uStrength; uniform float uPlume; uniform vec2 uMouse;
float hash(vec2 p){ p = fract(p*vec2(123.34,456.21)); p += dot(p,p+45.32); return fract(p.x*p.y); }
float noise(vec2 p){ vec2 i=floor(p), f=fract(p); vec2 u=f*f*(3.-2.*f);
  return mix(mix(hash(i),hash(i+vec2(1,0)),u.x), mix(hash(i+vec2(0,1)),hash(i+vec2(1,1)),u.x), u.y); }
float fbm(vec2 p){ float v=0., a=.5; mat2 r=mat2(.8,.6,-.6,.8); for(int i=0;i<4;i++){ v+=a*noise(p); p=r*p*2.02+vec2(1.7,9.2); a*=.5; } return v; }
void main(){
  vec2 uv = gl_FragCoord.xy / uRes;
  float asp = uRes.x / uRes.y;
  vec2 p = vec2(uv.x*asp, uv.y);
  float t = uTime;
  float d;
  if (uPlume > .5) {
    float sway = .045*sin(uv.y*6. - t*.9) + .03*sin(uv.y*13. - t*1.7);
    float w = .025 + uv.y*.30;
    float col = exp(-pow((uv.x-.5-sway*uv.y*1.6)/w, 2.));
    vec2 q = vec2(fbm(p*3. + vec2(0., -t*.55)), fbm(p*3. + vec2(4.1, -t*.45)));
    float f = fbm(p*2.6 + q*1.8 + vec2(0., -t*.7));
    d = col * smoothstep(.25, .85, f) * smoothstep(0., .06, uv.y) * (1. - smoothstep(.55, 1., uv.y));
    d *= 1.6;
  } else {
    vec2 m = vec2(uMouse.x*asp, uMouse.y);
    vec2 toM = p - m; float md = length(toM);
    p += normalize(toM + 1e-4) * .08 * exp(-md*5.);
    vec2 q = vec2(fbm(p*1.4 + vec2(t*.03, -t*.05)), fbm(p*1.4 + vec2(5.2, 1.3) - t*.04));
    float f = fbm(p*1.7 + q*2.2 + vec2(-t*.02, -t*.07));
    d = smoothstep(.42, .95, f);
    d *= .45 + .55*smoothstep(1., .1, uv.y);
  }
  float a = clamp(d * uStrength, 0., 1.) * .55;
  vec3 c = mix(vec3(1., .86, .66), vec3(1., .97, .92), d);
  gl_FragColor = vec4(c * a, a);
}`;

  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const instances = [];

  function make(canvas) {
    const gl = canvas.getContext('webgl', { premultipliedAlpha: true, alpha: true, antialias: false, powerPreference: 'low-power' });
    if (!gl) return null;
    const sh = (type, src) => { const s = gl.createShader(type); gl.shaderSource(s, src); gl.compileShader(s);
      if (!gl.getShaderParameter(s, gl.COMPILE_STATUS)) { console.warn(gl.getShaderInfoLog(s)); return null; } return s; };
    const prog = gl.createProgram();
    const vs = sh(gl.VERTEX_SHADER, VERT), fs = sh(gl.FRAGMENT_SHADER, FRAG);
    if (!vs || !fs) return null;
    gl.attachShader(prog, vs); gl.attachShader(prog, fs); gl.linkProgram(prog); gl.useProgram(prog);
    const buf = gl.createBuffer(); gl.bindBuffer(gl.ARRAY_BUFFER, buf);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
    const loc = gl.getAttribLocation(prog, 'p'); gl.enableVertexAttribArray(loc); gl.vertexAttribPointer(loc, 2, gl.FLOAT, false, 0, 0);
    gl.enable(gl.BLEND); gl.blendFunc(gl.ONE, gl.ONE_MINUS_SRC_ALPHA);
    const u = n => gl.getUniformLocation(prog, n);
    const inst = {
      canvas, gl, visible: false,
      uRes: u('uRes'), uTime: u('uTime'), uStrength: u('uStrength'), uPlume: u('uPlume'), uMouse: u('uMouse'),
      strength: parseFloat(canvas.dataset.smokeStrength || '.6'),
      plume: canvas.dataset.smokeMode === 'plume' ? 1 : 0,
      mouse: [.5, .5], target: [.5, .5], boost: 1
    };
    const resize = () => {
      const r = canvas.getBoundingClientRect();
      const scale = inst.plume ? .5 : .3; // drawn small and upscaled by CSS; the smoke is soft anyway
      canvas.width = Math.max(2, Math.round(r.width * scale));
      canvas.height = Math.max(2, Math.round(r.height * scale));
      gl.viewport(0, 0, canvas.width, canvas.height);
    };
    resize();
    new ResizeObserver(resize).observe(canvas);
    new IntersectionObserver(([e]) => { inst.visible = e.isIntersecting; }, { rootMargin: '100px' }).observe(canvas);
    if (!inst.plume) {
      canvas.parentElement.addEventListener('pointermove', e => {
        const r = canvas.getBoundingClientRect();
        inst.target = [(e.clientX - r.left) / r.width, 1 - (e.clientY - r.top) / r.height];
      });
    }
    return inst;
  }

  function draw(inst, t) {
    const { gl } = inst;
    inst.mouse[0] += (inst.target[0] - inst.mouse[0]) * .05;
    inst.mouse[1] += (inst.target[1] - inst.mouse[1]) * .05;
    gl.clearColor(0, 0, 0, 0); gl.clear(gl.COLOR_BUFFER_BIT);
    gl.uniform2f(inst.uRes, inst.canvas.width, inst.canvas.height);
    gl.uniform1f(inst.uTime, t);
    gl.uniform1f(inst.uStrength, inst.strength * inst.boost);
    gl.uniform1f(inst.uPlume, inst.plume);
    gl.uniform2f(inst.uMouse, inst.mouse[0], inst.mouse[1]);
    gl.drawArrays(gl.TRIANGLES, 0, 3);
  }

  // Smoke moves slowly, so 30 fps looks identical and halves GPU work (a third on 90/144 Hz screens).
  let last = 0;
  function loop(now) {
    requestAnimationFrame(loop);
    if (document.hidden || now - last < 32) return;
    last = now;
    const t = now / 1000;
    instances.forEach(i => { if (i.visible) draw(i, t); });
  }

  window.AWSmoke = {
    init() {
      document.querySelectorAll('[data-smoke]').forEach(c => { const i = make(c); if (i) { instances.push(i); c._smoke = i; } });
      if (reduced) { instances.forEach(i => draw(i, 12)); return; }
      requestAnimationFrame(loop);
    }
  };
})();
