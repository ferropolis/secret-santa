(() => {
  const canvas = document.getElementById('snow-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');

  // === DPI/Retina-Handling ===
  let cssW = 0, cssH = 0, dpr = Math.max(1, Math.floor(window.devicePixelRatio || 1));
  function resizeCanvas() {
    cssW = window.innerWidth;
    cssH = window.innerHeight;
    dpr = Math.max(1, Math.floor(window.devicePixelRatio || 1));
    canvas.style.width = cssW + 'px';
    canvas.style.height = cssH + 'px';
    canvas.width = Math.floor(cssW * dpr);
    canvas.height = Math.floor(cssH * dpr);
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0); // alle Zeichnungen in CSS-Pixeln
    // Boden auf neue Breite mappen
    const newGround = new Array(COLS).fill(0);
    for (let i = 0; i < COLS; i++) newGround[i] = ground[Math.floor(i * ground.length / COLS)] || 0;
    ground = newGround;
  }

  // Motion preferences
  const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (reduceMotion) return;

  // ---- Config ----
  const IDLE_MS = Infinity;     // nach 45s Inaktivität "schneit es ein"
  const BASE_SPAWN = 2;      // anfangs: 1–2 Flocken aktiv
  const MAX_SPAWN = 90;      // bei Inaktivität
  const GRAVITY_MIN = 0.25;
  const GRAVITY_MAX = 0.6;
  const WIND_MAX = 0.3;      // leichte horizontale Drift
  const SIZE_MIN = 1.2;
  const SIZE_MAX = 3.0;

  // "Boden": diskretisierte Spalten für Schneehöhe
  const COLS = 120;
  let ground = new Array(COLS).fill(0);

  // Zustand
  let flakes = [];
  let spawnBudget = BASE_SPAWN;
  let lastActivity = Date.now();
  let lastFrame = performance.now();
  let snowCover = 0;         // 0..1 – wenn 1, ist alles verschneit

  // Events für Aktivität
  ['mousemove', 'keydown', 'wheel', 'touchstart', 'scroll'].forEach(ev => {
    window.addEventListener(ev, () => { lastActivity = Date.now(); }, { passive: true });
  });

  // Resize
  window.addEventListener('resize', resizeCanvas);
  resizeCanvas();

  // Hilfsfunktionen
  const rand = (a, b) => a + Math.random() * (b - a);
  const clamp = (v, a, b) => Math.max(a, Math.min(b, v));

  function colIndex(x) {
    return clamp(Math.floor(x / cssW * COLS), 0, COLS - 1);
  }

  function spawnFlake() {
    flakes.push({
      x: Math.random() * cssW,
      y: -10,
      vx: rand(-WIND_MAX, WIND_MAX),
      vy: rand(GRAVITY_MIN, GRAVITY_MAX),
      r: rand(SIZE_MIN, SIZE_MAX)
    });
  }

  function stickFlake(f) {
    // Flöckchen "landet": erhöhe Bodensäule an der Position
    const ci = colIndex(f.x);
    ground[ci] += f.r * 0.9; // etwas kompakter als der Radius
    // mini seitliche Verteilung, ergibt weicheren Haufen
    if (ci > 0) ground[ci - 1] += f.r * 0.25;
    if (ci < COLS - 1) ground[ci + 1] += f.r * 0.25;
  }

  function drawGround() {
    // Boden als weiße Kontur
    ctx.fillStyle = 'rgba(255,255,255,0.98)';
    const dx = cssW / COLS;

    ctx.beginPath();
    ctx.moveTo(0, cssH);
    for (let i = 0; i < COLS; i++) {
      const x = i * dx;
      const y = cssH - ground[i];
      ctx.lineTo(x, y);
    }
    ctx.lineTo(cssW, cssH);
    ctx.closePath();
    ctx.fill();

    // zarte Kontur oben für Sichtbarkeit
    ctx.strokeStyle = 'rgba(0,0,0,0.10)';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(0, cssH - ground[0]);
    for (let i = 1; i < COLS; i++) {
      const x = i * dx;
      const y = cssH - ground[i];
      ctx.lineTo(x, y);
    }
    ctx.stroke();
  }

  function drawSnowCover(alpha) {
    // transparente weiße Schicht – simuliert "zugeschneit"
    ctx.fillStyle = `rgba(255,255,255,${alpha})`;
    ctx.fillRect(0, 0, cssW, cssH);
  }

  function step(now) {
    const dt = Math.min(50, now - lastFrame); // ms clamp
    lastFrame = now;

    // Check Inaktivität
    const idle = Date.now() - lastActivity > IDLE_MS;
    const targetSpawn = idle ? MAX_SPAWN : BASE_SPAWN;
    spawnBudget += (targetSpawn - spawnBudget) * 0.02; // smooth approach

    // bei Idle: SnowCover langsam steigen lassen
    const targetCover = idle ? 1 : 0;
    snowCover += (targetCover - snowCover) * 0.003 * dt; // sehr langsam, ~5–10s bis voll

    // Hintergrund klaren
    ctx.clearRect(0, 0, cssW, cssH);

    // Flocken nachlegen – SpawnBudget ~ flakes/sec
    const spawns = spawnBudget * (dt / 1000);
    let whole = Math.floor(spawns);
    for (let i = 0; i < whole; i++) spawnFlake();
    if (Math.random() < (spawns - whole)) spawnFlake();

    // Update/Draw Flakes
    const alive = [];
    for (let i = 0; i < flakes.length; i++) {
      const f = flakes[i];
      f.vx += rand(-0.01, 0.01);                  // leichte Turbulenz
      f.x += f.vx * (dt / 16);
      f.y += f.vy * (dt / 16);

      // Bodenhöhe an dieser Stelle
      const ci = colIndex(f.x);
      const floorY = cssH - (ground[ci] || 0);

      // Landen?
      if (f.y + f.r >= floorY) {
        stickFlake(f);
        continue; // nicht weiter zeichnen
      }

      // Bildschirmrand wrap
      if (f.x < -10) f.x = cssW + 9;
      if (f.x > cssW + 10) f.x = -9;

      // === Zeichnen: weiße Flocke mit dezenter Kontur ===
      ctx.fillStyle = '#ffffff';
      ctx.strokeStyle = 'rgba(0,0,0,0.15)'; // zarte sichtbare Outline
      ctx.lineWidth = 0.5;
      ctx.beginPath();
      ctx.arc(f.x, f.y, f.r, 0, Math.PI * 2);
      ctx.fill();
      ctx.stroke();

      alive.push(f);
    }
    flakes = alive.slice(-1200); // Limit, damit’s nicht ausufert

    // Boden und evtl. Schneedecke zeichnen
    drawGround();
    if (snowCover > 0.001) {
      drawSnowCover(clamp(snowCover, 0, 1));
    }

    requestAnimationFrame(step);
  }

  // Start mit 1–2 initialen Flocken
  for (let i = 0; i < BASE_SPAWN; i++) spawnFlake();
  requestAnimationFrame(step);
})();
