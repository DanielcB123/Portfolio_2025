<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';

const page = usePage();
const user = page.props.auth?.user;

// THEME TOGGLE
const theme = ref('dark');
const aboutSection = ref(null)
const aboutHighlight = ref(false)

const scrollToAbout = () => {
  if (typeof window === 'undefined') return

  // Scroll to the bottom of the page
  const bottom = document.documentElement.scrollHeight - window.innerHeight

  window.scrollTo({
    top: bottom,
    behavior: 'smooth',
  })

  // Trigger a short highlight animation on the about section
  aboutHighlight.value = true
  window.setTimeout(() => {
    aboutHighlight.value = false
  }, 900)
}


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

const toggleTheme = () => {
  applyTheme(theme.value === 'dark' ? 'light' : 'dark');
};

// Typed code snippet for the "live editor" card
const fullSnippet = `
// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/new-feature', [NewFeatureController::class, 'index'])
        ->name('new-feature.index');

    Route::post('/new-feature', [NewFeatureController::class, 'store'])
        ->name('new-feature.store');
});

// app/Http/Controllers/NewFeatureController.php
class NewFeatureController extends Controller
{
    public function __construct(
        private NewFeatureService $service,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,closed'],
            'q'      => ['nullable', 'string', 'max:120'],
        ]);

        $items = $this->service->listForUser($request->user(), $filters);

        return inertia('NewFeature/Index', [
            'items'   => $items,
            'filters' => $filters,
        ]);
    }

    public function store(StoreNewFeatureRequest $request)
    {
        $item = $this->service->createForUser(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('new-feature.index')
            ->with('flash.success', 'New feature item created.');
    }
}

// app/Services/NewFeatureService.php
class NewFeatureService
{
    public function __construct(
        private NewFeatureRepository $repo,
    ) {}

    public function listForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->repo->forUser($user)
            ->when(
                $filters['status'] ?? null,
                fn ($q, $status) => $q->where('status', $status)
            )
            ->when(
                $filters['q'] ?? null,
                fn ($q, $search) => $q->where('title', 'like', "%{$search}%")
            )
            ->orderByDesc('created_at')
            ->paginate(10);
    }

    public function createForUser(User $user, array $data): NewFeature
    {
        return DB::transaction(function () use ($user, $data) {
            return $this->repo->create([
                'user_id' => $user->id,
                'title'   => $data['title'],
                'status'  => 'open',
                'meta'    => $data['meta'] ?? [],
            ]);
        });
    }
}

// database/migrations/2025_01_01_000000_create_new_features_table.php
Schema::create('new_features', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->string('status')->default('open')->index();
    $table->json('meta')->nullable();
    $table->timestamps();
});

// resources/js/Pages/NewFeature/Index.vue
export default {
  props: {
    items: Object,
    filters: Object,
  },
  setup(props) {
    const form = useForm({
      title: '',
      meta: {},
    });

    const submit = () => {
      form.post(route('new-feature.store'));
    };

    return { form, submit, items: props.items };
  },
};

// resources/views/layouts/app.blade.php (excerpt)
<title>{{ $title ?? 'NewFeature' }} | Company</title>
`;

const typedSnippet = ref('');
const cursorVisible = ref(true);

let typeTimer = null;
let cursorTimer = null;

onMounted(() => {
  // Initialize theme from localStorage or system preference
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
  }

  // Typing effect
  let i = 0;

  typeTimer = window.setInterval(() => {
    if (i < fullSnippet.length) {
      typedSnippet.value += fullSnippet[i];
      i += 1;
    } else {
      window.clearInterval(typeTimer);
      typeTimer = null;
    }
  }, 22); // typing speed

  cursorTimer = window.setInterval(() => {
    cursorVisible.value = !cursorVisible.value;
  }, 550);
});

