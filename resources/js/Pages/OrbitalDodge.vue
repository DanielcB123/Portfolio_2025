<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const isRunning = ref(false);
const isGameOver = ref(false);
const isReady = ref(true);

const score = ref(0);
const bestScore = ref(0);
const leaderboard = ref([]);

const showNamePrompt = ref(false);
const playerName = ref('');
const pendingScore = ref(null);
const isSavingScore = ref(false);
const saveError = ref('');

const isNovaMode = ref(false);
const novaTimer = ref(0);
// const novaVisualTriggered = ref(false);

const formattedLeaderboard = computed(() =>
  leaderboard.value.slice().sort((a, b) => b.score - a.score).slice(0, 5)
);

// theme
const theme = ref('dark');

const applyTheme = (value) => {
  theme.value = value;

  if (typeof window === 'undefined') return;

  const root = window.document.documentElement;

  if (value === 'dark') {
    root.classList.add('dark');
  } else {
    root.classList.remove('dark');
  }

  window.localStorage.setItem('theme', value);
};

// arena size
const arenaWidth = 380;
const arenaHeight = 380;

// player
const player = reactive({
  x: arenaWidth.value / 2,
  y: arenaHeight.value / 2,
  radius: 12,
  speed: 220,
});

// enemies
const enemies = ref([]); // { id, x, y, vx, vy, radius }

// stars
const stars = ref([]); // { id, x, y, radius }

const explosions = ref([]); // { id, x, y, radius, life }
let nextExplosionId = 1;


const keys = reactive({
  left: false,
  right: false,
  up: false,
  down: false,
});

let nextEnemyId = 1;
let nextStarId = 1;

let spawnTimer = 0;
let spawnInterval = 1.1;

let starTimer = 0;
let starInterval = 4;

let novaCooldown = 0; 

let lastTime = 0;
let rafId = null;

// difficulty scaling from stars
let enemySpeedBoost = 0;
let enemySizeBoost = 0;

function createExplosion(x, y, baseRadius = 12) {
  explosions.value.push({
    id: nextExplosionId++,
    x,
    y,
    radius: baseRadius,
    life: 1, // 1 to 0 over time
  });
}

function updateArenaSize() {
  if (typeof window === 'undefined') return;

  // Try to keep it within the viewport, accounting for padding
  const padding = 64; // rough total horizontal padding from layout
  const maxSize = 380;
  const minSize = 260;

  const viewportWidth = window.innerWidth;
  const available = viewportWidth - padding;

  const size = Math.max(minSize, Math.min(maxSize, available));

  arenaWidth.value = size;
  arenaHeight.value = size;

  // Clamp player inside new bounds
  const r = player.radius;
  if (player.x > arenaWidth.value - r) player.x = arenaWidth.value - r;
  if (player.y > arenaHeight.value - r) player.y = arenaHeight.value - r;
}

async function fetchLeaderboard() {
  try {
    const { data } = await axios.get('/api/leaderboard/orbital-dodge');
    leaderboard.value = Array.isArray(data) ? data : [];
  } catch (e) {
    console.error('Failed to load leaderboard', e);
  }
}

function startMove(dir) {
  // on first touch, if idle, start the game
  if (!isRunning.value && !isGameOver.value) {
    startGame();
  }

  if (dir === 'left') keys.left = true;
  if (dir === 'right') keys.right = true;
  if (dir === 'up') keys.up = true;
  if (dir === 'down') keys.down = true;
}

function stopMove(dir) {
  if (dir === 'left') keys.left = false;
  if (dir === 'right') keys.right = false;
  if (dir === 'up') keys.up = false;
  if (dir === 'down') keys.down = false;
}


function qualifiesForTopTen(newScore) {
  if (newScore <= 0) return false;

  const current = leaderboard.value.slice().sort((a, b) => b.score - a.score);

  if (current.length < 5) {
    return true;
  }

  const minTopScore = current[current.length - 1]?.score ?? 0;
  return newScore > minTopScore;
}

async function submitScoreToServer() {
  if (!playerName.value.trim() || pendingScore.value == null) return;

  isSavingScore.value = true;
  saveError.value = '';

  try {
    await axios.post('/api/leaderboard/orbital-dodge', {
      name: playerName.value.trim(),
      score: pendingScore.value,
    });

    await fetchLeaderboard();

    showNamePrompt.value = false;
    pendingScore.value = null;
    playerName.value = '';
  } catch (e) {
    console.error(e);
    saveError.value = 'Could not save score. Please try again.';
  } finally {
    isSavingScore.value = false;
  }
}

function cancelSaveScore() {
  showNamePrompt.value = false;
  pendingScore.value = null;
  playerName.value = '';
  saveError.value = '';
}


