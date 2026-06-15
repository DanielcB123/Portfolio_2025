<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useVerticalResize } from '@/Composables/useVerticalResize';

const page = usePage();
const user = page.props.auth?.user;

// THEME TOGGLE
const theme = ref('dark');
const aboutSection = ref(null)
const aboutHighlight = ref(false)
const wireFeaturesCard = ref(null)
const wireFeaturesTrigger = ref(null)
const wireFeaturesHighlighted = ref(false)

const dismissWireFeaturesHighlight = (event) => {
  if (!wireFeaturesHighlighted.value) return

  const target = event.target
  if (
    wireFeaturesCard.value?.contains(target) ||
    wireFeaturesTrigger.value?.contains(target)
  ) {
    return
  }

  wireFeaturesHighlighted.value = false
}

const scrollToRef = (targetRef, options = { behavior: 'smooth', block: 'center' }) => {
  if (typeof window === 'undefined') return

  targetRef.value?.scrollIntoView(options)
}

const scrollToWork = () => {
  scrollToRef(wireFeaturesCard)
  wireFeaturesHighlighted.value = true
}

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

// Typed code snippet tabs for the "live editor" card
const codeTabs = [
  {
    id: 'vue',
    label: 'Vue',
    code: `// resources/js/Pages/NewFeature/Index.vue
<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
  items: Object,
  filters: Object,
});

const form = useForm({
  title: '',
  meta: {},
});

const submit = () => {
  form.post(route('new-feature.store'));
};
<\/script>

<template>
  <Head title="New Feature" />

  <form @submit.prevent="submit">
    <input v-model="form.title" type="text" />
    <button type="submit" :disabled="form.processing">
      Create item
    </button>
  </form>
</template>`,
  },
  {
    id: 'web-php',
    label: 'web.php',
    code: `// routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/new-feature', [NewFeatureController::class, 'index'])
        ->name('new-feature.index');

    Route::post('/new-feature', [NewFeatureController::class, 'store'])
        ->name('new-feature.store');
});`,
  },
  {
    id: 'middleware',
    label: 'Middleware',
    code: `// app/Http/Middleware/EnsureNewFeatureAccess.php
class EnsureNewFeatureAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->hasVerifiedEmail() || ! $user->can('access-new-feature')) {
            abort(403, 'You do not have access to this feature.');
        }

        return $next($request);
    }
}`,
  },
  {
    id: 'controller',
    label: 'Controller',
    code: `// app/Http/Controllers/NewFeatureController.php
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
}`,
  },
  {
    id: 'service',
    label: 'Service',
    code: `// app/Services/NewFeatureService.php
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
}`,
  },
  {
    id: 'model',
    label: 'Model',
    code: `// app/Models/NewFeature.php
class NewFeature extends Model
{
    protected $fillable = ['user_id', 'title', 'status', 'meta'];

    protected $casts = [
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }
}`,
  },
  {
    id: 'migration',
    label: 'Migration',
    code: `// database/migrations/2025_01_01_000000_create_new_features_table.php
Schema::create('new_features', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->string('status')->default('open')->index();
    $table->json('meta')->nullable();
    $table->timestamps();
});`,
  },
];

const activeCodeTab = ref(codeTabs[0].id);
const typedSnippet = ref('');
const cursorVisible = ref(true);

const {
  height: codeShellHeight,
  start: startCodeShellResize,
  load: loadCodeShellHeight,
} = useVerticalResize({
  storageKey: 'wire-features-code-height',
  defaultHeight: 225,
  minHeight: 180,
  maxHeight: 560,
});

let typeTimer = null;
let cursorTimer = null;
let typeIndex = 0;

const clearTypeTimer = () => {
  if (typeTimer) {
    window.clearInterval(typeTimer);
    typeTimer = null;
  }
};

const getActiveTabCode = () =>
  codeTabs.find((tab) => tab.id === activeCodeTab.value)?.code ?? '';

const startTypingEffect = () => {
  clearTypeTimer();
  typedSnippet.value = '';
  typeIndex = 0;

  const code = getActiveTabCode();

  typeTimer = window.setInterval(() => {
    if (typeIndex < code.length) {
      typedSnippet.value += code[typeIndex];
      typeIndex += 1;
    } else {
      clearTypeTimer();
    }
  }, 22);
};