onBeforeUnmount(() => {
  if (typeTimer) window.clearInterval(typeTimer);
  if (cursorTimer) window.clearInterval(cursorTimer);
});
</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <!-- Glowing gradient orbs -->
    <div class="pointer-events-none absolute inset-0">
      <div
        class="absolute -top-32 -left-16 h-72 w-72 rounded-full bg-gradient-to-br from-blue-500 via-emerald-400 to-cyan-400 opacity-40 blur-3xl animate-orb-float"
      ></div>
      <div
        class="absolute top-1/2 -right-32 h-80 w-80 rounded-full bg-gradient-to-tr from-purple-500 via-fuchsia-500 to-amber-400 opacity-30 blur-3xl animate-orb-float-delayed"
      ></div>
      <div
        class="absolute -bottom-40 left-1/3 h-96 w-96 rounded-full bg-gradient-to-tl from-emerald-500 via-sky-500 to-blue-600 opacity-25 blur-3xl animate-orb-breathe"
      ></div>
    </div>

    <!-- Subtle grid background -->
    <div
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.35)_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.7)_0,_rgba(15,23,42,1)_55%)]"
    ></div>
    <div
      class="pointer-events-none absolute inset-0 opacity-30 mix-blend-soft-light [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:56px_56px] dark:[background-image:linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)]"
    ></div>

    <!-- Content -->
    <div class="relative z-10 flex min-h-screen flex-col">
      <!-- Top nav / badge -->
<header class="flex items-center justify-between px-6 pt-4 sm:px-10 sm:pt-5">
  <div class="mx-auto flex w-full max-w-6xl items-center justify-between pt-2.5">
    <!-- Left: name pill -->
    <div
      class="inline-flex items-center gap-3 rounded-full border border-slate-300/80 bg-white/80 px-4 py-1.5 shadow-lg shadow-blue-500/10 backdrop-blur dark:border-slate-700/70 dark:bg-slate-900/70"
    >
      <span
        class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 text-xs font-semibold text-slate-950"
      >
        DB
      </span>
      <div class="text-xs sm:text-sm leading-tight">
        <div class="font-semibold tracking-wide text-slate-800 uppercase dark:text-slate-100">
          Daniel Burgess
        </div>
        <div class="text-[11px] text-slate-500 dark:text-slate-300">
          Full stack engineer building real world products
        </div>
      </div>
    </div>

    <!-- Right: theme, availability, about -->
    <div class="flex items-center gap-2 sm:gap-3">
      <!-- Theme toggle -->
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

      <!-- Availability -->
      <div
        class="hidden sm:flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-300"
      >
        <span
          class="h-2 w-2 rounded-full bg-emerald-400 shadow shadow-emerald-400/70 animate-pulse"
        ></span>
        <span>Available for new opportunities</span>
      </div>

      <!-- About pill -->
      <div
        class="inline-flex items-center gap-2 rounded-full border border-slate-300/70 bg-white/80 px-3 py-1.5 text-[10px] uppercase tracking-[0.18em] text-slate-600 shadow-lg shadow-slate-200/70 dark:border-slate-700/70 dark:bg-slate-950/70 dark:text-slate-400 dark:shadow-slate-900/80"
      >
        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
        <span>Portfolio</span>
      </div>
    </div>
  </div>
</header>


      <!-- Main hero -->
      <main
        class="flex flex-1 items-center justify-center px-6 pb-10 pt-6 sm:px-10 sm:pt-8 lg:pb-14"
      >
        <div
          class="grid w-full max-w-6xl gap-10 items-center lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1.1fr)]"
        >
          <!-- Left: headline and CTAs -->
          <section class="space-y-6 sm:space-y-7">
            <p
              class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 text-[11px] uppercase tracking-[0.18em] text-slate-600 shadow shadow-blue-500/10 dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-300"
            >
              <span
                class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping-once"
              ></span>
              Turning complex problems into sharp, reliable products
            </p>

            <div class="space-y-4 sm:space-y-5">
              <h1
                class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight text-slate-900 dark:text-slate-50"
              >
                I build
                <span class="relative whitespace-nowrap">
                  <span
                    class="relative z-10 bg-gradient-to-r from-emerald-500 via-cyan-400 to-blue-500 bg-clip-text text-transparent"
                  >
                    production grade
                  </span>
                  <span
                    class="pointer-events-none absolute -inset-1 rounded-full bg-emerald-400/20 blur-xl"
                  ></span>
                </span>
                web and app experiences.
              </h1>

              <p class="max-w-xl text-sm sm:text-base text-slate-600 dark:text-slate-300">  
                From Laravel and Vue to React Native and revived Vanilla PHP legacy systems, I ship tools that matter. 
                CRM platforms, insurance workflows, dispatch systems, billing engines, civic engagement apps. 
                I am ready to create the next one with your team.
              </p>
            </div>