// storage
function loadStorage() {
  if (typeof window === 'undefined') return;

  const rawBest = window.localStorage.getItem('orbital_dodge_best_score');
  if (rawBest) bestScore.value = parseInt(rawBest, 10) || 0;
}

function saveBestScore() {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem('orbital_dodge_best_score', String(bestScore.value));
}


function saveLeaderboard() {
  if (typeof window === 'undefined') return;
  window.localStorage.setItem('orbital_dodge_leaderboard', JSON.stringify(leaderboard.value));
}

function activateNovaMode() {
  isNovaMode.value = true;
  novaTimer.value = 10; // seconds
}


// game setup / reset
function resetGameState() {
  player.x = arenaWidth.value / 2;
  player.y = arenaHeight.value / 2;

  enemies.value = [];
  stars.value = [];
  score.value = 0;

  spawnTimer = 0;
  spawnInterval = 1.1;

  starTimer = 0;
  starInterval = 4;

  enemySpeedBoost = 0;
  enemySizeBoost = 0;

  isGameOver.value = false;

  // reset nova
  isNovaMode.value = false;
  novaTimer.value = 0;
  novaCooldown = 0;     // reset cooldown too
}



// enemy spawning
function spawnEnemy() {
  const side = Math.floor(Math.random() * 4);
  let x, y;

  const margin = 10;

  if (side === 0) {
    x = margin + Math.random() * (arenaWidth.value - margin * 2);
    y = -margin;
  } else if (side === 1) {
    x = arenaWidth.value + margin;
    y = margin + Math.random() * (arenaHeight.value - margin * 2);
  } else if (side === 2) {
    x = margin + Math.random() * (arenaWidth.value - margin * 2);
    y = arenaHeight.value + margin;
  } else {
    x = -margin;
    y = margin + Math.random() * (arenaHeight.value - margin * 2);
  }


  const radius = 8 + Math.random() * 4 + enemySizeBoost;

  const dx = player.x - x;
  const dy = player.y - y;
  const len = Math.hypot(dx, dy) || 1;

  const speed = 90 + score.value * 0.6 + enemySpeedBoost;

  const vx = (dx / len) * speed;
  const vy = (dy / len) * speed;

  enemies.value.push({
    id: nextEnemyId++,
    x,
    y,
    vx,
    vy,
    radius,
  });
}

// star spawning
function spawnStar() {
  const margin = 30;
  const radius = 10 + Math.random() * 3;

  const x = margin + Math.random() * (arenaWidth.value - margin * 2);
  const y = margin + Math.random() * (arenaHeight.value - margin * 2);


  stars.value.push({
    id: nextStarId++,
    x,
    y,
    radius,
    type: 'star', 
  });
}


function spawnNovaOrb() {
  const margin = 30;
  const radius = 10;

  const x = margin + Math.random() * (arenaWidth - margin * 2);
  const y = margin + Math.random() * (arenaHeight - margin * 2);

  stars.value.push({
    id: nextStarId++,
    x,
    y,
    radius,
    type: 'nova',
  });
}