const selectCodeTab = (tabId) => {
  if (activeCodeTab.value === tabId) return;

  activeCodeTab.value = tabId;
  startTypingEffect();
};

onMounted(() => {
  loadCodeShellHeight();

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

  startTypingEffect();

  cursorTimer = window.setInterval(() => {
    cursorVisible.value = !cursorVisible.value;
  }, 550);

  document.addEventListener('click', dismissWireFeaturesHighlight);
});

onBeforeUnmount(() => {
  clearTypeTimer();
  if (cursorTimer) window.clearInterval(cursorTimer);
  document.removeEventListener('click', dismissWireFeaturesHighlight);
});
</script>

<template>
  <div
    class="welcome-page relative isolate w-full min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <!-- Glowing gradient orbs -->
    <div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
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
      class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.35)_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.7)_0,_rgba(15,23,42,1)_55%)]"
      aria-hidden="true"
    ></div>
    <div
      class="pointer-events-none fixed inset-0 -z-10 opacity-30 mix-blend-soft-light [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:56px_56px] dark:[background-image:linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)]"
      aria-hidden="true"
    ></div>

    <!-- Content -->
    <div class="relative w-full min-w-0">
      <!-- Top nav / badge -->
<header class="page-gutter pt-4 sm:pt-5">
  <div class="page-shell pt-2.5">
    <!-- Mobile header: compact single row -->
    <div class="flex items-center justify-between gap-3 sm:hidden">
      <div class="flex min-w-0 flex-1 items-center gap-2.5">
        <span
          class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 text-xs font-semibold text-slate-950"
        >
          DB
        </span>
        <div class="min-w-0 leading-tight">
          <div class="truncate text-sm font-semibold tracking-wide text-slate-800 dark:text-slate-100">
            Daniel Burgess
          </div>
          <div class="truncate text-[11px] text-slate-500 dark:text-slate-400">
            Full stack engineer
          </div>
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-2">
        <button
          type="button"
          aria-label="Toggle theme"
          class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-300/80 bg-white/70 text-slate-700 shadow shadow-slate-200/60 backdrop-blur transition hover:border-emerald-400/80 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300"
          @click="applyTheme(theme === 'dark' ? 'light' : 'dark')"
        >
          <span v-if="theme === 'dark'">☾</span>
          <span v-else>☀</span>
        </button>
        <span
          class="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 shadow shadow-emerald-400/70"
          title="Portfolio live"
          aria-hidden="true"
        />
      </div>
    </div>

    <!-- Desktop header: full pill layout -->
    <div class="hidden items-center justify-between sm:flex">
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

      <div class="flex items-center gap-2 sm:gap-3">
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

        <div
          class="hidden sm:flex items-center gap-1 text-[11px] text-slate-500 dark:text-slate-300"
        >
          <span
            class="h-2 w-2 rounded-full bg-emerald-400 shadow shadow-emerald-400/70 animate-pulse"
          ></span>
          <span>Available for new opportunities</span>
        </div>

        <div
          class="inline-flex items-center gap-2 rounded-full border border-slate-300/70 bg-white/80 px-3 py-1.5 text-[10px] uppercase tracking-[0.18em] text-slate-600 shadow-lg shadow-slate-200/70 dark:border-slate-700/70 dark:bg-slate-950/70 dark:text-slate-400 dark:shadow-slate-900/80"
        >
          <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
          <span>Portfolio</span>
        </div>
      </div>
    </div>
  </div>
