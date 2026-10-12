/* Black Lab theme interactions. All content is visible without JavaScript; motion respects reduced-motion. */
(() => {
  const d = document;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const header = d.querySelector('[data-header]');
  const adminBar = d.getElementById('wpadminbar');

  // Header: solid once scrolled; sits under the WordPress admin bar while that bar is visible.
  let ticking = false;
  const frame = () => {
    ticking = false;
    if (!header) return;
    header.classList.toggle('scrolled', window.scrollY > 40);
    header.style.top = adminBar ? Math.max(0, adminBar.getBoundingClientRect().bottom) + 'px' : '';
  };
  const onScroll = () => { if (!ticking) { ticking = true; window.requestAnimationFrame(frame); } };
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', onScroll, { passive: true });
  frame();

  // Hero video loop: still for visitors who asked for reduced motion.
  d.querySelectorAll('.blab-hero-bg video').forEach((v) => {
    if (reduce) { v.removeAttribute('autoplay'); v.pause(); return; }
    v.muted = true;
    v.play().catch(() => {});
  });

  // Hero title: reveal line by line (lines are the <br> breaks in the heading).
  d.querySelectorAll('.blab-page-hero .blab-title').forEach((title) => {
    const lines = title.innerHTML.split(/<br\s*\/?>/i).map((s) => s.trim()).filter(Boolean);
    title.innerHTML = lines.map((line, i) => '<span class="line" style="--i:' + i + '"><span>' + line + '</span></span>').join('');
  });

  // Menu: phone panel, tap-to-open sub menus (a toggle button is added to every parent item), Escape closes.
  const toggle = d.querySelector('.menu-toggle');
  const nav = d.getElementById('navigation');
  const closeMenu = () => { d.body.classList.remove('menu-open'); if (toggle) toggle.setAttribute('aria-expanded', 'false'); };
  if (toggle) toggle.addEventListener('click', () => {
    if (nav && header) nav.style.top = Math.max(0, header.getBoundingClientRect().bottom) + 'px';
    const open = d.body.classList.toggle('menu-open');
    toggle.setAttribute('aria-expanded', String(open));
  });
  d.querySelectorAll('.menu-item-has-children').forEach((li) => {
    // Only items whose sub menu is actually shown (WordPress also marks items whose deeper levels are hidden).
    if (!li.querySelector(':scope > .sub-menu') || li.closest('.sub-menu')) return;
    const button = d.createElement('button');
    button.type = 'button';
    button.className = 'sub-toggle';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', (li.querySelector('a') || {}).textContent + ' submenu');
    li.querySelector('a').after(button);
    button.addEventListener('click', () => button.setAttribute('aria-expanded', String(li.classList.toggle('open'))));
  });
  d.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    closeMenu();
    d.querySelectorAll('.menu-item-has-children.open').forEach((li) => li.classList.remove('open'));
    if (d.activeElement && d.activeElement.closest('.menu-item-has-children')) d.activeElement.blur();
  });
  if (nav) nav.addEventListener('click', (e) => { if (e.target.closest('a')) closeMenu(); });

  // Client logos: an endless strip on larger screens; two neat rows on phones; static for reduced motion.
  d.querySelectorAll('.blab-logos').forEach((box) => {
    if (reduce || box.classList.contains('marquee') || !window.matchMedia('(min-width: 901px)').matches) return;
    const track = d.createElement('div');
    track.className = 'track';
    [...box.children].forEach((child) => track.appendChild(child));
    [...track.children].forEach((child) => { const copy = child.cloneNode(true); copy.setAttribute('aria-hidden', 'true'); copy.querySelectorAll('img').forEach((i) => { i.alt = ''; }); copy.querySelectorAll('a').forEach((a) => a.setAttribute('tabindex', '-1')); track.appendChild(copy); });
    box.appendChild(track);
    box.classList.add('marquee');
  });

  // Reveal on scroll: section headings, cards, tiles, columns and media fade up with small staggers.
  const targets = '.blab-section-head, .blab-intro .wp-block-column, .blab-card, .blab-tile, .blab-project, .blab-poster, .blab-compare, .blab-video, .blab-three .wp-block-column, .blab-location, .blab-icon-row li, .blab-stills figure, .blab-split > .wp-block-column, .blab-lab-text .wp-block-column, .blab-post-card, .blab-restore > p, .blab-cta-inner > *';
  const items = [...d.querySelectorAll(targets)].filter((el) => !el.closest('.blab-page-hero'));
  items.forEach((el) => el.setAttribute('data-reveal', ''));
  const show = (el) => el.classList.add('in');
  if (reduce || !('IntersectionObserver' in window)) {
    items.forEach(show);
  } else {
    const io = new IntersectionObserver((entries) => entries.forEach((entry) => {
      if (entry.isIntersecting) { show(entry.target); io.unobserve(entry.target); }
    }), { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });
    items.forEach((el) => {
      const siblings = [...el.parentElement.children].filter((c) => c.hasAttribute('data-reveal'));
      const index = siblings.indexOf(el);
      if (index > 0) el.style.setProperty('--d', String(Math.min(index % 5, 4)));
      io.observe(el);
    });
    // Safety net for fast jumps: anything already scrolled past is shown.
    let pending = items.slice();
    let sweeping = false;
    const sweep = () => {
      sweeping = false;
      const limit = window.innerHeight * 0.95;
      pending = pending.filter((el) => {
        if (el.getBoundingClientRect().top < limit) { show(el); io.unobserve(el); return false; }
        return true;
      });
      if (!pending.length) window.removeEventListener('scroll', onSweep);
    };
    const onSweep = () => { if (!sweeping) { sweeping = true; window.setTimeout(sweep, 160); } };
    window.addEventListener('scroll', onSweep, { passive: true });
    window.addEventListener('load', sweep);
  }
  window.addEventListener('beforeprint', () => items.forEach(show));

  // Before / after grading sliders (also usable with the keyboard through the range input).
  d.querySelectorAll('[data-compare]').forEach((box) => {
    const range = box.querySelector('.cmp-range');
    const set = () => box.style.setProperty('--pos', range.value + '%');
    range.addEventListener('input', set);
    set();
  });

  // Gallery lightbox (restoration stills and any gallery linked to its images).
  const links = [...d.querySelectorAll('.wp-block-gallery a[href]')].filter((a) => a.querySelector('img'));
  if (links.length) {
    const box = d.createElement('dialog');
    box.className = 'lightbox';
    box.innerHTML = '<button class="lb-close" type="button" aria-label="Close">×</button><button class="lb-prev" type="button" aria-label="Previous image">‹</button><img alt=""><button class="lb-next" type="button" aria-label="Next image">›</button>';
    d.body.appendChild(box);
    const img = box.querySelector('img');
    let current = 0;
    const view = (i) => { current = (i + links.length) % links.length; img.src = links[current].href; img.alt = links[current].querySelector('img').alt; };
    links.forEach((a, i) => a.addEventListener('click', (e) => { e.preventDefault(); view(i); box.showModal(); }));
    box.querySelector('.lb-close').addEventListener('click', () => box.close());
    box.querySelector('.lb-prev').addEventListener('click', () => view(current - 1));
    box.querySelector('.lb-next').addEventListener('click', () => view(current + 1));
    box.addEventListener('click', (e) => { if (e.target === box) box.close(); });
    box.addEventListener('keydown', (e) => { if (e.key === 'ArrowLeft') view(current - 1); if (e.key === 'ArrowRight') view(current + 1); });
  }

  // Forms: validation, then an in-page submit (falls back to a normal post).
  d.querySelectorAll('[data-blab-form]').forEach((form) => {
    let loaded = Date.now();
    const elapsed = form.querySelector('[data-elapsed]');
    const status = form.querySelector('.form-status');
    const setError = (field, message) => {
      const wrap = field.closest('.field');
      field.setAttribute('aria-invalid', message ? 'true' : 'false');
      if (wrap) wrap.querySelector('.field-error').textContent = message || '';
    };
    const say = (cls, text) => { status.innerHTML = ''; const p = d.createElement('p'); p.className = cls; p.textContent = text; status.appendChild(p); };
    form.addEventListener('submit', async (e) => {
      elapsed.value = String(Date.now() - loaded);
      let first = null;
      form.querySelectorAll('input:not([type=hidden]):not([name=blab_website]), textarea').forEach((field) => {
        const label = (field.getAttribute('aria-label') || field.closest('.field').querySelector('label').firstChild.textContent).replace('*', '').trim();
        let message = '';
        if (field.required && !field.value.trim()) message = label + ' is required.';
        else if (field.type === 'email' && field.value && !field.checkValidity()) message = 'Please enter a valid email address.';
        setError(field, message);
        if (message && !first) first = field;
      });
      e.preventDefault();
      if (first) { say('form-error', 'Please check the highlighted fields.'); first.focus(); return; }
      form.classList.add('sending');
      try {
        // getAttribute: the hidden field named "action" shadows form.action.
        const response = await fetch(form.getAttribute('action'), { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (result.ok) {
          form.reset();
          loaded = Date.now();
          say('form-success', result.message);
        } else {
          say('form-error', result.message);
          Object.entries(result.errors || {}).forEach(([key, message]) => { const field = form.querySelector('[name="blab_' + key + '"]'); if (field) setError(field, message); });
        }
      } catch (error) {
        HTMLFormElement.prototype.submit.call(form);
        return;
      } finally {
        form.classList.remove('sending');
      }
      status.scrollIntoView({ block: 'nearest', behavior: reduce ? 'auto' : 'smooth' });
    });
  });
})();