// main loop logic
function update(delta) {
  if (!isRunning.value) return;

  // move player
  let vx = 0;
  let vy = 0;
  if (keys.left) vx -= 1;
  if (keys.right) vx += 1;
  if (keys.up) vy -= 1;
  if (keys.down) vy += 1;

  if (vx !== 0 || vy !== 0) {
    const len = Math.hypot(vx, vy) || 1;
    vx = (vx / len) * player.speed * delta;
    vy = (vy / len) * player.speed * delta;

    player.x += vx;
    player.y += vy;

    const r = player.radius;
    if (player.x < r) player.x = r;
    if (player.x > arenaWidth.value - r) player.x = arenaWidth.value - r;
    if (player.y < r) player.y = r;
    if (player.y > arenaHeight.value - r) player.y = arenaHeight.value - r;
  }

  // move enemies
  enemies.value.forEach((e) => {
    e.x += e.vx * delta;
    e.y += e.vy * delta;
  });

  // remove far enemies
  enemies.value = enemies.value.filter(
    (e) =>
      e.x > -80 &&
      e.x < arenaWidth.value + 80 &&
      e.y > -80 &&
      e.y < arenaHeight.value + 80
  );

  // spawn enemies
  spawnTimer += delta;
  if (spawnTimer >= spawnInterval) {
    spawnTimer = 0;
    spawnEnemy();
    if (spawnInterval > 0.55) spawnInterval -= 0.02;
  }

  // spawn stars occasionally, keep at most 3
  starTimer += delta;
  if (starTimer >= starInterval) {
    starTimer = 0;
    if (stars.value.length < 3) {
      const hasNovaInArena = stars.value.some(s => s.type === 'nova');
      const canSpawnNova = !isNovaMode.value && novaCooldown <= 0 && !hasNovaInArena;

      const roll = Math.random();

      if (canSpawnNova && roll >= 0.65) {
        // 15 percent chance for nova, only if allowed
        spawnNovaOrb();
      } else {
        // otherwise always spawn a normal star
        spawnStar();
      }
    }
  }


  // star + nova collection
  for (let i = stars.value.length - 1; i >= 0; i--) {
    const s = stars.value[i];
    const dist = Math.hypot(s.x - player.x, s.y - player.y);

    if (dist < s.radius + player.radius) {

      // NOVA ORB
      if (s.type === 'nova') {
        activateNovaMode();       // turn on invincibility + visual state
        stars.value.splice(i, 1);
        continue;
      }

      // NORMAL STAR
      score.value += 20;
      enemySpeedBoost += 10;
      enemySizeBoost += 0.8;
      spawnInterval = Math.max(0.45, spawnInterval - 0.03);
      stars.value.splice(i, 1);
    }
  }


  // explosions - expand and fade out
  for (let i = explosions.value.length - 1; i >= 0; i--) {
    const ex = explosions.value[i];

    // expand slightly over time
    ex.radius += 60 * delta;

    // fade out
    ex.life -= 2 * delta; // lives about 0.5 seconds

    if (ex.life <= 0) {
      explosions.value.splice(i, 1);
    }
  }

  // collision with enemies
  for (let i = enemies.value.length - 1; i >= 0; i--) {
    const e = enemies.value[i];
    const dist = Math.hypot(e.x - player.x, e.y - player.y);

    if (dist < e.radius + player.radius) {
      if (isNovaMode.value) {
        // destroy enemy in nova mode and award points
        score.value += 5;

        // explosion effect centered on player
        createExplosion(player.x, player.y, player.radius + 10);

        enemies.value.splice(i, 1);
        continue;
      }

      // normal game over before nova is collected
      endGame();
      return;
    }
  }






  // Nova Mode timer and cooldown
  if (isNovaMode.value) {
    const before = novaTimer.value;
    novaTimer.value -= delta;

    if (before > 0 && novaTimer.value <= 0) {
      // nova effect just ended
      isNovaMode.value = false;
      novaTimer.value = 0;
      novaCooldown = 5; // at least 5 seconds before another nova can appear
    }
  } else if (novaCooldown > 0) {
    novaCooldown -= delta;
    if (novaCooldown < 0) novaCooldown = 0;
  }




  // passive score by time
  score.value += delta * 10;
  score.value = Math.floor(score.value);
}

function loop(timestamp) {
  const delta = (timestamp - lastTime) / 1000;
  lastTime = timestamp;

  update(delta);

  if (isRunning.value) {
    rafId = window.requestAnimationFrame(loop);
  }
}

function startGame() {
  resetGameState();
  isRunning.value = true;
  isGameOver.value = false;
  lastTime = performance.now();

  if (rafId) window.cancelAnimationFrame(rafId);
  rafId = window.requestAnimationFrame(loop);
}

function endGame() {
  isRunning.value = false;
  isGameOver.value = true;

  if (score.value > bestScore.value) {
    bestScore.value = score.value;
    saveBestScore();
  }

  // Decide if this run qualifies for top 10
  if (qualifiesForTopTen(score.value)) {
    pendingScore.value = score.value;
    playerName.value = '';
    showNamePrompt.value = true;
  }

  if (rafId) {
    window.cancelAnimationFrame(rafId);
    rafId = null;
  }
}


// input
function handleKeyDown(e) {
  if (e.code === 'ArrowLeft' || e.code === 'KeyA') keys.left = true;
  if (e.code === 'ArrowRight' || e.code === 'KeyD') keys.right = true;
  if (e.code === 'ArrowUp' || e.code === 'KeyW') keys.up = true;
  if (e.code === 'ArrowDown' || e.code === 'KeyS') keys.down = true;

  if (e.code === 'Enter' && !isRunning.value) {
    e.preventDefault();
    startGame();
  }
}


function handleKeyUp(e) {
  if (e.code === 'ArrowLeft' || e.code === 'KeyA') keys.left = false;
  if (e.code === 'ArrowRight' || e.code === 'KeyD') keys.right = false;
  if (e.code === 'ArrowUp' || e.code === 'KeyW') keys.up = false;
  if (e.code === 'ArrowDown' || e.code === 'KeyS') keys.down = false;
}