</header>


      <!-- Main hero -->
      <main class="page-gutter w-full pb-10 pt-4 sm:pt-8 lg:pb-14">
        <div
          class="page-shell grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1.1fr)] lg:items-center"
        >
          <!-- Left: headline and CTAs -->
          <section class="w-full min-w-0 space-y-6 sm:space-y-7">
            <p
              class="flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-2.5 text-center text-[10px] uppercase leading-snug tracking-[0.12em] text-slate-600 shadow shadow-blue-500/10 sm:inline-flex sm:w-auto sm:rounded-full sm:px-3 sm:py-1 sm:text-[11px] sm:tracking-[0.18em] dark:border-slate-700/70 dark:bg-slate-900/70 dark:text-slate-300"
            >
              <span
                class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-400 animate-ping-once"
              ></span>
              <span>Turning complex problems into sharp, reliable products</span>
            </p>

            <div class="space-y-4 sm:space-y-5">
              <h1
                class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight leading-tight text-slate-900 dark:text-slate-50"
              >
                I build
                <span class="relative sm:whitespace-nowrap">
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

              <p class="w-full text-sm sm:max-w-xl sm:text-base text-slate-600 dark:text-slate-300">
                I build production software across the full stack with Laravel, Vue, React, React Native,
                and modern PHP. My work combines strong MySQL, schema design, API development,
                and legacy system modernization, including spaghetti code refactoring. I have built
                and supported operational systems for insurance workflows, engineering workflows, dispatch,
                billing, CRM, internal dashboards, and field service technician mobile applications in
                data heavy environments. I also handle the practical side of shipping software with Docker, CI/CD pipelines,
                GitHub Actions, Render, and AWS infrastructure such as EC2 (Server), S3 (Storage), RDS (DB),
                IAM (Access Management), Route 53 (DNS), and related cloud services.
              </p>
            </div>

            <!-- Hero CTAs: stacked on mobile, centered row on tablet, left-aligned from lg -->
            <div class="flex w-full flex-col gap-4">
              <div
                class="flex w-full flex-col gap-3 md:flex-row md:flex-wrap md:items-center md:justify-center md:gap-x-4 md:gap-y-3 lg:justify-start"
              >
                <a
                  ref="wireFeaturesTrigger"
                  href="#wire-features"
                  @click.prevent="scrollToWork"
                  class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl bg-gradient-to-r from-blue-500 via-emerald-400 to-cyan-400 px-5 py-3.5 text-sm font-semibold text-slate-950 shadow-xl shadow-emerald-500/25 transition hover:shadow-2xl hover:shadow-emerald-500/40 md:w-auto md:rounded-full md:px-5 md:py-2.5 lg:px-6"
                >
                  See how I work
                  <span
                    class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-950/10 text-[11px]"
                  >
                    →
                  </span>
                </a>

                <a
                  href="#about"
                  @click.prevent="scrollToAbout"
                  class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl border border-slate-300/80 bg-white/80 px-5 py-3.5 text-sm font-medium text-slate-900 shadow-lg shadow-slate-200/30 transition hover:border-emerald-400/80 hover:bg-white hover:shadow-emerald-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-emerald-400/80 dark:hover:bg-slate-900 dark:hover:shadow-emerald-500/30 md:w-auto md:rounded-full md:px-5 md:py-2.5 lg:px-6"
                >
                  Why I am a great hire
                </a>

                <Link
                  href="/about"
                  class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl border border-slate-300/80 bg-white/80 px-5 py-3.5 text-sm font-medium text-slate-900 shadow-lg transition hover:border-emerald-400/80 hover:bg-white hover:shadow-emerald-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-emerald-400/80 dark:hover:bg-slate-900 dark:hover:shadow-emerald-500/30 md:w-auto md:rounded-full md:px-5 md:py-2.5 lg:px-6"
                >
                  About my background
                </Link>
              </div>

              <!-- Demo section divider -->
              <div class="flex items-center gap-3 py-0.5">
                <span class="h-px flex-1 bg-slate-300/80 dark:bg-slate-700/80" aria-hidden="true" />
                <span class="shrink-0 text-[11px] uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">
                  Explore demos
                </span>
                <span class="h-px flex-1 bg-slate-300/80 dark:bg-slate-700/80" aria-hidden="true" />
              </div>

              <!-- Demo links: stacked on mobile, centered row on tablet -->
              <div
                class="flex w-full flex-col gap-3 md:flex-row md:flex-wrap md:items-center md:justify-center md:gap-x-3 md:gap-y-3 lg:justify-start"
              >
                <template v-if="user">
                  <Link
                    href="/dashboard"
                    class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-3.5 text-sm text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30 md:w-auto md:rounded-full md:py-2 md:text-xs"
                  >
                    TaskFlow Dashboard
                  </Link>

                  <Link
                    href="/incident-command"
                    class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-3.5 text-sm text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30 md:w-auto md:rounded-full md:py-2 md:text-xs"
                  >
                    <span class="md:hidden">Incident Command</span>
                    <span class="hidden md:inline">Incident Command Center Dashboard</span>
                  </Link>

                  <Link
                    href="/orbital-dodge"
                    class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-3.5 text-sm text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30 md:w-auto md:rounded-full md:py-2 md:text-xs"
                  >
                    Orbital Dodge Game
                  </Link>
                </template>

                <template v-else>
                  <Link
                    href="/login"
                    class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-3.5 text-sm text-slate-900 shadow-lg transition hover:border-emerald-400/80 hover:bg-white hover:shadow-emerald-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-emerald-400/80 dark:hover:bg-slate-900 dark:hover:shadow-emerald-500/30 md:w-auto md:rounded-full md:py-2 md:text-xs"
                  >
                    <span class="md:hidden">TaskFlow demo</span>
                    <span class="hidden md:inline">Log in to TaskFlow demo</span>
                  </Link>

                  <Link
                    href="/incident-command/login"
                    class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-3.5 text-sm text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30 md:w-auto md:rounded-full md:py-2 md:text-xs"
                  >
                    <span class="md:hidden">Incident Command demo</span>
                    <span class="hidden md:inline">Log in to Incident Command Center Demo</span>
                  </Link>

                  <Link
                    href="/orbital-dodge"
                    class="inline-flex min-h-[3rem] w-full items-center justify-center gap-2 rounded-2xl border border-slate-300/80 bg-white/80 px-4 py-3.5 text-sm text-slate-900 shadow-lg transition hover:border-blue-400/80 hover:bg-white hover:shadow-blue-500/20 dark:border-slate-700/80 dark:bg-slate-900/70 dark:text-slate-100 dark:hover:border-blue-400/80 dark:hover:bg-slate-900 dark:hover:shadow-blue-500/30 md:w-auto md:rounded-full md:py-2 md:text-xs"
                  >
                    Orbital Dodge Game
                  </Link>
                </template>
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
          <section class="relative w-full min-w-0 overflow-hidden">
            <!-- Halo glow -->
            <div
              class="pointer-events-none absolute -inset-4 rounded-[40px] bg-gradient-to-tr from-blue-500/10 via-emerald-500/5 to-cyan-500/10 blur-3xl sm:-inset-10 dark:from-blue-500/20 dark:via-emerald-500/10 dark:to-cyan-500/20"
            ></div>

            <div class="relative grid w-full min-w-0 gap-4 sm:gap-5">
              <!-- Card 1 -->
              <article
                class="card-enter-2 group relative w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200/90 
                      bg-gradient-to-br from-white via-slate-50 to-slate-100 p-4
                      shadow-2xl shadow-slate-200/80 transition-transform duration-300
                      hover:-translate-y-1.5 hover:border-emerald-400/70 hover:shadow-emerald-500/20
                      dark:border-slate-800/90 dark:bg-gradient-to-br dark:from-slate-900 
                      dark:via-slate-900 dark:to-slate-950 dark:shadow-slate-950/80 
                      dark:hover:shadow-emerald-500/20">

                <div class="flex min-w-0 flex-wrap items-start justify-between gap-2 sm:gap-4">
                  <h2 class="min-w-0 text-sm font-semibold text-slate-900 dark:text-slate-50">
                    Incident Command Center
                  </h2>

                  <span
                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-blue-500/10 px-2 py-0.5
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
                  class="pointer-events-none absolute -left-4 bottom-0 h-24 w-24 
                        bg-gradient-to-tr from-emerald-400/20 to-cyan-300/15 blur-2xl sm:-left-10">
                </div>
              </article>


              <!-- Card 2 -->
              <article
                class="card-enter-2 group relative w-full min-w-0 overflow-hidden rounded-2xl border border-slate-200/90 
                bg-gradient-to-br from-white via-slate-50 to-slate-100 p-4 shadow-2xl shadow-slate-200/80 
                transition-transform duration-300 hover:-translate-y-1.5 hover:border-blue-400/70 
                hover:shadow-blue-500/20 dark:border-slate-800/90 dark:bg-gradient-to-br dark:from-slate-900 
                dark:via-slate-900 dark:to-slate-950 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/20"
              >
                <div class="flex min-w-0 flex-wrap items-start justify-between gap-2 sm:gap-4">
                  <h2 class="min-w-0 text-sm font-semibold text-slate-900 dark:text-slate-50">
                    TaskFlow
                  </h2>
                  <span
                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-medium text-blue-600 dark:text-blue-300"
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
                  class="pointer-events-none absolute -left-4 bottom-0 h-24 w-24 bg-gradient-to-tr from-blue-500/20 to-cyan-300/15 blur-2xl sm:-left-10"
                ></div>
              </article>

              <!-- Card 3: quick stats -->
              <article
                class="card-enter-3 relative flex w-full min-w-0 flex-col gap-3 overflow-hidden rounded-2xl border border-slate-200/90 
                bg-white/90 p-4 shadow-2xl shadow-slate-200/80 backdrop-blur-lg dark:border-slate-800/90 
                dark:bg-slate-900/80 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/20"
              >
                <div class="flex min-w-0 items-center justify-between gap-4">
                  <h2 class="min-w-0 text-sm font-semibold text-slate-900 dark:text-slate-50">
                    What I bring to your team
                  </h2>
                </div>

                <div class="grid min-w-0 grid-cols-1 gap-3 text-center text-xs sm:grid-cols-3">
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
                id="wire-features"
                ref="wireFeaturesCard"
                :class="[
                  'card-enter-4 group relative w-full min-w-0 scroll-mt-24 overflow-hidden rounded-2xl border bg-gradient-to-tr from-white via-slate-50 to-slate-100 p-4 shadow-2xl backdrop-blur-lg min-h-[260px] transition-all duration-500 ease-out hover:-translate-y-1.5 hover:border-emerald-400/70 hover:shadow-emerald-500/20 dark:bg-gradient-to-tr dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/20',
                  wireFeaturesHighlighted
                    ? '-translate-y-2.5 border-emerald-400/90 shadow-[0_25px_50px_-12px_rgba(16,185,129,0.5)] dark:border-emerald-400/80 dark:shadow-[0_25px_50px_-12px_rgba(16,185,129,0.4)]'
                    : 'border-slate-200/90 shadow-slate-200/80 dark:border-slate-800/90',
                ]"
              >
                <div
                  class="pointer-events-none absolute inset-0 rounded-2xl transition-opacity duration-500"
                  :class="wireFeaturesHighlighted ? 'opacity-100' : 'opacity-0'"
                  aria-hidden="true"
                >
                  <div
                    class="absolute inset-0 rounded-2xl border-2 border-emerald-400/60 shadow-[0_0_55px_rgba(16,185,129,0.45),0_0_25px_rgba(34,211,238,0.25)]"
                  />
                </div>

                <div class="relative min-w-0">
                <div class="mb-3 flex min-w-0 flex-wrap items-start justify-between gap-2 sm:gap-4">
                  <h2 class="min-w-0 text-sm font-semibold text-slate-900 dark:text-slate-50">
                    How I wire features together
                  </h2>
                  <span
                    class="inline-flex shrink-0 items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-700 dark:bg-slate-800/80 dark:text-slate-200"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Live snippet
                  </span>
                </div>

                <div
                class="code-shell w-full min-w-0 max-w-full border border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-50"
                :style="{ height: `${codeShellHeight}px` }"
                >
                    <div
                        class="code-shell-header border-b border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="flex items-center gap-1 border-b border-slate-200/80 px-2 py-1.5 dark:border-slate-700/80">
                            <span class="dot dot-red"></span>
                            <span class="dot dot-yellow"></span>
                            <span class="dot dot-green"></span>
                            <span
                                class="code-shell-title text-[0.65rem] uppercase tracking-[0.06em] text-slate-500 dark:text-slate-300"
                            >
                                NewFeature stack
                            </span>
                        </div>
                        <nav
                          class="code-tab-nav flex min-w-0 flex-nowrap items-center justify-start gap-0.5 overflow-x-auto px-2 py-1.5 text-[9px] sm:justify-center sm:text-[10px]"
                          aria-label="Code snippet layers"
                        >
                          <template v-for="(tab, index) in codeTabs" :key="tab.id">
                            <span
                              v-if="index > 0"
                              class="shrink-0 px-0.5 text-slate-400 dark:text-slate-600"
                              aria-hidden="true"
                            >
                              ·
                            </span>
                            <button
                              type="button"
                              :aria-current="activeCodeTab === tab.id ? 'page' : undefined"
                              class="shrink-0 whitespace-nowrap rounded px-1 py-0.5 transition sm:px-1.5"
                              :class="
                                activeCodeTab === tab.id
                                  ? 'bg-emerald-500/15 font-medium text-emerald-600 dark:text-emerald-300'
                                  : 'text-slate-500 hover:text-emerald-500 dark:text-slate-400 dark:hover:text-emerald-300'
                              "
                              @click="selectCodeTab(tab.id)"
                            >
                              {{ tab.label }}
                            </button>
                          </template>
                        </nav>
                    </div>

                    <pre
                        class="code-block bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50 text-slate-900 dark:from-slate-900 dark:via-slate-950 dark:to-slate-900 dark:text-slate-100"
                    >
                        <code>{{ typedSnippet }}<span v-if="cursorVisible" class="code-cursor">▍</span></code>
                    </pre>

                    <div
                      class="code-shell-resize-handle"
                      role="separator"
                      aria-orientation="horizontal"
                      aria-label="Resize code panel"
                      title="Drag to resize"
                      @mousedown.prevent="startCodeShellResize"
                      @touchstart="startCodeShellResize"
                    >
                      <span class="code-shell-resize-grip" aria-hidden="true" />
                    </div>
                </div>

                </div>
              </article>
            </div>
          </section>
        </div>
      </main>

      <!-- How I Build Systems -->
      <section
        id="projects"
        class="page-gutter border-t border-slate-200/80 bg-slate-50/95 py-10 sm:py-12 lg:py-14 dark:border-slate-800/80 dark:bg-slate-950/95"
      >
        <div class="page-shell flex flex-col gap-8">
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
            <!-- Card: Production data models -->
            <article
              class="relative flex flex-col gap-3 rounded-2xl border border-slate-200/80 bg-white/90 p-4 shadow-xl shadow-slate-200/70 dark:border-slate-800/80 dark:bg-slate-900/90 dark:shadow-slate-950/70"
            >
              <div class="flex items-center justify-between gap-3">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  Production data models
                </h3>
                <span
                  class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] text-emerald-600 dark:text-emerald-300"
                >
                  DB and queries
                </span>
              </div>
              <p class="text-xs text-slate-600 dark:text-slate-300">
                Built and maintained data models behind real business workflows, including insurance operations,
                dispatch, billing, CRM activity, recruiting tools, dashboards, and technicians mobile applications running
                in data heavy environments.
              </p>
              <ul class="mt-1 space-y-1 text-[11px] text-slate-600 dark:text-slate-300">
                <li>• Relational schemas with clear ownership and foreign keys</li>
                <li>• Reporting queries, operational dashboards, and workflow backed data</li>
                <li>• Production safe migrations, backfills, and deployment aware changes</li>
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
            'page-gutter border-t border-slate-200/80 bg-slate-50 py-10 sm:py-12 lg:py-14 dark:border-slate-800/80 dark:bg-slate-950',
            aboutHighlight ? 'about-highlight' : ''
        ]"
      >

        <div class="page-shell space-y-8">
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
.welcome-page {
  touch-action: pan-y;
}

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
  height: 225px;
}

