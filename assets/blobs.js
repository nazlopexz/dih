// drives the .blob-a/b/c elements on every page — each blob chases the
// cursor at its own speed so the field feels alive instead of one glow
// glued to the pointer
(function () {
  const configs = [
    { selector: '.blob-a', ease: 0.09,  ox: 0,    oy: 0   },
    { selector: '.blob-b', ease: 0.05,  ox: 120,  oy: -80 },
    { selector: '.blob-c', ease: 0.035, ox: -160, oy: 100 },
  ];

  const blobs = configs
    .map(c => ({ ...c, el: document.querySelector(c.selector) }))
    .filter(b => b.el);

  if (!blobs.length) return;

  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  const pos = blobs.map(() => ({ x: mouseX, y: mouseY }));

  window.addEventListener('mousemove', e => {
    mouseX = e.clientX;
    mouseY = e.clientY;
  });

  window.addEventListener('touchmove', e => {
    if (e.touches.length) {
      mouseX = e.touches[0].clientX;
      mouseY = e.touches[0].clientY;
    }
  }, { passive: true });

  function tick() {
    const cx = window.innerWidth / 2;
    const cy = window.innerHeight / 2;
    const distFromCenter = Math.hypot(mouseX - cx, mouseY - cy) / Math.hypot(cx, cy);

    blobs.forEach((b, i) => {
      const targetX = mouseX + b.ox;
      const targetY = mouseY + b.oy;
      pos[i].x += (targetX - pos[i].x) * b.ease;
      pos[i].y += (targetY - pos[i].y) * b.ease;

      const scale = 1 + distFromCenter * (0.15 + i * 0.05);
      const rotate = distFromCenter * 20 * (i % 2 === 0 ? 1 : -1);

      b.el.style.transform =
        `translate(-50%, -50%) translate(${pos[i].x - cx}px, ${pos[i].y - cy}px) scale(${scale}) rotate(${rotate}deg)`;
    });

    requestAnimationFrame(tick);
  }

  tick();
})();