function handleArenaClick() {
  if (!isRunning.value) {
    startGame();
  }
}

onMounted(async () => {
  loadStorage();
  updateArenaSize();     // <–– add this before reset
  resetGameState();

  if (typeof window !== 'undefined') {
    const stored = window.localStorage.getItem('theme');
    if (stored === 'light' || stored === 'dark') {
      applyTheme(stored);
    } else {
      const prefersDark =
        window.matchMedia &&
        window.matchMedia('(prefers-color-scheme: dark)').matches;
      applyTheme(prefersDark ? 'dark' : 'light');
    }

    window.addEventListener('resize', updateArenaSize);  // <–– add
  }

  await fetchLeaderboard();

  window.addEventListener('keydown', handleKeyDown);
  window.addEventListener('keyup', handleKeyUp);
});



onBeforeUnmount(() => {
  window.removeEventListener('keydown', handleKeyDown);
  window.removeEventListener('keyup', handleKeyUp);
  if (typeof window !== 'undefined') {
    window.removeEventListener('resize', updateArenaSize);
  }
  if (rafId) window.cancelAnimationFrame(rafId);
});

</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <Head title="Orbital Dodge" />

    <!-- Background gradients and grid, similar to Incident Command -->
    <div
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.16)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.4)_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.16)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(56,189,248,0.15)_0,_rgba(15,23,42,1)_55%)]"
    ></div>
    <div
      class="pointer-events-none absolute inset-0 opacity-30 mix-blend-soft-light [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:56px_56px] dark:[background-image:linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)]"
    ></div>

    <!-- Content wrapper -->
    <div class="relative z-10 flex min-h-screen items-center justify-center px-4">
      <div class="relative w-full max-w-5xl">
        <!-- Top bar: back to portfolio + theme toggle -->
        <div class="mb-5 flex items-center justify-between gap-3">
          <div class="flex-1">
            <Link
              href="/"
              class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/80 px-4 py-1.5 text-xs text-slate-700 shadow shadow-slate-200/60 backdrop-blur transition hover:border-emerald-400/80 hover:text-emerald-600 hover:bg-white dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300 dark:shadow-slate-900/40 dark:hover:border-emerald-400/80 dark:hover:text-emerald-300 dark:hover:bg-slate-900"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-3.5 w-3.5 opacity-70"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6l-6 6 6 6M20 12H4" />
              </svg>
              Back to Portfolio
            </Link>
          </div>

          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/70 px-3 py-1.5 text-[11px] text-slate-700 shadow shadow-slate-200/60 backdrop-blur transition hover:border-emerald-400/80 hover:text-emerald-600 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300 dark:shadow-slate-900/40 dark:hover:border-emerald-400/80 dark:hover:text-emerald-300"
            @click="applyTheme(theme === 'dark' ? 'light' : 'dark')"
          >
            <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-amber-300">
              <span v-if="theme === 'dark'">☾</span>
              <span v-else>☀</span>
            </span>
            <span class="hidden sm:inline">
              <span v-if="theme === 'dark'">Dark mode</span>
              <span v-else>Light mode</span>
            </span>
          </button>
        </div>

        <!-- Halo glow behind main card -->
        <div
          class="pointer-events-none absolute -inset-10 rounded-[32px] bg-gradient-to-tr from-blue-500/25 via-emerald-400/18 to-cyan-400/22 blur-3xl"
        ></div>

        <!-- Main card -->
        <div
          class="relative rounded-2xl border border-slate-200 bg-white/95 px-6 py-7 shadow-2xl shadow-slate-200/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-slate-950/80"
        >
          <!-- Header, similar vibe to IC login -->
          <header class="mb-7 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-400 mb-1">
                Portfolio game
              </p>
              <h1 class="text-xl font-semibold text-slate-900 dark:text-slate-100">
                Orbital Dodge
              </h1>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-300 max-w-xl">
                Top down survival arena. Move with WASD or arrow keys, dodge hostile orbs, collect stars
                for bonus points, and watch the difficulty ramp up as you survive.
              </p>
            </div>

            <div
              class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] text-slate-700 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300"
            >
              <span
                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 text-[10px] font-semibold text-slate-950"
              >
                OD
              </span>
              <span class="hidden sm:inline">
                Reactivity, timing, and flow.
              </span>
            </div>
          </header>

          <!-- Game and leaderboard grid -->
          <div class="grid gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] items-start">
            <!-- Game panel -->
            <div
              class="relative rounded-2xl border border-slate-200 bg-slate-50/95 p-4 shadow-xl shadow-slate-200/80 dark:border-slate-800 dark:bg-slate-950/90 dark:shadow-slate-950/60"
            >
              <div class="flex items-center justify-between mb-3 text-xs text-slate-500 dark:text-slate-300">
                <span class="inline-flex items-center gap-1">
                  <span
                    class="flex h-2 w-2 rounded-full"
                    :class="isRunning ? 'bg-emerald-400' : isGameOver ? 'bg-rose-400' : 'bg-slate-400 dark:bg-slate-600'"
                  />
                  <span class="uppercase tracking-[0.18em] text-[10px]">
                    {{ isRunning ? 'Running' : isGameOver ? 'Game over' : 'Idle' }}
                  </span>
                </span>
                <div class="flex items-center gap-4">
                  <span>
                    Score
                    <span class="font-semibold text-emerald-500 dark:text-emerald-400">{{ score }}</span>
                  </span>
                  <span class="hidden sm:inline">
                    Best
                    <span class="font-semibold text-cyan-500 dark:text-cyan-400">{{ bestScore }}</span>
                  </span>
                </div>
              </div>

              <!-- Arena -->
              <div
                class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-200/95 dark:border-slate-800 dark:bg-slate-950/90 cursor-pointer"
                style="{ width: arenaWidth + 'px', height: arenaHeight + 'px' }"
                @click="handleArenaClick"
              >
                <!-- background grid -->
                <div
                  class="absolute inset-0 pointer-events-none"
                  style="
                    background-image: radial-gradient(circle at 1px 1px, rgba(148,163,184,0.18) 1px, transparent 0);
                    background-size: 18px 18px;
                  "
                ></div>

                <!-- stars and nova orbs (collectibles) -->
                <div
                  v-for="star in stars"
                  :key="'star-' + star.id"
                  class="absolute"
                >
                  <!-- normal star -->
                  <div
                    v-if="star.type === 'star' || !star.type"
                    class="star-wrapper"
                    :style="{
                      width: star.radius * 2 + 'px',
                      height: star.radius * 2 + 'px',
                      transform: `translate(${star.x - star.radius}px, ${star.y - star.radius}px)`
                    }"
                  >
                    <div class="star-core"></div>
                    <div class="star-sparkle sparkle-1"></div>
                    <div class="star-sparkle sparkle-2"></div>
                    <div class="star-sparkle sparkle-3"></div>
                    <div class="star-sparkle sparkle-4"></div>
                  </div>

                  <!-- nova orb -->
                  <div
                    v-else-if="star.type === 'nova'"
                    class="nova-render"
                    :style="{
                      width: star.radius * 2 + 'px',
                      height: star.radius * 2 + 'px',
                      transform: `translate(${star.x - star.radius}px, ${star.y - star.radius}px)`
                    }"
                  ></div>
                </div>

                <!-- explosions when enemies are destroyed in nova mode -->
                <div
                  v-for="exp in explosions"
                  :key="'explosion-' + exp.id"
                  class="absolute explosion-ring"
                  :style="{
                    width: exp.radius * 2 + 'px',
                    height: exp.radius * 2 + 'px',
                    transform: `translate(${exp.x - exp.radius}px, ${exp.y - exp.radius}px)`,
                    opacity: exp.life
                  }"
                ></div>


                <!-- player -->
                <div
                  class="absolute rounded-full shadow-lg"
                  :style="{
                    width: player.radius * 2 + 'px',
                    height: player.radius * 2 + 'px',
                    transform: `translate(${player.x - player.radius}px, ${player.y - player.radius}px)`,
                    background: 'radial-gradient(circle at 30% 30%, #a5f3fc, #22c55e)',
                    boxShadow: '0 0 12px rgba(45,212,191,0.8)',
                  }"
                ></div>

                <!-- enemies -->
                <div
                  v-for="enemy in enemies"
                  :key="enemy.id"
                  class="absolute rounded-full"
                  :style="{
                    width: enemy.radius * 2 + 'px',
                    height: enemy.radius * 2 + 'px',
                    transform: `translate(${enemy.x - enemy.radius}px, ${enemy.y - enemy.radius}px)`,
                    background: isNovaMode
                      ? 'radial-gradient(circle at 30% 30%, #e0f2fe, #38bdf8)'   // cool blue when safe to hit
                      : 'radial-gradient(circle at 30% 30%, #fed7aa, #fb923c)', // normal danger orange
                    boxShadow: isNovaMode
                      ? '0 0 12px rgba(56,189,248,0.9)'
                      : '0 0 10px rgba(248,113,113,0.9)',
                  }"
                />




                <!-- overlays -->
                <div
                  v-if="!isRunning && !isGameOver"
                  class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center bg-slate-950/70 dark:bg-slate-950/80"
                >
                  <p class="text-sm font-semibold text-slate-100 mb-1">Orbital Dodge</p>
                  <p class="text-[11px] text-slate-300 mb-2 max-w-xs">
                    Click inside the arena or press Enter to start. Use WASD or arrow keys to dodge hostile
                    orbs. Collect stars to gain points and make the arena more dangerous.
                  </p>
                </div>

                <div
                  v-if="isGameOver"
                  class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center bg-slate-950/80"
                >
                  <p class="text-base font-semibold text-rose-300 mb-1">Game over</p>
                  <p class="text-xs text-slate-200 mb-1">
                    Score {{ Math.max(score, 0) }} / Best {{ Math.max(bestScore, 0) }}
                  </p>
                  <p class="text-[11px] text-slate-400">
                    Click the arena or press Enter to play again.
                  </p>
                </div>
              </div>

              <!-- Mobile touch controls -->
              <div class="mt-4 flex flex-col items-center gap-2 sm:hidden">
                <div class="grid grid-cols-3 gap-2">
                  <!-- top row -->
                  <div></div>
                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white/90 text-xs font-semibold text-slate-700 shadow-sm active:scale-95 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    @pointerdown.prevent="startMove('up')"
                    @pointerup.prevent="stopMove('up')"
                    @pointercancel.prevent="stopMove('up')"
                    @pointerleave.prevent="stopMove('up')"
                  >
                    ↑
                  </button>
                  <div></div>

                  <!-- middle row -->
                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white/90 text-xs font-semibold text-slate-700 shadow-sm active:scale-95 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    @pointerdown.prevent="startMove('left')"
                    @pointerup.prevent="stopMove('left')"
                    @pointercancel.prevent="stopMove('left')"
                    @pointerleave.prevent="stopMove('left')"
                  >
                    ←
                  </button>

                  <div
                    class="flex items-center justify-center text-[10px] text-slate-500 dark:text-slate-400"
                  >
                    Hold to move
                  </div>

                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white/90 text-xs font-semibold text-slate-700 shadow-sm active:scale-95 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    @pointerdown.prevent="startMove('right')"
                    @pointerup.prevent="stopMove('right')"
                    @pointercancel.prevent="stopMove('right')"
                    @pointerleave.prevent="stopMove('right')"
                  >
                    →
                  </button>

                  <!-- bottom row -->
                  <div></div>
                  <button
                    type="button"
                    class="flex h-9 w-9 items-center justify-center rounded-full border border-slate-300 bg-white/90 text-xs font-semibold text-slate-700 shadow-sm active:scale-95 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                    @pointerdown.prevent="startMove('down')"
                    @pointerup.prevent="stopMove('down')"
                    @pointercancel.prevent="stopMove('down')"
                    @pointerleave.prevent="stopMove('down')"
                  >
                    ↓
                  </button>
                  <div></div>
                </div>
              </div>


              <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-400 px-4 py-1.5 text-xs font-semibold text-slate-950 shadow-md shadow-emerald-500/30 hover:translate-y-0.5 hover:shadow-lg hover:shadow-emerald-500/40 active:translate-y-[1px]"
                    @click="startGame"
                  >
                    <span v-if="!isRunning && !isGameOver">Start run</span>
                    <span v-else-if="isRunning">Restart</span>
                    <span v-else>Play again</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Leaderboard panel -->
            <aside
              class="rounded-2xl border border-slate-200 bg-slate-50/95 p-4 shadow-xl shadow-slate-200/80 text-xs dark:border-slate-800 dark:bg-slate-950/90 dark:shadow-slate-950/60"
            >
              <h2 class="mb-2 text-sm font-semibold text-slate-900 dark:text-slate-100 flex items-center justify-between">
                Leaderboard
                <span class="text-[11px] font-normal text-slate-400">Global demo leaderboard</span>
              </h2>

              <div v-if="formattedLeaderboard.length" class="space-y-2">
                <div
                  v-for="(entry, idx) in formattedLeaderboard"
                  :key="entry.id ?? entry.created_at + entry.score + idx"
                  class="flex items-center justify-between rounded-lg border border-slate-200 bg-white/95 px-2 py-2 dark:border-slate-700 dark:bg-slate-900/80"
                >
                  <div class="flex items-center gap-2">
                    <span
                      class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[11px] text-slate-800 dark:bg-slate-800 dark:text-slate-100"
                    >
                      {{ idx + 1 }}
                    </span>
                    <div>
                      <div class="font-medium text-slate-900 dark:text-slate-100">
                        {{ entry.name || 'Anonymous' }}
                      </div>
                      <div class="text-[10px] text-slate-500 dark:text-slate-400">
                        {{ entry.score }} pts · {{ new Date(entry.created_at).toLocaleString() }}
                      </div>
                    </div>
                  </div>
                  <span class="text-[10px] text-emerald-500 dark:text-emerald-400">Orbital Dodge</span>
                </div>
              </div>

              <div
                v-else
                class="rounded-lg border border-dashed border-slate-300 px-3 py-6 text-center text-slate-500 dark:border-slate-700 dark:text-slate-400"
              >
                <p class="mb-1 text-slate-700 dark:text-slate-200">No runs yet</p>
                <p class="text-[11px]">
                  Your best runs on this device will appear here once you start playing.
                </p>
              </div>