<div class="flex flex-col gap-4 sm:flex-row sm:items-center">
<div class="flex flex-col gap-4 w-full">
  
    <!-- Row 1: Three CTAs -->
    <div class="flex flex-wrap justify-center gap-3 sm:flex-nowrap">
      <div class="flex gap-3 group">
        <!-- Primary CTA -->
        <a
          class="cursor-default inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-blue-500 via-emerald-400 to-cyan-400 px-6 py-2.5 text-sm font-semibold text-slate-950 shadow-xl shadow-emerald-500/25 transition hover:shadow-2xl hover:shadow-emerald-500/40"
        >
          See how I work
          <span
            class="text-lg inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-950/10 text-[11px] group-hover:translate-x-0.5 transition-transform"
          >
            →
          </span>
        </a>

        <!-- Secondary CTA -->
        <a
          href="#about"
          @click.prevent="scrollToAbout"
          class="inline-flex items-center hover:translate-y-0.5 gap-2 rounded-full border border-slate-300/80 bg-white/80 px-5 py-2.5 text-sm font-medium text-slate-900 shadow-lg shadow-slate-200/30 transition
                hover:border-emerald-400/80 hover:bg-white hover:shadow-emerald-500/20
                group-hover:shadow-emerald-500/60
                dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100
                dark:hover:border-emerald-400/80 dark:hover:bg-slate-900 dark:hover:shadow-emerald-500/30"
        >
          Why I am a great hire
        </a>
        </div>

        <!-- Tertiary CTA -->
        <Link
          href="/about"
          class="inline-flex items-center hover:translate-y-0.5 gap-2 rounded-full border border-slate-300/80 bg-white/80 px-5 py-2.5 text-sm font-medium text-slate-900 shadow-lg transition hover:border-emerald-400/80 hover:bg-white hover:shadow-emerald-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-emerald-400/80 dark:hover:bg-slate-900 dark:hover:shadow-emerald-500/30"
        >
          About my background
        </Link>
      </div>

  <!-- Row 2: centered "or" -->
  <div class="flex justify-center">
    <span class="text-[11px] text-slate-500 dark:text-slate-300">or</span>
  </div>

  <!-- Row 3: centered login / go to dashboards -->
  <div class="flex flex-wrap justify-center gap-2">
    <template v-if="user">
      <!-- Existing TaskFlow dashboard button -->
      <Link
        href="/dashboard"
        class="inline-flex items-center gap-2 hover:translate-y-0.5 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1.5 text-xs text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30"
      >
        TaskFlow Dashboard
      </Link>

      <!-- Incident Incident Command Center dashboard button -->
      <Link
        href="/incident-command"
        class="inline-flex items-center gap-2 hover:translate-y-0.5 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1.5 text-xs text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30"
      >
        Incident Command Center Dashboard
      </Link>

      <!-- Game button -->
      <Link
        href="/orbital-dodge"
        class="inline-flex items-center gap-2 hover:translate-y-0.5 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1.5 text-xs text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30"
      >
        Orbital Dodge Game
      </Link>
    </template>

    <template v-else>
      <!-- Existing TaskFlow dashboard button -->
      <Link
        href="/dashboard"
        class="inline-flex items-center gap-2 hover:translate-y-0.5 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1.5 text-xs text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30"
      >
        TaskFlow Dashboard
      </Link>

      <!-- New Incident Command Center login -->
      <Link
        href="/incident-command/login"
        class="inline-flex items-center hover:translate-y-0.5 gap-2 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1.5 text-xs text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30"
      >
        Log in to Incident Command Center Demo
      </Link>

      <!-- Game button -->
      <Link
        href="/orbital-dodge"
        class="inline-flex items-center hover:translate-y-0.5 gap-2 rounded-full border border-slate-300/80 bg-white/80 px-3 py-1.5 text-xs text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30"
      >
        Orbital Dodge Game
      </Link>
    </template>
  </div>


  </div>



            </div>

            <!-- Contact / links strip -->
            <div class="mt-2 flex flex-wrap items-center gap-3 text-[11px] text-slate-500 dark:text-slate-400">
              <span class="uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">
                Contact
              </span>

              <a
                href="https://github.com/DanielBurgessNSA"
                target="_blank"
                rel="noopener"
                class="underline-offset-2 hover:text-emerald-500 hover:underline"
              >
                GitHub (1)
              </a>
              <span class="hidden sm:inline text-slate-400">•</span>

              <a
                href="https://github.com/DanielcB123"
                target="_blank"
                rel="noopener"
                class="underline-offset-2 hover:text-emerald-500 hover:underline"
              >
                GitHub (2)
              </a>
              <span class="hidden sm:inline text-slate-400">•</span>

              <a
                href="https://www.linkedin.com/in/daniel-c-burgess/"
                target="_blank"
                rel="noopener"
                class="underline-offset-2 hover:text-emerald-500 hover:underline"
              >
                LinkedIn
              </a>
              <span class="hidden sm:inline text-slate-400">•</span>

              <a
                href="mailto:burgess.daniel4141@gmail.com"
                class="underline-offset-2 hover:text-emerald-500 hover:underline"
              >
                burgess.daniel4141@gmail.com
              </a>
              <span class="hidden sm:inline text-slate-400">•</span>

              <a
                href="tel:+18175421326"
                class="underline-offset-2 hover:text-emerald-500 hover:underline"
              >
                +1 (817) 542-1326
              </a>
            </div>

            <!-- Tech stack bullets -->
            <div class="mt-3 flex flex-wrap gap-2 text-[11px] text-slate-600 dark:text-slate-300">
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Laravel and legacy PHP rescue
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Vue 3, TailwindCSS, and Inertia focused UX
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Relational & NoSQL databases: MySQL, PostgreSQL, MongoDB, SQLite
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                React Native and Expo mobile apps
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Database design, complex SQL
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Stripe, Connect, Issuing
              </span>

              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Building and consuming internal and external APIs
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Backend heavy, with strong frontend and mobile experience
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/70 dark:bg-slate-900/80"
              >
                Google Maps API, Mapbox, Leaflet
              </span>

            </div>
          </section>

          <!-- Right: animated "project snapshot" cards + live code -->
          <section class="relative">
            <!-- Halo glow -->
            <div
              class="pointer-events-none absolute -inset-10 rounded-[40px] bg-gradient-to-tr from-blue-500/10 via-emerald-500/5 to-cyan-500/10 blur-3xl dark:from-blue-500/20 dark:via-emerald-500/10 dark:to-cyan-500/20"
            ></div>

            <div class="relative grid gap-4 sm:gap-5">
              <!-- Card 1 -->
              <article
                class="card-enter-2 group relative overflow-hidden rounded-2xl border border-slate-200/90 
                      bg-gradient-to-br from-white via-slate-50 to-slate-100 p-4
                      shadow-2xl shadow-slate-200/80 transition-transform duration-300
                      hover:-translate-y-1.5 hover:border-emerald-400/70 hover:shadow-emerald-500/20
                      dark:border-slate-800/90 dark:bg-gradient-to-br dark:from-slate-900 
                      dark:via-slate-900 dark:to-slate-950 dark:shadow-slate-950/80 
                      dark:hover:shadow-emerald-500/20">

                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    Incident Command Center
                  </h2>

                  <span
                    class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2 py-0.5
                          text-[10px] font-medium text-blue-600 dark:text-blue-300">
                    Portfolio access
                  </span>
                </div>

                <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">
                  Real-time incident dashboard with timeline events, severity filters, follow-ups,
                  micro-interactions, and live-ops-inspired UX built with Laravel, Vue 3,
                  and Inertia.
                </p>

                <div class="mt-3 flex flex-wrap gap-1.5 text-[10px] text-slate-600 dark:text-slate-300">
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Vue 3
                  </span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Inertia
                  </span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Laravel
                  </span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Live Ops UX
                  </span>
                </div>

                <!-- subtle glow element -->
                <div
                  class="pointer-events-none absolute -left-10 bottom-0 h-24 w-24 
                        bg-gradient-to-tr from-emerald-400/20 to-cyan-300/15 blur-2xl">
                </div>
              </article>


              <!-- Card 2 -->
              <article
                class="card-enter-2 group relative overflow-hidden rounded-2xl border border-slate-200/90 
                bg-gradient-to-br from-white via-slate-50 to-slate-100 p-4 shadow-2xl shadow-slate-200/80 
                transition-transform duration-300 hover:-translate-y-1.5 hover:border-blue-400/70 
                hover:shadow-blue-500/20 dark:border-slate-800/90 dark:bg-gradient-to-br dark:from-slate-900 
                dark:via-slate-900 dark:to-slate-950 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/20"
              >
                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    TaskFlow
                  </h2>
                  <span
                    class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-medium text-blue-600 dark:text-blue-300"
                  >
                    Portfolio access
                  </span>
                </div>

                <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">
                  Kanban style platform concept with multi team support, inline task edits, keyboard driven UX, and
                  micro interactions built with Laravel and Vue 3.
                </p>

                <div
                  class="mt-3 flex flex-wrap gap-1.5 text-[10px] text-slate-600 dark:text-slate-300"
                >
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Inertia
                  </span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Vue 3
                  </span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    Tailwind
                  </span>
                  <span class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80">
                    DX focused
                  </span>
                </div>
                <div
                  class="pointer-events-none absolute -left-10 bottom-0 h-24 w-24 bg-gradient-to-tr from-blue-500/20 to-cyan-300/15 blur-2xl"
                ></div>
              </article>

              <!-- Card 3: quick stats -->
              <article
                class="card-enter-3 relative flex flex-col gap-3 rounded-2xl border border-slate-200/90 
                bg-white/90 p-4 shadow-2xl shadow-slate-200/80 backdrop-blur-lg dark:border-slate-800/90 
                dark:bg-slate-900/80 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/20"
              >
                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    What I bring to your team
                  </h2>
                </div>

                <div class="grid grid-cols-3 gap-3 text-center text-xs">
                  <div
                    class="rounded-xl border border-slate-200/80 bg-slate-50 px-2 py-3 dark:border-slate-800/80 dark:bg-slate-900"
                  >
                    <div class="text-lg font-semibold text-emerald-500 dark:text-emerald-300">
                      6+
                    </div>
                    <div class="mt-1 text-[10px] text-slate-600 dark:text-slate-300">
                      Years hands on shipping features
                    </div>
                  </div>
                  <div
                    class="rounded-xl border border-slate-200/80 bg-slate-50 px-2 py-3 dark:border-slate-800/80 dark:bg-slate-900"
                  >
                    <div class="text-lg font-semibold text-cyan-500 dark:text-cyan-300">
                      24/7
                    </div>
                    <div class="mt-1 text-[10px] text-slate-600 dark:text-slate-300">
                      Ops experience with real outages
                    </div>
                  </div>
                  <div class="rounded-xl border border-slate-200/80 bg-slate-50 px-2 py-3 dark:border-slate-800/80 dark:bg-slate-900">
                    <div class="text-lg font-semibold text-blue-500 dark:text-blue-300">
                      900+
                    </div>
                    <div class="mt-1 text-[10px] text-slate-600 dark:text-slate-300">
                      Features and/or updates shipped across platforms
                    </div>
                  </div>

                </div>

                <p class="text-[11px] text-slate-600 dark:text-slate-300">
                  I thrive on untangling legacy code, shipping clean features, and collaborating
                  with design, product, and ops so things work in production, not just on slides.
                </p>
              </article>

              <!-- Card 4: live typing code preview -->
              <article
                class="card-enter-4 relative overflow-hidden rounded-2xl border border-slate-200/90 
                bg-gradient-to-tr from-white via-slate-50 to-slate-100 p-4 shadow-2xl shadow-slate-200/80 
                backdrop-blur-lg min-h-[260px] dark:border-slate-800/90 dark:bg-gradient-to-tr dark:from-slate-900 
                dark:via-slate-950 dark:to-slate-900 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/20"
              >
                <div class="mb-3 flex items-center justify-between gap-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    How I wire features together
                  </h2>
                  <span
                    class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700 dark:bg-slate-800/80 dark:text-slate-200"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Live snippet
                  </span>
                </div>

                <div
                class="code-shell border border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
                >
                    <div
                        class="code-shell-header flex items-center justify-between gap-1 border-b border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-1">
                            <span class="dot dot-red"></span>
                            <span class="dot dot-yellow"></span>
                            <span class="dot dot-green"></span>
                            <span
                                class="code-shell-title text-[0.65rem] uppercase tracking-[0.06em] text-slate-500 dark:text-slate-300"
                            >
                                NewFeature stack
                            </span>
                        </div>
                        <div
                        class="hidden sm:flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400"
                        >
                            <span>web.php</span>
                            <span>·</span>
                            <span>Controller</span>
                            <span>·</span>
                            <span>Service</span>
                            <span>·</span>
                            <span>Migration</span>
                            <span>·</span>
                            <span>Vue</span>
                        </div>
                    </div>

                    <pre
                        class="code-block bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50 text-slate-900 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 dark:text-slate-100"
                    >
                        <code>{{ typedSnippet }}<span v-if="cursorVisible" class="code-cursor">▍</span></code>
                    </pre>
                </div>


              </article>
            </div>
          </section>
        </div>
      </main>

      <!-- How I Build Systems -->
      <section
        id="projects"
        class="border-t border-slate-200/80 bg-slate-50/95 px-6 py-10 sm:px-10 sm:py-12 lg:py-14 dark:border-slate-800/80 dark:bg-slate-950/95"
      >
        <div class="mx-auto flex max-w-6xl flex-col gap-8">
          <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">
                How I Build Systems
              </p>
              <h2 class="mt-1 text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-slate-50">
                Systems that connect UI, backend, and data
              </h2>
              <p class="mt-2 max-w-2xl text-sm text-slate-600 dark:text-slate-300">
                I design schemas, write queries, model domains, and then wrap that in interfaces
                that stay fast and understandable for the people using them every day.
              </p>
            </div>
          </header>

          <div class="grid gap-5 md:grid-cols-3">
            <!-- Card: Operational analytics and schema design -->
            <article
              class="relative flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-xl shadow-slate-200/70 dark:border-slate-800/80 dark:bg-slate-900/90 dark:shadow-slate-950/70"
            >
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  Operational analytics and schema design
                </h3>
                <span
                  class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-600 dark:text-emerald-300"
                >
                  DB and queries
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-300">
                Created durable data models and indexes that tie together product features, background systems, 
                operational workflows, and real-time user experiences.
              </p>
              <ul class="mt-1 space-y-1 text-[11px] text-slate-600 dark:text-slate-300">
                <li>• Normalized schemas with clear foreign keys</li>
                <li>• Aggregate queries for totals, trends, and leaderboards</li>
                <li>• Migrations that are safe to run on live traffic</li>
              </ul>
            </article>

            <!-- Card: End to end feature delivery -->
            <article
              class="relative flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-xl shadow-slate-200/70 dark:border-slate-800/80 dark:bg-slate-900/90 dark:shadow-slate-950/70"
            >
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  End to end feature delivery
                </h3>
                <span
                  class="rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] text-blue-600 dark:text-blue-300"
                >
                  Full stack flows
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-300">
                Take a feature from sketch to production, including routing, controllers, service
                classes, jobs, events, and the Vue or React Native surface that users touch.
              </p>
              <ul class="mt-1 space-y-1 text-[11px] text-slate-600 dark:text-slate-300">
                <li>• Request validation and error handling patterns</li>
                <li>• Transaction boundaries where data really matters</li>
                <li>• Background jobs for slow or external work</li>
              </ul>
            </article>

            <!-- Card: Payments, auth, and integrations -->
            <article
              class="relative flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-xl shadow-slate-200/70 dark:border-slate-800/80 dark:bg-slate-900/90 dark:shadow-slate-950/70"
            >
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  Payments, auth, and integrations
                </h3>
                <span
                  class="rounded-full bg-cyan-500/10 px-2 py-0.5 text-[10px] text-cyan-600 dark:text-cyan-300"
                >
                  Real world edges
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-300">
                Implemented Stripe based flows, token based auth, and external APIs with proper
                webhooks, retries, and audit logs so finance, support, and users can trust the data.
              </p>
              <ul class="mt-1 space-y-1 text-[11px] text-slate-600 dark:text-slate-300">
                <li>• Stripe style payment intents and fee breakdowns</li>
                <li>• Authentication and permissions backed by the DB</li>
                <li>• Webhook handling with idempotency and logging</li>
              </ul>
            </article>
          </div>
        </div>
      </section>

      <!-- How I work -->
      <section
        id="about"
        ref="aboutSection"
        :class="[
            'border-t border-slate-200/80 bg-slate-50 px-6 py-10 sm:px-10 sm:py-12 lg:py-14 dark:border-slate-800/80 dark:bg-slate-950',
            aboutHighlight ? 'about-highlight' : ''
        ]"
      >

        <div class="mx-auto max-w-6xl space-y-8">
          <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">
              How I work
            </p>
            <h2 class="text-2xl sm:text-3xl font-semibold text-slate-900 dark:text-slate-50">
              Full stack thinking, not just single screens
            </h2>
            <p class="max-w-2xl text-sm text-slate-600 dark:text-slate-300">
              I care about data models, queries, and reliability as much as the interface. The goal
              is to ship features that feel smooth on the surface and stay stable when the data grows
              and real users stress the system.
            </p>
          </header>

          <div class="grid gap-8 lg:grid-cols-2">
            <!-- Column: Architecture and data -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Architecture and data decisions I own
              </h3>
              <div
                class="space-y-3 rounded-2xl border border-slate-200 bg-white/90 p-4 text-xs text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900/85 dark:text-slate-300"
              >
                <div class="flex flex-wrap gap-2">
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] dark:bg-slate-800/80">
                    Schema design and indexing
                  </span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] dark:bg-slate-800/80">
                    Migrations and data backfills
                  </span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] dark:bg-slate-800/80">
                    Transactions and consistency
                  </span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] dark:bg-slate-800/80">
                    Caching and query tuning
                  </span>
                  <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] dark:bg-slate-800/80">
                    Background jobs and queues
                  </span>
                </div>
                <p>
                  I think in terms of aggregates, read and write paths, and how a table will feel six
                  months from now when it holds millions of rows, not just how it looks on day one.
                </p>
              </div>
            </div>

            <!-- Column: Collaboration and delivery -->
            <div class="space-y-4">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                How I collaborate across the stack
              </h3>
              <div
                class="space-y-3 rounded-2xl border border-slate-200 bg-white/90 p-4 text-xs text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900/85 dark:text-slate-300"
              >
                <ul class="space-y-1">
                  <li>• Work with product to shape data and success metrics</li>
                  <li>• Fix the 10 percent that causes 90 percent of the headaches</li>
                  <li>• Align with design on states, loading, and errors</li>
                  <li>• Keep ops in the loop on logging and observability</li>
                  <li>• Write docs so future changes are less risky</li>
                </ul>
                <p>
                  Strong teams ship when communication is clear and the code reflects shared
                  decisions. I try to be the person who connects the UI, the database, and the
                  business rules into one coherent story.
                </p>
              </div>
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<style scoped>
@keyframes orb-float {
  0% {
    transform: translate3d(0, 0, 0) scale(1);
  }
  50% {
    transform: translate3d(10px, 20px, 0) scale(1.04);
  }
  100% {
    transform: translate3d(0, 0, 0) scale(1);
  }
}