/* Code shell layout only */
.code-shell {
  border-radius: 0.9rem;
  overflow: hidden;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.3);

  display: flex;
  flex-direction: column;
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

.code-shell-resize-handle {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  height: 0.75rem;
  cursor: ns-resize;
  border-top: 1px solid rgba(148, 163, 184, 0.22);
  background: rgba(148, 163, 184, 0.06);
  transition: background 0.15s ease;
}

.code-shell-resize-handle:hover {
  background: rgba(16, 185, 129, 0.12);
}

.code-shell-resize-grip {
  width: 2.25rem;
  height: 0.2rem;
  border-radius: 999px;
  background: rgba(148, 163, 184, 0.45);
}

:deep(.dark) .code-shell-resize-handle {
  border-top-color: rgba(71, 85, 105, 0.65);
  background: rgba(15, 23, 42, 0.55);
}

:deep(.dark) .code-shell-resize-handle:hover {
  background: rgba(16, 185, 129, 0.18);
}

:deep(.dark) .code-shell-resize-grip {
  background: rgba(148, 163, 184, 0.55);
}

/* Header layout only */
.code-shell-header {
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  min-width: 0;
}

.code-tab-nav {
  scrollbar-width: none;
}

.code-tab-nav::-webkit-scrollbar {
  display: none;
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
  min-width: 0;
  overflow: auto;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
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