<!-- Legend -->
<div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-800">
  <h3 class="mb-2 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
    Arena legend
  </h3>

  <div class="space-y-3 text-[11px] text-slate-600 dark:text-slate-300">
    <!-- Player (real orb) -->
    <div class="flex items-center gap-3">
      <div class="legend-orb-wrapper">
        <div class="player-orb"></div>
      </div>
      <div>
        <div class="font-medium text-slate-800 dark:text-slate-100">You</div>
        <div class="text-[10px] text-slate-500 dark:text-slate-400">
          Fast, responsive, and fragile. Avoid everything orange.
        </div>
      </div>
    </div>

    <!-- Enemy (real orb) -->
    <div class="flex items-center gap-3">
      <div class="legend-orb-wrapper">
        <div class="enemy-orb"></div>
      </div>
      <div>
        <div class="font-medium text-slate-800 dark:text-slate-100">Hostile orbs</div>
        <div class="text-[10px] text-slate-500 dark:text-slate-400">
          Home in on your position. Touching one ends the run instantly.
        </div>
      </div>
    </div>

    <!-- Star (real star with sparkles) -->
    <div class="flex items-center gap-3">
      <div class="legend-orb-wrapper">
        <div class="star-core star-core--legend"></div>
        <div class="star-sparkle sparkle-1"></div>
        <div class="star-sparkle sparkle-2"></div>
        <div class="star-sparkle sparkle-3"></div>
        <div class="star-sparkle sparkle-4"></div>
      </div>
      <div>
        <div class="font-medium text-slate-800 dark:text-slate-100">Power stars</div>
        <div class="text-[10px] text-slate-500 dark:text-slate-400">
          Worth <span class="font-semibold text-emerald-500 dark:text-emerald-400">+20 points</span>.
          Also increases enemy speed and size. High risk, high reward.
        </div>
      </div>
    </div>

    <!-- Nova Orb (real nova orb) -->
    <div class="flex items-center gap-3">
      <div class="legend-orb-wrapper">
        <div class="nova-render nova-render--legend"></div>
      </div>
      <div>
        <div class="font-medium text-slate-800 dark:text-slate-100">Nova orb</div>
        <div class="text-[10px] text-slate-500 dark:text-slate-400">
          Grants <span class="font-semibold text-cyan-400">10 seconds</span> of invincibility.
          Smash enemy orbs for <span class="font-semibold text-cyan-400">+5 points each</span>.
          When it ends, enemies become dangerous again.
        </div>
      </div>
    </div>


  </div>