@keyframes orb-float-delayed {
  0% {
    transform: translate3d(0, 0, 0) scale(1);
  }
  50% {
    transform: translate3d(-12px, -16px, 0) scale(1.06);
  }
  100% {
    transform: translate3d(0, 0, 0) scale(1);
  }
}

@keyframes orb-breathe {
  0% {
    transform: translate3d(0, 10px, 0) scale(0.98);
    opacity: 0.25;
  }
  50% {
    transform: translate3d(0, -6px, 0) scale(1.06);
    opacity: 0.4;
  }
  100% {
    transform: translate3d(0, 10px, 0) scale(0.98);
    opacity: 0.25;
  }
}

@keyframes ping-once {
  0% {
    transform: scale(0.9);
    opacity: 0.7;
  }
  50% {
    transform: scale(1.25);
    opacity: 1;
  }
  100% {
    transform: scale(0.9);
    opacity: 0.7;
  }
}

@keyframes spin-slow {
  0% {
    transform: rotate(0deg);
  }
  100% {
    transform: rotate(360deg);
  }
}

/* Card staggered entrance */
@keyframes card-fade-up {
  0% {
    opacity: 0;
    transform: translateY(18px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.card-enter-1,
.card-enter-2,
.card-enter-3,
.card-enter-4 {
  opacity: 0;
  animation-name: card-fade-up;
  animation-duration: 0.7s;
  animation-timing-function: ease-out;
  animation-fill-mode: forwards;
}

.card-enter-1 {
  animation-delay: 0.15s;
}

.card-enter-2 {
  animation-delay: 0.3s;
}

.card-enter-3 {
  animation-delay: 0.45s;
}

.card-enter-4 {
  animation-delay: 0.6s;
}

/* Code shell */
.code-shell-dark {
  border-radius: 0.9rem;
  background: radial-gradient(circle at top left, #0f172a, #020617);
  border: 1px solid rgba(51, 65, 85, 0.9);
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.9);

  display: flex;
  flex-direction: column;
  height: 210px;
}

/* Code shell layout only */
.code-shell {
  border-radius: 0.9rem;
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.3);

  display: flex;
  flex-direction: column;
  height: 210px;
}

/* Header layout only */
.code-shell-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.35rem;
  padding: 0.4rem 0.75rem;
  flex-shrink: 0;
}

/* Code block layout only */
.code-block {
  margin: 0;
  padding: 0.75rem 0.9rem 0.9rem;
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,
    "Liberation Mono", "Courier New", monospace;
  font-size: 0.7rem;
  line-height: 1.4;

  flex: 1;
  overflow: auto;
  white-space: pre-wrap;
}

/* Subtle, modern scroll bar for the live code snippet */
.code-block::-webkit-scrollbar {
  width: 6px;
}

.code-block::-webkit-scrollbar-track {
  background: transparent;
}

.code-block::-webkit-scrollbar-thumb {
  background: rgba(223, 193, 193, 0.18);
  border-radius: 9999px;
}

.code-block::-webkit-scrollbar-thumb:hover {
  background: rgba(0, 0, 0, 0.28);
}

/* Dark scrollbar tweaks */
:deep(.dark) .code-block::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.18);
}

