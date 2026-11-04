/* AWRELIEF · site motion and interface
   GSAP + ScrollTrigger + Lenis. Every effect has a reduced-motion and touch fallback.
   Inside the Elementor editor everything renders in its calm, static state. */
(function () {
  const C = window.AFZ || {};
  const { gsap, ScrollTrigger } = window;
  if (!gsap || !ScrollTrigger) return;
  gsap.registerPlugin(ScrollTrigger);

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches || !!C.editor;
  const touch = window.matchMedia('(hover: none), (pointer: coarse)').matches;
  const mm = gsap.matchMedia();
  const money = n => (C.currency || '£') + Number(n).toFixed(2);
  const motion = C.motion || {};
  const body = document.body;

  if (reduced) document.documentElement.classList.add('rm');

  /* ---------------------------------------------------------
     Text splitting
     --------------------------------------------------------- */
  function wrapTextNodes(el, unit) {
    // gold <em> words keep one gradient, so each <em> moves as a single unit instead of being split
    $$('em', el).forEach(em => {
      const w = document.createElement('span'); w.className = unit;
      em.replaceWith(w); w.appendChild(em);
    });
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
    const nodes = [];
    while (walker.nextNode()) nodes.push(walker.currentNode);
    nodes.forEach(node => {
      if (!node.textContent.trim() || node.parentElement.closest('em')) return;
      const frag = document.createDocumentFragment();
      node.textContent.split(/(\s+)/).forEach(part => {
        if (!part) return;
        if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(' ')); return; }
        if (unit === 'word') {
          const w = document.createElement('span'); w.className = 'word'; w.textContent = part; frag.appendChild(w);
        } else {
          const w = document.createElement('span'); w.style.display = 'inline-block'; w.style.whiteSpace = 'nowrap';
          [...part].forEach(ch => { const c = document.createElement('span'); c.className = 'char'; c.textContent = ch; w.appendChild(c); });
          frag.appendChild(w);
        }
      });
      node.replaceWith(frag);
    });
  }
  if (!C.editor) {
    $$('[data-split="chars"]').forEach(el => wrapTextNodes(el, 'char'));
    $$('[data-words]').forEach(el => wrapTextNodes(el, 'word'));
    $$('[data-split="lines"]').forEach(el => {
      el.innerHTML = el.innerHTML.split(/<br\s*\/?>/i).map(l => `<span class="line"><span>${l.trim()}</span></span>`).join('');
    });
  }

  /* ---------------------------------------------------------
     Smooth scroll
     --------------------------------------------------------- */
  let lenis = null;
  if (!reduced && motion.smooth && window.Lenis) {
    lenis = new window.Lenis({ duration: 1.25, easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)), smoothWheel: true });
    lenis.on('scroll', ScrollTrigger.update);
    gsap.ticker.add(t => lenis.raf(t * 1000));
    gsap.ticker.lagSmoothing(0);
    if ($('#preloader')) lenis.stop();
  }
  const lock = v => { if (lenis) v ? lenis.stop() : lenis.start(); };
  const scrollTo = target => {
    const el = typeof target === 'string' ? $(target) : target;
    if (!el) return false;
    lenis ? lenis.scrollTo(el, { offset: -70, duration: 1.6 }) : el.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth' });
    return true;
  };
  // in-page links ("#library" or "/#library" while on the homepage) glide instead of jumping
  document.addEventListener('click', e => {
    const a = e.target.closest('a[href*="#"]');
    if (!a || a.target === '_blank') return;
    const url = new URL(a.href, location.href);
    if (url.pathname !== location.pathname || !url.hash) return;
    if (url.hash.length < 2) { e.preventDefault(); return; }
    let target = null;
    try { target = $(url.hash); } catch (err) { return; }
    if (!target && url.hash !== '#top') return;
    e.preventDefault();
    body.classList.remove('menu-open', 'mega-open');
    lock(false);
    scrollTo(url.hash === '#top' ? body : target);
  });

  /* ---------------------------------------------------------
     Preloader + hero intro
     --------------------------------------------------------- */
  const heroIntro = ['.hero__eyebrow', '.hero__lede', '.hero__ctas', '.hero__rail'].filter(s => $(s));
  const heroChars = $$('.hero__title .char');
  if (!reduced && $('.hero')) {
    gsap.set(heroChars, { yPercent: 115 });
    gsap.set(heroIntro, { autoAlpha: 0, y: 24 });
  }
  function intro() {
    const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
    if (!$('.hero')) return tl;
    tl.to('.hero__video', { scale: 1, duration: 2.4, ease: 'power3.out' }, 0)
      .to(heroChars, { yPercent: 0, duration: 1.4, stagger: .028 }, .1)
      .to(heroIntro, { autoAlpha: 1, y: 0, duration: 1.2, stagger: .1 }, .5);
    return tl;
  }
  const store = {
    get(k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set(k, v) { try { localStorage.setItem(k, v); } catch (e) { /* private mode */ } },
    sget(k) { try { return sessionStorage.getItem(k); } catch (e) { return null; } },
    sset(k, v) { try { sessionStorage.setItem(k, v); } catch (e) { /* private mode */ } }
  };
  function done() { body.classList.remove('is-loading'); lock(false); ScrollTrigger.refresh(); }

  const pre = $('#preloader');
  if (pre && !reduced) {
    const ready = Promise.race([
      Promise.all([document.fonts ? document.fonts.ready : Promise.resolve(), new Promise(r => window.addEventListener('load', r, { once: true }))]),
      new Promise(r => setTimeout(r, 3500))
    ]);
    ready.then(() => {
      const seen = store.sget('aw-seen');
      const counter = { v: 0 };
      const countEl = $('.preloader__count b');
      const dur = seen ? .9 : 2.3;
      gsap.timeline({ onComplete() { pre.remove(); done(); } })
        .to('.preloader__word', { opacity: 1, duration: .8, ease: 'power2.out' }, 0)
        .to('.preloader__emblem-fill', { clipPath: 'inset(0% 0 0 0)', duration: dur, ease: 'power2.inOut' }, 0)
        .to(counter, { v: 100, duration: dur, ease: 'power2.inOut', onUpdate: () => { countEl.textContent = String(Math.round(counter.v)).padStart(3, '0'); } }, 0)
        .to('.preloader__inner', { autoAlpha: 0, y: -20, duration: .6, ease: 'power2.in' }, dur + .1)
        .to('.preloader__curtain--a', { yPercent: -100, duration: 1.2, ease: 'expo.inOut' }, dur + .45)
        .to('.preloader__curtain--b', { yPercent: 100, duration: 1.2, ease: 'expo.inOut' }, dur + .45)
        .add(intro(), dur + .85);
      store.sset('aw-seen', '1');
    });
  } else {
    if (pre) pre.remove();
    if (reduced) { gsap.set(heroChars, { yPercent: 0 }); gsap.set(heroIntro, { autoAlpha: 1, y: 0 }); done(); }
    else { done(); intro(); }
  }

  /* ---------------------------------------------------------
     Header, announcement, menus
     --------------------------------------------------------- */
  let lastY = 0;
  const progress = $('.scroll-progress span');
  if (!C.editor) {
    ScrollTrigger.create({
      start: 0, end: 'max',
      onUpdate(self) {
        const y = self.scroll();
        body.classList.toggle('is-scrolled', y > 40);
        if (!body.classList.contains('menu-open')) body.classList.toggle('header-hidden', y > lastY && y > 400);
        lastY = y;
        if (progress) gsap.set(progress, { scaleX: self.progress });
      }
    });
  }

  const msgs = $$('.announce__msg');
  if (msgs.length > 1 && !reduced) {
    let mi = 0;
    setInterval(() => {
      const cur = msgs[mi]; mi = (mi + 1) % msgs.length; const next = msgs[mi];
      cur.classList.remove('is-active'); cur.classList.add('is-out');
      next.classList.remove('is-out'); next.classList.add('is-active');
      setTimeout(() => cur.classList.remove('is-out'), 800);
    }, 4200);
  }

  const header = $('#header');
  const megaItem = $('.has-mega');
  if (header && megaItem && $('.mega')) {
    let megaTimer;
    const openMega = v => { clearTimeout(megaTimer); megaTimer = setTimeout(() => body.classList.toggle('mega-open', v), v ? 60 : 220); };
    megaItem.addEventListener('mouseenter', () => openMega(true));
    megaItem.addEventListener('focusin', () => openMega(true));
    header.addEventListener('mouseleave', () => openMega(false));
    $$('.nav__item:not(.has-mega)').forEach(n => n.addEventListener('mouseenter', () => openMega(false)));
  }
  const burger = $('.burger');
  if (burger) {
    burger.addEventListener('click', e => {
      const open = body.classList.toggle('menu-open');
      e.currentTarget.setAttribute('aria-expanded', open);
      $('.mmenu') && $('.mmenu').setAttribute('aria-hidden', !open);
      lock(open);
    });
    $$('.mmenu a').forEach(a => a.addEventListener('click', () => { body.classList.remove('menu-open'); lock(false); }));
  }

  /* ---------------------------------------------------------
     Search overlay
     --------------------------------------------------------- */
  // not ".search": WordPress puts that class on <body> on search result pages
  const search = $('.search-panel');
  const openSearch = v => {
    if (!search) return;
    body.classList.toggle('search-open', v); search.setAttribute('aria-hidden', !v); lock(v);
    if (v) setTimeout(() => $('input[type="search"]', search).focus(), 200);
  };
  $$('[data-open-search]').forEach(b => b.addEventListener('click', () => openSearch(true)));
  $$('[data-close-search]').forEach(b => b.addEventListener('click', () => openSearch(false)));
  // a click anywhere outside the search field and its results closes the panel
  if (search) search.addEventListener('click', e => { if (!e.target.closest('.search__form, .search__results')) openSearch(false); });

  /* ---------------------------------------------------------
     Cursor + magnetic + tilt
     --------------------------------------------------------- */
  const cur = $('.cursor');
  if (cur && !touch && !reduced) {
    const dot = $('.cursor__dot'), ring = $('.cursor__ring'), label = $('.cursor__label');
    const pos = { x: innerWidth / 2, y: innerHeight / 2 }, rp = { ...pos };
    const dx = gsap.quickSetter(dot, 'x', 'px'), dy = gsap.quickSetter(dot, 'y', 'px');
    const rx = gsap.quickSetter(ring, 'x', 'px'), ry = gsap.quickSetter(ring, 'y', 'px');
    window.addEventListener('pointermove', e => { pos.x = e.clientX; pos.y = e.clientY; dx(pos.x); dy(pos.y); cur.classList.remove('is-hidden'); });
    gsap.ticker.add(() => { rp.x += (pos.x - rp.x) * .16; rp.y += (pos.y - rp.y) * .16; rx(rp.x); ry(rp.y); });
    document.addEventListener('pointerover', e => {
      const lab = e.target.closest('[data-cursor]');
      const hov = e.target.closest('a, button, [data-hover], input, select, textarea');
      if (lab && !(hov && hov !== lab && lab.contains(hov) && hov.matches('button, .chip, a'))) {
        label.textContent = lab.dataset.cursor; cur.classList.add('is-label'); cur.classList.remove('is-hover');
      } else if (hov) { cur.classList.add('is-hover'); cur.classList.remove('is-label'); }
      else cur.classList.remove('is-hover', 'is-label');
    });
    document.addEventListener('pointerleave', () => cur.classList.add('is-hidden'));
    document.addEventListener('pointerenter', () => cur.classList.remove('is-hidden'));
  } else if (cur) cur.remove();

  if (!touch && !reduced) {
    $$('.magnetic').forEach(el => {
      const xTo = gsap.quickTo(el, 'x', { duration: .8, ease: 'elastic.out(1, .4)' });
      const yTo = gsap.quickTo(el, 'y', { duration: .8, ease: 'elastic.out(1, .4)' });
      el.addEventListener('pointermove', e => {
        const r = el.getBoundingClientRect();
        xTo((e.clientX - r.left - r.width / 2) * .32); yTo((e.clientY - r.top - r.height / 2) * .42);
      });
      el.addEventListener('pointerleave', () => { xTo(0); yTo(0); });
    });
    $$('[data-tilt]').forEach(el => {
      const rX = gsap.quickTo(el, 'rotationX', { duration: .9, ease: 'power3.out' });
      const rY = gsap.quickTo(el, 'rotationY', { duration: .9, ease: 'power3.out' });
      gsap.set(el, { transformPerspective: 900 });
      el.addEventListener('pointermove', e => {
        const r = el.getBoundingClientRect(), px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
        rY((px - .5) * 10); rX((.5 - py) * 10);
        el.style.setProperty('--gx', px * 100 + '%'); el.style.setProperty('--gy', py * 100 + '%');
      });
      el.addEventListener('pointerleave', () => { rX(0); rY(0); });
    });
  }

  /* ---------------------------------------------------------
     Hero: candle glow follows the pointer, frame folds on scroll
     --------------------------------------------------------- */
  $$('.hero').forEach(hero => {
    hero.addEventListener('pointermove', e => {
      const r = hero.getBoundingClientRect();
      hero.style.setProperty('--mx', ((e.clientX - r.left) / r.width) * 100 + '%');
      hero.style.setProperty('--my', ((e.clientY - r.top) / r.height) * 100 + '%');
    });
    if (!reduced) {
      gsap.timeline({ scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } })
        .fromTo($('.hero__media', hero), { clipPath: 'inset(0% 0% 0% 0% round 0px)' }, { clipPath: 'inset(7% 4% 14% 4% round 28px)', ease: 'none' }, 0)
        .to($('.hero__video', hero), { yPercent: 12, ease: 'none' }, 0)
        .to($('.hero__content', hero), { yPercent: -18, autoAlpha: .2, ease: 'none' }, 0);
    }
  });

  /* ---------------------------------------------------------
     Marquee driven by scroll velocity
     --------------------------------------------------------- */
  $$('[data-marquee]').forEach(track => {
    const mq = gsap.to(track, { xPercent: -50, duration: 38, ease: 'none', repeat: -1 });
    if (reduced) { mq.pause(); return; }
    const skew = gsap.quickTo(track, 'skewX', { duration: .6, ease: 'power3.out' });
    ScrollTrigger.create({
      trigger: track.closest('.marquee'), start: 'top bottom', end: 'bottom top',
      onToggle: self => (self.isActive ? mq.play() : mq.pause()), // no ticking while off screen
      onUpdate(self) {
        const v = gsap.utils.clamp(-1200, 1200, self.getVelocity());
        gsap.to(mq, { timeScale: 1 + Math.abs(v) / 250, duration: .3, overwrite: true, onComplete: () => gsap.to(mq, { timeScale: 1, duration: 1.2 }) });
        skew(v / -160);
      }
    });
  });

  /* ---------------------------------------------------------
     Manifesto: words light up as you read
     --------------------------------------------------------- */
  $$('.manifesto').forEach(sec => {
    if (reduced) { gsap.set($$('.word', sec), { opacity: 1 }); return; }
    gsap.to($$('.manifesto__text .word', sec), { opacity: 1, stagger: .5, ease: 'none', scrollTrigger: { trigger: $('.manifesto__text', sec), start: 'top 78%', end: 'bottom 45%', scrub: 1 } });
    $$('.float', sec).forEach(f => {
      const d = parseFloat(f.dataset.depth || '.4');
      gsap.fromTo(f, { y: 160 * d }, { y: -260 * d, rotation: `+=${d * 20}`, ease: 'none', scrollTrigger: { trigger: sec, start: 'top bottom', end: 'bottom top', scrub: true } });
    });
    const em = $('.manifesto__emblem', sec);
    if (em) gsap.from(em, { scale: .6, autoAlpha: 0, duration: 1.4, ease: 'expo.out', scrollTrigger: { trigger: em, start: 'top 85%' } });
  });

  /* ---------------------------------------------------------
     Ritual: pinned anatomy of a scent
     --------------------------------------------------------- */
  $$('.ritual').forEach(ritual => {
    const steps = $$('.ritual__step', ritual);
    if (!steps.length) return;
    const count = $('.ritual__count b', ritual);
    let shown = -1;
    const setStep = i => {
      if (i === shown) return; shown = i;
      steps.forEach((s, k) => { s.classList.toggle('is-active', k === i); s.classList.toggle('is-past', k < i); });
      ritual.style.setProperty('--hue', steps[i].dataset.hue);
      if (count) count.textContent = String(i + 1).padStart(2, '0');
    };
    setStep(0);
    mm.add('(min-width: 961px) and (prefers-reduced-motion: no-preference)', () => {
      if (reduced) return;
      const smoke = $('.smoke--ritual', ritual);
      const n = steps.length;
      const tl = gsap.timeline({
        scrollTrigger: {
          trigger: $('.ritual__pin', ritual), start: 'top top', end: '+=' + (n * 73) + '%', pin: true, scrub: 1, anticipatePin: 1,
          onUpdate(self) {
            setStep(Math.min(n - 1, Math.floor(self.progress * n)));
            gsap.set($('.ritual__bar i', ritual), { scaleX: self.progress });
            if (smoke && smoke._smoke) smoke._smoke.boost = .7 + self.progress * .9;
          }
        }
      });
      const ingr = $('.ritual__ingredients', ritual);
      if (ingr) tl.fromTo(ingr, { scale: .75, rotation: -20, autoAlpha: .2 }, { scale: 1.15, rotation: 25, autoAlpha: .55, ease: 'none' }, 0);
      tl.fromTo($('.ritual__candle', ritual), { y: 40 }, { y: -10, ease: 'none' }, 0)
        .fromTo($('.ritual__halo', ritual), { scale: .85 }, { scale: 1.1, ease: 'none' }, 0);
      gsap.from($$('.ritual__title, .ritual__copy .eyebrow', ritual), { y: 50, autoAlpha: 0, duration: 1.2, ease: 'expo.out', stagger: .1, scrollTrigger: { trigger: ritual, start: 'top 70%' } });
      return () => { ritual.style.removeProperty('--hue'); };
    });
  });

  /* Flag active scrolling so hover effects pause (see .is-scrolling in the stylesheet). */
  let scrollIdle;
  window.addEventListener('scroll', () => {
    if (!body.classList.contains('is-scrolling')) body.classList.add('is-scrolling');
    clearTimeout(scrollIdle);
    scrollIdle = setTimeout(() => body.classList.remove('is-scrolling'), 160);
  }, { passive: true });

  /* ---------------------------------------------------------
     Scent library: horizontal travel on desktop, swipe on touch
     --------------------------------------------------------- */
  $$('.library').forEach(lib => {
    const track = $('.library__track', lib);
    if (!track) return;
    // Decode the ingredient images up front: lazy-loading them inside the moving track made
    // Safari decode mid-scroll and stall the horizontal pin.
    $$('.scent__img', track).forEach(img => img.decode && img.decode().catch(() => {}));
    const cards = $$('.scent', track);
    const libCount = $('.library__count b', lib), libBar = $('.library__bar i', lib);
    // touch the DOM only when the number changes; rewriting it every frame forced a relayout per frame
    let libShown = 0;
    const setLibCount = n => { if (libCount && n !== libShown) { libShown = n; libCount.textContent = String(n).padStart(2, '0'); } };
    mm.add('(min-width: 961px) and (prefers-reduced-motion: no-preference)', () => {
      if (reduced) return;
      const dist = () => Math.max(0, track.scrollWidth - window.innerWidth);
      gsap.to(track, {
        x: () => -dist(), ease: 'none',
        scrollTrigger: {
          trigger: $('.library__pin', lib), start: 'top top', end: () => '+=' + dist(), pin: true, scrub: 1, anticipatePin: 1, invalidateOnRefresh: true,
          onUpdate(self) {
            if (libBar) libBar.style.transform = `scaleX(${self.progress})`;
            setLibCount(Math.min(cards.length, 1 + Math.round(self.progress * (cards.length - 1))));
          }
        }
      });
      // Animate the card contents, not the cards: .scent has a CSS transform transition (hover lift)
      // that fights GSAP and left the first cards stuck mid-offset, overlapping their neighbours.
      const enter = cards.slice(0, 5);
      gsap.from(enter, { autoAlpha: 0, duration: 1.2, stagger: .08, ease: 'power2.out', clearProps: 'opacity,visibility', scrollTrigger: { trigger: lib, start: 'top 60%' } });
      gsap.from(enter.map(c => c.children), { x: 120, duration: 1.4, stagger: .08, ease: 'expo.out', clearProps: 'transform', scrollTrigger: { trigger: lib, start: 'top 60%' } });
    });
    mm.add('(max-width: 960px)', () => {
      const vp = $('.library__viewport', lib);
      const onScroll = () => {
        const p = vp.scrollLeft / Math.max(1, vp.scrollWidth - vp.clientWidth);
        if (libBar) libBar.style.transform = `scaleX(${p})`;
        setLibCount(1 + Math.round(p * (cards.length - 1)));
      };
      vp.addEventListener('scroll', onScroll, { passive: true });
      return () => vp.removeEventListener('scroll', onScroll);
    });
  });

  /* Triggers below are created after the pinned sections so their positions include pin spacing. */
  /* ---------------------------------------------------------
     Headings: line reveals, staggered group reveals
     --------------------------------------------------------- */
  if (!reduced) {
    $$('[data-split="lines"]').forEach(el => {
      if (el.closest('.hero')) return;
      gsap.from($$('.line > span', el), { yPercent: 110, rotate: 2, duration: 1.3, stagger: .1, ease: 'expo.out', scrollTrigger: { trigger: el, start: 'top 86%' } });
    });
    ['.cats__grid .cat', '.best__grid .prod', '.journal__grid .post', '.repel__list li', '.stats .stat', '.footer__cols > div'].forEach(sel => {
      if (!$(sel)) return;
      ScrollTrigger.batch(sel, { start: 'top 90%', once: true, onEnter: els => gsap.from(els, { y: 70, autoAlpha: 0, duration: 1.3, stagger: .09, ease: 'expo.out' }) });
    });
  }

  /* ---------------------------------------------------------
     Feature: arch opens, video plays only while visible
     --------------------------------------------------------- */
  $$('.repel').forEach(sec => {
    const rv = $('.repel__video', sec);
    if (rv && rv.tagName === 'VIDEO') {
      new IntersectionObserver(([e]) => {
        if (e.isIntersecting) {
          if (!rv.dataset.loaded) { $$('source', rv).forEach(s => { s.src = s.dataset.src; }); rv.load(); rv.dataset.loaded = 1; }
          if (!reduced) rv.play().catch(() => {});
        } else rv.pause();
      }, { rootMargin: '200px' }).observe(rv);
    }
    if (reduced) return;
    const media = $('.repel__media', sec);
    gsap.to($('.repel__arch', sec), { clipPath: 'inset(0% 0% 0% 0% round 999px 999px 26px 26px)', ease: 'none', scrollTrigger: { trigger: media, start: 'top 85%', end: 'center 45%', scrub: 1 } });
    if (rv) gsap.fromTo(rv, { scale: 1.3 }, { scale: 1.05, ease: 'none', scrollTrigger: { trigger: media, start: 'top bottom', end: 'bottom top', scrub: true } });
    const leaf = $('.repel__leaf', sec);
    if (leaf) gsap.fromTo(leaf, { y: 120, rotation: 12 }, { y: -80, rotation: -4, ease: 'none', scrollTrigger: { trigger: sec, start: 'top bottom', end: 'bottom top', scrub: true } });
  });

  /* ---------------------------------------------------------
     Story: mask reveal, parallax, counters
     --------------------------------------------------------- */
  $$('.story__figure').forEach(fig => {
    const img = $('img', fig);
    if (img) { const shown = () => img.classList.add('is-loaded'); img.complete && img.naturalWidth ? shown() : img.addEventListener('load', shown); }
    gsap.to(fig, { clipPath: 'inset(0% 0% round 22px)', duration: reduced ? 0 : 1.1, ease: 'power3.out', scrollTrigger: { trigger: fig, start: 'top 95%' } });
    if (!reduced && img) gsap.fromTo(img, { yPercent: -8 }, { yPercent: 8, ease: 'none', scrollTrigger: { trigger: fig, start: 'top bottom', end: 'bottom top', scrub: true } });
  });
  $$('[data-count]').forEach(el => {
    if (reduced) return;
    const to = parseFloat(el.dataset.count), dec = parseInt(el.dataset.decimals || '0', 10), o = { v: parseFloat(el.dataset.from || '0') };
    el.textContent = o.v.toFixed(dec);
    ScrollTrigger.create({ trigger: el, start: 'top 88%', once: true, onEnter: () => gsap.to(o, { v: to, duration: 2.2, ease: 'power3.out', onUpdate: () => { el.textContent = o.v.toFixed(dec); } }) });
  });

  /* ---------------------------------------------------------
     Reviews slider (drag, arrows, auto-advance)
     --------------------------------------------------------- */
  $$('.reviews').forEach(sec => {
    const rTrack = $('.reviews__track', sec), rCards = $$('.review', sec), rCount = $('.reviews__count b', sec);
    if (!rTrack || rCards.length < 2) return;
    let ri = 0, rTimer;
    const step = () => rCards[0].getBoundingClientRect().width + (parseFloat(getComputedStyle(rTrack).columnGap) || 30);
    const goRev = i => {
      ri = (i + rCards.length) % rCards.length;
      gsap.to(rTrack, { x: -ri * step(), duration: reduced ? 0 : 1.2, ease: 'expo.out' });
      rCards.forEach((c, k) => c.classList.toggle('is-active', k === ri));
      if (rCount) rCount.textContent = String(ri + 1).padStart(2, '0');
      clearTimeout(rTimer); if (!reduced) rTimer = setTimeout(() => goRev(ri + 1), 7000);
    };
    $$('[data-rev]', sec).forEach(b => b.addEventListener('click', () => goRev(ri + parseInt(b.dataset.rev, 10))));
    let x0 = null, startX = 0;
    const stage = $('.reviews__stage', sec);
    stage.addEventListener('pointerdown', e => { x0 = e.clientX; startX = gsap.getProperty(rTrack, 'x'); stage.setPointerCapture(e.pointerId); });
    stage.addEventListener('pointermove', e => { if (x0 === null) return; gsap.set(rTrack, { x: startX + (e.clientX - x0) }); });
    const end = e => { if (x0 === null) return; const d = e.clientX - x0; x0 = null; goRev(ri + (d < -60 ? 1 : d > 60 ? -1 : 0)); };
    stage.addEventListener('pointerup', end); stage.addEventListener('pointercancel', end);
    ScrollTrigger.create({ trigger: sec, start: 'top 70%', once: true, onEnter: () => goRev(0) });
    window.addEventListener('resize', () => gsap.set(rTrack, { x: -ri * step() }));
  });

  /* ---------------------------------------------------------
     Footer wordmark: sized to the content width, rises letter by letter
     --------------------------------------------------------- */
  $$('.footer__mark .display').forEach(mark => {
    const fit = () => {
      mark.style.fontSize = '';
      const avail = mark.parentElement.clientWidth, w = mark.offsetWidth;
      if (w) mark.style.fontSize = parseFloat(getComputedStyle(mark).fontSize) * avail / w + 'px';
    };
    fit();
    document.fonts && document.fonts.ready.then(fit);
    window.addEventListener('resize', fit);
    if (!reduced) gsap.from($$('.char', mark), { yPercent: 100, rotate: 6, autoAlpha: 0, duration: 1.4, stagger: .045, ease: 'expo.out', scrollTrigger: { trigger: mark, start: 'top 92%' } });
  });

  /* ---------------------------------------------------------
     Requests to the shop (WooCommerce AJAX endpoints)
     --------------------------------------------------------- */
  const post = (endpoint, data) => {
    const fd = new FormData();
    fd.append('nonce', C.nonce || '');
    Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
    return fetch((C.ajax || '').replace('%%endpoint%%', endpoint), { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(r => r.json());
  };

  /* ---------------------------------------------------------
     Live search: products and scents as you type
     --------------------------------------------------------- */
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const liveInput = search && $('input[type="search"]', search);
  const liveOut = search && $('.search__results', search);
  if (liveInput && liveOut) {
    const t = C.i18n || {};
    let timer, last = '', seq = 0;
    const render = (d, q) => {
      if (!d.products.length && !d.scents.length) {
        liveOut.innerHTML = `<p class="search__empty">${esc((t.none || 'Nothing matches “%s”.').replace('%s', q))}</p>`;
        return;
      }
      let html = '';
      if (d.scents.length) {
        html += `<p class="mono search__label">${esc(t.scents || 'Scents')}</p><div class="search__scents">` + d.scents.map(s =>
          `<a class="search__scent" href="${esc(s.url)}" style="--aura:${esc(s.aura)}">${s.img ? `<img src="${esc(s.img)}" alt="" loading="lazy">` : '<i></i>'}<span>${esc(s.name)}<small>${esc(s.family || '')}</small></span></a>`
        ).join('') + '</div>';
      }
      if (d.products.length) {
        html += `<p class="mono search__label">${esc(t.products || 'Products')}</p><ul class="search__list">` + d.products.map(p =>
          `<li><a class="search__item" href="${esc(p.url)}"><img src="${esc(p.img)}" alt="" loading="lazy"><span class="search__name">${esc(p.name)}<small>${esc(p.meta)}</small></span><span class="search__price">${esc(p.price)}</span></a></li>`
        ).join('') + '</ul>';
      }
      if (d.total > d.products.length) html += `<a class="link-arrow search__all" href="${esc(d.all)}">${esc((t.all || 'See all %d results').replace('%d', d.total))} <span>→</span></a>`;
      liveOut.innerHTML = html;
    };
    liveInput.addEventListener('input', () => {
      const q = liveInput.value.trim();
      clearTimeout(timer);
      if (q === last) return;
      last = q;
      if (q.length < 2) { liveOut.innerHTML = ''; search.classList.remove('has-results', 'is-loading'); return; }
      search.classList.add('is-loading');
      timer = setTimeout(() => {
        const id = ++seq;
        post('afz_search', { q }).then(r => {
          if (id !== seq || !r || !r.success) return; // a newer keystroke already asked again
          render(r.data, q);
          search.classList.add('has-results');
        }).catch(() => {}).finally(() => { if (id === seq) search.classList.remove('is-loading'); });
      }, 220);
    });
    // arrow down from the field steps into the results
    liveInput.addEventListener('keydown', e => {
      if (e.key === 'ArrowDown') { const a = $('a', liveOut); if (a) { e.preventDefault(); a.focus(); } }
    });
    liveOut.addEventListener('keydown', e => {
      if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
      const links = $$('a', liveOut), i = links.indexOf(document.activeElement);
      if (i < 0) return;
      e.preventDefault();
      const n = i + (e.key === 'ArrowDown' ? 1 : -1);
      (n < 0 ? liveInput : links[Math.min(n, links.length - 1)]).focus();
    });
  }

  /* ---------------------------------------------------------
     Basket: fly-to-basket, side drawer, free-delivery progress
     --------------------------------------------------------- */
  const cartEl = $('.cart-drawer'), list = $('.cart__items'), basket = $('.basket-btn');
  const FREE = Number(C.freeShip) || 0;
  let justAdded = null; // only the newly added line slides in; +/- re-renders stay still
  function renderCart(state) {
    if (!cartEl || !state) return;
    list.innerHTML = state.items.map(it => `<li class="cart-item${it.id === justAdded ? ' is-new' : ''}">
        <a class="cart-item__img" href="${esc(it.url)}"><img src="${esc(it.img)}" alt=""></a>
        <span><a class="cart-item__name" href="${esc(it.url)}">${esc(it.name)}</a>
          <span class="qty"><button type="button" data-qty="-1" data-key="${esc(it.key)}" aria-label="Decrease quantity">−</button><output>${it.qty}</output><button type="button" data-qty="1" data-key="${esc(it.key)}" aria-label="Increase quantity"${it.max > 0 && it.qty >= it.max ? ' disabled' : ''}>+</button></span></span>
        <span class="cart-item__price">${money(it.total)}</span></li>`).join('');
    justAdded = null;
    list.dataset.state = JSON.stringify(state.items.map(i => [i.key, i.qty]));
    cartEl.classList.toggle('has-items', state.count > 0);
    $$('.basket-btn').forEach(b => b.classList.toggle('has-items', state.count > 0));
    $$('.basket-btn__count').forEach(c => { c.textContent = state.count; });
    $('.cart__sum', cartEl).textContent = money(state.subtotal);
    // the drawer and the basket page each have a free-delivery meter
    if (FREE) $$('.cart__ship').forEach(ship => {
      const left = Math.max(0, FREE - state.subtotal);
      $('.cart__ship-msg', ship).innerHTML = left > 0 ? (C.i18n.spend || '').replace('%s', money(left)) : C.i18n.unlocked;
      $('.cart__ship-bar i', ship).style.transform = `scaleX(${Math.min(1, state.subtotal / FREE)})`;
      const bar = $('.cart__ship-bar', ship); if (bar) bar.setAttribute('aria-valuenow', Math.min(FREE, state.subtotal).toFixed(2));
      ship.classList.toggle('is-free', left === 0 && state.subtotal > 0);
    });
  }
  const openCart = v => {
    if (!cartEl) return;
    body.classList.toggle('cart-open', v); cartEl.setAttribute('aria-hidden', !v); lock(v);
    if (v) setTimeout(() => { const b = $('.cart__head .round-btn'); b && b.focus({ preventScroll: true }); }, 300);
  };
  if (cartEl) {
    try { renderCart(JSON.parse($('#afz-cart-state').textContent)); } catch (e) { /* keep server markup */ }
    // pages served from cache may show an old basket: refresh once from the server
    post('afz_cart', { op: 'get' }).then(r => r && r.success && renderCart(r.data)).catch(() => {});
    $$('[data-open-cart]').forEach(b => b.addEventListener('click', () => openCart(true)));
    cartEl.addEventListener('click', e => { if (e.target.closest('[data-close-cart]')) openCart(false); });
    list.addEventListener('click', e => {
      const b = e.target.closest('[data-qty]');
      if (!b || b.disabled) return;
      const out = b.parentElement.querySelector('output');
      const q = Math.max(0, parseInt(out.textContent, 10) + parseInt(b.dataset.qty, 10));
      out.textContent = q; b.closest('.cart-item').style.opacity = .55;
      post('afz_cart', { op: 'set', key: b.dataset.key, qty: q }).then(r => { if (r && r.success) { renderCart(r.data); syncShopPages(); } });
    });
  }
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    openCart(false); openSearch(false); closePopup();
    body.classList.remove('menu-open', 'mega-open'); lock(false);
  });
  document.addEventListener('click', e => {
    const btn = e.target.closest('[data-add]');
    if (!btn || !cartEl) return;
    e.preventDefault(); e.stopPropagation();
    if (btn.classList.contains('is-busy')) return;
    btn.classList.add('is-busy');
    const card = btn.closest('.prod');
    const from = (card && $('.prod__pack', card)) || ($('.repel__leaf', btn.closest('.repel') || document)) || btn;
    const id = parseInt(btn.dataset.add, 10);
    const request = post('afz_cart', { op: 'add', id, qty: 1 });
    const finish = r => {
      btn.classList.remove('is-busy');
      if (!r || !r.success) { alert((r && r.data && r.data.message) || C.i18n.error); return; }
      if (r.data.redirect) { location.href = r.data.redirect; return; }
      if (r.data.error) { alert(r.data.error); }
      renderCart(r.data); syncShopPages();
      if (basket) { basket.classList.remove('bump'); void basket.offsetWidth; basket.classList.add('bump'); }
      setTimeout(() => openCart(true), 250);
    };
    justAdded = id;
    if (reduced || !basket || !from.getBoundingClientRect) { request.then(finish).catch(() => finish(null)); return; }
    const a = from.getBoundingClientRect(), b = basket.getBoundingClientRect();
    const fly = document.createElement('img');
    fly.src = from.currentSrc || from.src || ''; fly.className = 'fly'; fly.alt = '';
    $('.afz-global').appendChild(fly);
    gsap.set(fly, { left: a.left + a.width / 2 - 35, top: a.top + a.height / 2 - 45 });
    const flight = new Promise(res => {
      gsap.timeline({ onComplete: () => { fly.remove(); res(); } })
        .to(fly, { left: b.left + b.width / 2 - 35, duration: .9, ease: 'power2.inOut' }, 0)
        .to(fly, { top: b.top - 20, duration: .9, ease: 'back.in(1.4)' }, 0)
        .to(fly, { scale: .25, rotation: 25, autoAlpha: .3, duration: .9, ease: 'power2.in' }, 0);
    });
    Promise.all([request, flight]).then(([r]) => finish(r)).catch(() => finish(null));
  });

  /* ---------------------------------------------------------
     Shop pages: keep WooCommerce's basket/checkout and the drawer in step
     --------------------------------------------------------- */
  function syncShopPages() {
    if (!window.jQuery) return;
    // wc_update_cart re-reads the basket from the server (submitting the form would resend its old quantities)
    if (body.classList.contains('woocommerce-cart')) window.jQuery(document.body).trigger('wc_update_cart');
    if (body.classList.contains('woocommerce-checkout')) window.jQuery(body).trigger('update_checkout');
  }
  if (window.jQuery && cartEl) {
    window.jQuery(body).on('updated_wc_div updated_cart_totals removed_from_cart updated_checkout', () => {
      post('afz_cart', { op: 'get' }).then(r => r && r.success && renderCart(r.data));
    });
  }

  // quantity fields get − / + buttons (product page and basket page)
  function steppers(scope) {
    $$('.quantity', scope).forEach(q => {
      const input = $('input.qty', q);
      if (!input || input.type === 'hidden' || q.querySelector('.afz-step')) return;
      const mk = (d, label) => { const b = document.createElement('button'); b.type = 'button'; b.className = 'afz-step'; b.textContent = d < 0 ? '−' : '+'; b.setAttribute('aria-label', label); b.dataset.d = d; return b; };
      q.prepend(mk(-1, 'Decrease quantity')); q.append(mk(1, 'Increase quantity'));
      q.addEventListener('click', e => {
        const b = e.target.closest('.afz-step'); if (!b) return;
        const min = parseFloat(input.min || '0') || 0, max = parseFloat(input.max || '') || Infinity, step = parseFloat(input.step || '1') || 1;
        input.value = Math.min(max, Math.max(min, (parseFloat(input.value) || 0) + step * parseFloat(b.dataset.d)));
        input.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.jQuery) window.jQuery(input).trigger('change');
      });
    });
  }
  steppers(document);
  if (window.jQuery) window.jQuery(body).on('updated_wc_div', () => steppers(document));

  // product gallery: thumbnails switch the large image
  $$('[data-afz-gallery]').forEach(g => {
    const shots = $$('.pdp__shot', g), thumbs = $$('.pdp__thumb', g);
    thumbs.forEach(t => t.addEventListener('click', () => {
      const i = +t.dataset.to;
      shots.forEach((s, k) => s.classList.toggle('is-active', k === i));
      thumbs.forEach((x, k) => { x.classList.toggle('is-active', k === i); x.setAttribute('aria-selected', k === i); });
    }));
  });

  // simple products add to the basket without leaving the page
  $$('.pdp[data-type="simple"] form.cart').forEach(form => {
    form.addEventListener('submit', e => {
      const btn = $('[name="add-to-cart"]', form);
      if (!btn || !cartEl) return;
      e.preventDefault();
      btn.classList.add('is-busy');
      const qty = parseInt(($('input.qty', form) || {}).value || '1', 10) || 1;
      justAdded = parseInt(btn.value, 10);
      post('afz_cart', { op: 'add', id: btn.value, qty }).then(r => {
        btn.classList.remove('is-busy');
        if (!r || !r.success) { form.submit(); return; }
        if (r.data.error) { alert(r.data.error); }
        renderCart(r.data);
        if (basket) { basket.classList.remove('bump'); void basket.offsetWidth; basket.classList.add('bump'); }
        openCart(true);
      }).catch(() => form.submit());
    });
  });

  // "Shop by scent" select jumps to that scent's page
  $$('[data-afz-jump]').forEach(sel => sel.addEventListener('change', () => { if (sel.value) location.href = sel.value; }));
  // sorting: WooCommerce submits the form on change
  $$('.woocommerce-ordering select').forEach(sel => sel.addEventListener('change', () => sel.form && sel.form.submit()));

  /* ---------------------------------------------------------
     Styled dropdowns. The native select stays in the form and keeps its
     change handlers; a combobox button and option list draw it on brand.
     --------------------------------------------------------- */
  let openDrop = null;
  const closeDrop = focus => {
    if (!openDrop) return;
    const d = openDrop; openDrop = null;
    d.wrap.classList.remove('is-open'); d.btn.setAttribute('aria-expanded', 'false'); d.btn.removeAttribute('aria-activedescendant');
    if (focus) d.btn.focus();
  };
  document.addEventListener('click', e => { if (openDrop && !openDrop.wrap.contains(e.target)) closeDrop(); });
  $$('.shop-bar__select, .woocommerce-ordering select, .contact__form select').forEach((sel, n) => {
    const id = sel.id || 'afz-select-' + n;
    const wrap = document.createElement('div');
    wrap.className = 'afz-select afz-select--' + (sel.closest('.contact__form') ? 'field' : 'pill');
    const btn = document.createElement('button');
    btn.type = 'button'; btn.id = id + '-btn'; btn.className = 'afz-select__btn';
    btn.setAttribute('role', 'combobox'); btn.setAttribute('aria-haspopup', 'listbox'); btn.setAttribute('aria-expanded', 'false');
    const value = document.createElement('span');
    value.className = 'afz-select__value'; value.id = id + '-value';
    btn.appendChild(value);
    const list = document.createElement('ul');
    list.className = 'afz-select__list'; list.id = id + '-list'; list.setAttribute('role', 'listbox'); list.setAttribute('data-lenis-prevent', '');
    btn.setAttribute('aria-controls', list.id);
    // the visible label (or the select's aria-label) names both the button and the list
    const label = sel.id && document.querySelector(`label[for="${sel.id}"]`);
    if (label) {
      label.id = label.id || id + '-label'; label.htmlFor = btn.id;
      btn.setAttribute('aria-labelledby', label.id + ' ' + value.id); list.setAttribute('aria-labelledby', label.id);
    } else if (sel.getAttribute('aria-label')) {
      const sr = document.createElement('span'); sr.className = 'sr-only'; sr.textContent = sel.getAttribute('aria-label') + ': ';
      btn.prepend(sr); list.setAttribute('aria-label', sel.getAttribute('aria-label'));
    }
    const items = [...sel.options].map((o, i) => {
      const li = document.createElement('li');
      li.id = id + '-opt-' + i; li.className = 'afz-select__opt'; li.setAttribute('role', 'option'); li.dataset.i = i;
      li.textContent = o.textContent.trim();
      if (o.dataset.aura) { li.classList.add('has-aura'); li.style.setProperty('--aura', o.dataset.aura); }
      list.appendChild(li);
      return li;
    });
    sel.parentNode.insertBefore(wrap, sel);
    wrap.append(sel, btn, list);
    sel.classList.add('afz-select__native'); sel.tabIndex = -1; sel.setAttribute('aria-hidden', 'true');

    let active = 0, typed = '', typedAt = 0, skipClick = 0;
    const sync = () => {
      const o = sel.options[sel.selectedIndex];
      value.textContent = o ? o.textContent.trim() : '';
      items.forEach((li, i) => li.setAttribute('aria-selected', i === sel.selectedIndex ? 'true' : 'false'));
    };
    const highlight = i => {
      active = Math.max(0, Math.min(items.length - 1, i));
      items.forEach((li, k) => li.classList.toggle('is-active', k === active));
      btn.setAttribute('aria-activedescendant', items[active].id);
      const li = items[active]; // keep it in view without scrolling the page
      if (li.offsetTop < list.scrollTop) list.scrollTop = li.offsetTop - 8;
      else if (li.offsetTop + li.offsetHeight > list.scrollTop + list.clientHeight) list.scrollTop = li.offsetTop + li.offsetHeight - list.clientHeight + 8;
    };
    const open = () => {
      if (openDrop) closeDrop();
      openDrop = { wrap, btn };
      // open upwards or right-aligned when the list would run off screen
      const r = btn.getBoundingClientRect();
      wrap.classList.toggle('is-up', r.bottom + Math.min(340, items.length * 44 + 16) > window.innerHeight - 12 && r.top > window.innerHeight / 2);
      wrap.classList.toggle('is-right', r.left + Math.max(r.width, 240) > document.documentElement.clientWidth - 12);
      wrap.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true');
      list.scrollTop = 0;
      highlight(Math.max(0, sel.selectedIndex));
    };
    const choose = i => {
      if (i !== sel.selectedIndex) {
        sel.selectedIndex = i; sync();
        sel.dispatchEvent(new Event('change', { bubbles: true }));
      }
      closeDrop(true);
    };
    btn.addEventListener('click', () => {
      if (Date.now() < skipClick) return; // the keyup "click" right after choosing with Space
      wrap.classList.contains('is-open') ? closeDrop() : open();
    });
    list.addEventListener('click', e => { const li = e.target.closest('[role="option"]'); if (li) choose(+li.dataset.i); });
    list.addEventListener('mousemove', e => { const li = e.target.closest('[role="option"]'); if (li && +li.dataset.i !== active) highlight(+li.dataset.i); });
    btn.addEventListener('keydown', e => {
      const isOpen = wrap.classList.contains('is-open');
      const k = e.key;
      if (['ArrowDown', 'ArrowUp', 'Home', 'End', 'PageDown', 'PageUp'].includes(k)) {
        e.preventDefault();
        if (!isOpen) { open(); return; }
        highlight(k === 'Home' ? 0 : k === 'End' ? items.length - 1 : active + ({ ArrowDown: 1, ArrowUp: -1, PageDown: 6, PageUp: -6 }[k]));
      } else if ((k === 'Enter' || k === ' ') && isOpen) {
        e.preventDefault(); skipClick = Date.now() + 400; choose(active);
      } else if (k === 'Escape' && isOpen) {
        e.preventDefault(); closeDrop(true);
      } else if (k === 'Tab' && isOpen) {
        closeDrop();
      } else if (k.length === 1 && /\S/.test(k) && !e.ctrlKey && !e.metaKey && !e.altKey) {
        // type-ahead: "h" jumps to Heaven
        typed = (Date.now() - typedAt > 700 ? '' : typed) + k.toLowerCase(); typedAt = Date.now();
        const i = items.findIndex(li => li.textContent.toLowerCase().startsWith(typed));
        if (i > -1) { if (!isOpen) open(); highlight(i); }
      }
    });
    sel.addEventListener('change', sync);
    window.addEventListener('pageshow', sync); // back button restores the old choice
    sync();
  });

  /* ---------------------------------------------------------
     Newsletter forms (section + popup)
     --------------------------------------------------------- */
  $$('[data-afz-subscribe]').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const email = form.querySelector('input[type="email"]');
      if (!email.value || !email.checkValidity()) { email.focus(); form.classList.add('is-invalid'); return; }
      form.classList.remove('is-invalid'); form.classList.add('is-busy');
      post('afz_subscribe', { email: email.value, source: form.dataset.source || 'site', website: form.querySelector('[name="website"]').value })
        .then(r => {
          form.classList.remove('is-busy');
          if (r && r.success) { form.classList.add('is-done'); store.set('afz-subscribed', '1'); }
          else alert((r && r.data && r.data.message) || C.i18n.error);
        })
        .catch(() => { form.classList.remove('is-busy'); alert(C.i18n.error); });
    });
  });

  /* ---------------------------------------------------------
     Contact form
     --------------------------------------------------------- */
  $$('[data-afz-contact]').forEach(form => {
    const note = $('.form-note', form);
    form.addEventListener('submit', e => {
      e.preventDefault();
      const bad = $$('[required]', form).find(f => !f.value.trim() || !f.checkValidity());
      if (bad) { bad.focus(); note.className = 'form-note is-error'; note.textContent = 'Please fill in your name, a valid email and a message.'; return; }
      const data = {}; new FormData(form).forEach((v, k) => { data[k] = v; });
      form.classList.add('is-busy'); note.className = 'form-note'; note.textContent = '';
      post('afz_contact', data).then(r => {
        form.classList.remove('is-busy');
        if (r && r.success) { form.reset(); note.className = 'form-note is-ok'; note.textContent = r.data.message; }
        else { note.className = 'form-note is-error'; note.textContent = (r && r.data && r.data.message) || C.i18n.error; }
      }).catch(() => { form.classList.remove('is-busy'); note.className = 'form-note is-error'; note.textContent = C.i18n.error; });
    });
  });

  /* ---------------------------------------------------------
     Welcome popup: once, after a delay, never at checkout
     --------------------------------------------------------- */
  const popup = $('.popup');
  function closePopup() {
    if (!popup || !body.classList.contains('popup-open')) return;
    body.classList.remove('popup-open'); popup.setAttribute('aria-hidden', 'true'); lock(false);
  }
  if (popup && C.popup && C.popup.on) {
    const last = parseInt(store.get('afz-popup') || '0', 10);
    const due = !store.get('afz-subscribed') && Date.now() - last > 30 * 864e5;
    const busy = () => ['cart-open', 'menu-open', 'search-open'].some(c => body.classList.contains(c)) || ($('.cookies.is-open') !== null);
    const show = () => {
      if (busy()) { setTimeout(show, 4000); return; } // wait for the cookie choice and any open drawer
      body.classList.add('popup-open'); popup.setAttribute('aria-hidden', 'false'); lock(true);
      store.set('afz-popup', String(Date.now()));
    };
    if (due) setTimeout(show, Math.max(3, C.popup.delay || 12) * 1000);
    popup.addEventListener('click', e => { if (e.target.closest('[data-close-popup]')) closePopup(); });
  }

  /* ---------------------------------------------------------
     Cookie consent
     --------------------------------------------------------- */
  const cookies = $('.cookies');
  if (cookies && C.cookies) {
    const has = document.cookie.split('; ').some(c => c.indexOf('afz_consent=') === 0);
    if (!has) { cookies.hidden = false; requestAnimationFrame(() => setTimeout(() => cookies.classList.add('is-open'), 1200)); }
    cookies.addEventListener('click', e => {
      const b = e.target.closest('[data-consent]');
      if (!b) return;
      document.cookie = 'afz_consent=' + b.dataset.consent + '; max-age=' + 365 * 864e2 + '; path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
      cookies.classList.remove('is-open');
      setTimeout(() => { cookies.hidden = true; }, 900);
      document.dispatchEvent(new CustomEvent('afz:consent', { detail: b.dataset.consent }));
    });
  }

  /* ---------------------------------------------------------
     Smoke + final refresh
     --------------------------------------------------------- */
  window.AWSmoke && window.AWSmoke.init();

  /* Pause the looping CSS animations (flame flicker, halo spin, pulses) in sections that are off screen. */
  const offscreen = new IntersectionObserver(entries => entries.forEach(e => e.target.classList.toggle('is-offscreen', !e.isIntersecting)), { rootMargin: '200px 0px' });
  $$('.hero, .ritual, .news').forEach(s => offscreen.observe(s));
  window.addEventListener('load', () => ScrollTrigger.refresh());
})();