</div>


            </aside>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Top 10 name prompt -->
<div
  v-if="showNamePrompt"
  class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm"
>
  <div
    class="w-full max-w-sm rounded-2xl border border-slate-700 bg-slate-900/95 p-5 shadow-2xl text-sm text-slate-100"
  >
    <h3 class="text-base font-semibold mb-1">You made the top 10</h3>
    <p class="text-[11px] text-slate-300 mb-3">
      Nice run, you just earned a spot on the leaderboard. Enter a display name to save your score
      of <span class="font-semibold text-emerald-400">{{ pendingScore }}</span> points.
    </p>

    <label class="block text-[11px] mb-1 text-slate-300">
      Display name
    </label>
    <input
      v-model="playerName"
      type="text"
      maxlength="50"
      class="mb-2 w-full rounded-md border border-slate-600 bg-slate-900 px-3 py-1.5 text-[13px] outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400"
      placeholder="Commander, Ace, Orbital pro..."
    />

    <p v-if="saveError" class="mb-2 text-[11px] text-rose-400">
      {{ saveError }}
    </p>

    <div class="mt-3 flex items-center justify-end gap-2 text-[11px]">
      <button
        type="button"
        class="rounded-full border border-slate-600 px-3 py-1.5 text-slate-200 hover:border-slate-400"
        @click="cancelSaveScore"
        :disabled="isSavingScore"
      >
        Skip
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-emerald-500 to-cyan-400 px-4 py-1.5 font-semibold text-slate-950 shadow-md shadow-emerald-500/40 hover:translate-y-0.5 hover:shadow-lg active:translate-y-[1px]"
        @click="submitScoreToServer"
        :disabled="isSavingScore || !playerName.trim()"
      >
        <span v-if="!isSavingScore">Save score</span>
        <span v-else>Saving...</span>
      </button>
    </div>
  </div>