:deep(.dark) .code-block::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.28);
}

/* Firefox scrollbar */
.code-block {
  scrollbar-width: thin;
  scrollbar-color: rgba(120, 172, 206, 0.596) transparent;
}

:deep(.dark) .code-block {
  scrollbar-color: rgba(223, 204, 204, 0.18) transparent;
}

/* Dots keep their colors */
.dot {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 999px;
  opacity: 0.9;
}

.dot-red {
  background-color: #f97373;
}

.dot-yellow {
  background-color: #facc6b;
}

.dot-green {
  background-color: #4ade80;
}

/* Title no longer sets color, Tailwind handles that */
.code-shell-title {
  margin-left: 0.4rem;
}


.code-block code {
  white-space: pre-wrap;
}

.code-cursor {
  display: inline-block;
  margin-left: 2px;
  color: #22c55e;
}

.animate-orb-float {
  animation: orb-float 18s ease-in-out infinite;
}

.animate-orb-float-delayed {
  animation: orb-float-delayed 22s ease-in-out infinite;
}

.animate-orb-breathe {
  animation: orb-breathe 26s ease-in-out infinite;
}

.animate-ping-once {
  animation: ping-once 1.8s ease-in-out infinite;
}

.animate-spin-slow {
  animation: spin-slow 6s linear infinite;
}

@keyframes about-pop-in {
  0% {
    transform: translateY(14px);
    opacity: 0;
  }
  60% {
    transform: translateY(0);
    opacity: 1;
  }
  100% {
    transform: translateY(0);
    opacity: 1;
  }
}

.about-highlight {
  animation: about-pop-in 0.8s ease-out;
}

</style>