</div>

</template>

<style scoped>
.star-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Five point star using clip path */
.star-core {
  position: relative;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle at 30% 30%, #fef9c3, #facc15 40%, #f97316 100%);
  clip-path: polygon(
    50% 0%,
    61% 35%,
    98% 35%,
    68% 57%,
    79% 91%,
    50% 70%,
    21% 91%,
    32% 57%,
    2% 35%,
    39% 35%
  );
  box-shadow:
    0 0 10px rgba(250, 204, 21, 0.9),
    0 0 18px rgba(249, 115, 22, 0.8);
  animation: starPulse 1.2s ease-in-out infinite;
}

/* little sparkles around the star */
.star-sparkle {
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 999px;
  background: radial-gradient(circle, #fef9c3 0, #facc15 60%, transparent 100%);
  box-shadow: 0 0 8px rgba(250, 250, 210, 0.9);
  opacity: 0;
  animation: sparkle 0.9s ease-out infinite;
}

/* four different directions for sparkles */
.sparkle-1 {
  animation-delay: 0s;
}
.sparkle-2 {
  animation-delay: 0.15s;
}
.sparkle-3 {
  animation-delay: 0.3s;
}
.sparkle-4 {
  animation-delay: 0.45s;
}

@keyframes starPulse {
  0% {
    transform: scale(0.9) rotate(0deg);
    filter: brightness(1);
  }
  50% {
    transform: scale(1.1) rotate(3deg);
    filter: brightness(1.2);
  }
  100% {
    transform: scale(0.9) rotate(0deg);
    filter: brightness(1);
  }
}

@keyframes sparkle {
  0% {
    opacity: 0;
    transform: translate(0, 0) scale(0.4);
  }
  40% {
    opacity: 1;
  }
  100% {
    opacity: 0;
    transform: translate(8px, -10px) scale(0.9);
  }
}

/* vary each sparkle direction a bit */
.sparkle-1 {
  transform-origin: 0 0;
}
.sparkle-2 {
  transform-origin: 100% 0;
}
.sparkle-3 {
  transform-origin: 0 100%;
}
.sparkle-4 {
  transform-origin: 100% 100%;
}

/* Wrapper to center the orb/star */
.legend-orb-wrapper {
  position: relative;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Player orb (identical to the real player but resized) */
.player-orb {
  width: 100%;
  height: 100%;
  border-radius: 999px;
  background: radial-gradient(circle at 30% 30%, #a5f3fc, #22c55e);
  box-shadow: 0 0 8px rgba(45, 212, 191, 0.9);
}

/* Enemy orb (same as real enemies) */
.enemy-orb {
  width: 100%;
  height: 100%;
  border-radius: 999px;
  background: radial-gradient(circle at 30% 30%, #fed7aa, #fb923c);
  box-shadow: 0 0 8px rgba(248, 113, 113, 0.9);
}

/* Star core scaled down */
.star-core--legend {
  width: 100%;
  height: 100%;
  transform: scale(0.9);
}
/* Scale down nova orb for legend */
/* Base nova orb style (same look as in arena) */
.nova-render {
  border-radius: 9999px;
  background: radial-gradient(circle at 40% 40%, #e0faff, #38bdf8, #0ea5e9);
  box-shadow:
    0 0 10px rgba(14, 165, 233, 0.9),
    0 0 18px rgba(14, 165, 233, 0.7);
}

/* Scale down and animate in legend */
.nova-render--legend {
  position: absolute;
  width: 18px;
  height: 100%;
  animation: novaPulseLegend 1s ease-in-out infinite;
}

@keyframes novaPulseLegend {
  0% {
    transform: scale(0.85);
    filter: brightness(1);
  }
  50% {
    transform: scale(1);
    filter: brightness(1.3);
  }
  100% {
    transform: scale(0.85);
    filter: brightness(1);
  }
}

.explosion-ring {
  border-radius: 9999px;
  border: 2px solid rgba(248, 250, 252, 0.9);
  box-shadow:
    0 0 12px rgba(248, 250, 252, 0.9),
    0 0 24px rgba(56, 189, 248, 0.8);
  background: radial-gradient(circle, rgba(248, 250, 252, 0.6) 0, transparent 60%);
  pointer-events: none;
  /* no transform animation, JS expands radius instead */
}



</style>
