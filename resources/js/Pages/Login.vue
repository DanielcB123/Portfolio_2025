<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

defineProps({
  canResetPassword: {
    type: Boolean,
    default: false,
  },
  status: {
    type: String,
    default: '',
  },
})

const page = usePage()

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const theme = ref('dark')

const applyTheme = (value) => {
  theme.value = value

  if (typeof window === 'undefined') return

  const root = window.document.documentElement

  if (value === 'dark') {
    root.classList.add('dark')
  } else {
    root.classList.remove('dark')
  }

  window.localStorage.setItem('theme', value)
}

onMounted(() => {
  if (typeof window === 'undefined') return

  const stored = window.localStorage.getItem('theme')
  if (stored === 'light' || stored === 'dark') {
    applyTheme(stored)
  } else {
    const prefersDark =
      window.matchMedia &&
      window.matchMedia('(prefers-color-scheme: dark)').matches
    applyTheme(prefersDark ? 'dark' : 'light')
  }
})

const submit = () => {
  form.post('login', {
    onSuccess: () => {
      const user = page.props.auth?.user
      if (user && user.api_key) {
        localStorage.setItem('api_key', user.api_key)
      }
    },
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <Head title="Log in" />

    <!-- Subtle grid background -->
    <div
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.35)_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.7)_0,_rgba(15,23,42,1)_55%)]"
    ></div>
    <div
      class="pointer-events-none absolute inset-0 opacity-30 mix-blend-soft-light [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:56px_56px] dark:[background-image:linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)]"
    ></div>

    <!-- Content -->
    <div class="relative z-10 flex min-h-screen items-center justify-center px-4">
      <div class="relative w-full max-w-md">
        <!-- Back to home / portfolio button -->
        <div class="mb-5 flex justify-center">
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

        <!-- Halo glow behind card -->
        <div
          class="pointer-events-none absolute -inset-10 rounded-[32px] bg-gradient-to-tr from-blue-500/25 via-emerald-400/15 to-cyan-400/20 blur-3xl"
        ></div>

        <div
          class="relative rounded-2xl border border-slate-200 bg-white/95 px-6 py-7 shadow-2xl shadow-slate-200/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-slate-950/80"
        >
          <!-- Mini header to echo welcome page identity -->
          <div class="mb-6 flex items-center justify-between">
            <div>
              <h1 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                Welcome back
              </h1>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                Log in to your TaskFlow workspace.
              </p>
            </div>
            <div
              class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-[11px] text-slate-700 dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300"
            >
              <span
                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 text-[10px] font-semibold text-slate-950"
              >
                TF
              </span>
              <span class="hidden sm:inline">
                Focus, ship, repeat.
              </span>
            </div>
          </div>

          <!-- Status -->
          <div
            v-if="status"
            class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs font-medium text-emerald-700 dark:text-emerald-300"
          >
            {{ status }}
          </div>

          <!-- Form -->
          <form @submit.prevent="submit" class="space-y-4">
            <!-- Email -->
            <div>
              <label
                for="email"
                class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200"
              >
                Email
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                required
                class="block w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
                placeholder="you@example.com"
              />
              <p v-if="form.errors.email" class="mt-1 text-xs text-red-500 dark:text-red-400">
                {{ form.errors.email }}
              </p>
            </div>

            <!-- Password -->
            <div>
              <label
                for="password"
                class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200"
              >
                Password
              </label>
              <input
                id="password"
                v-model="form.password"
                type="password"
                autocomplete="current-password"
                required
                class="block w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
                placeholder="Your password"
              />
              <p v-if="form.errors.password" class="mt-1 text-xs text-red-500 dark:text-red-400">
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Remember + Forgot -->
            <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
              <label class="inline-flex items-center gap-2">
                <input
                  type="checkbox"
                  v-model="form.remember"
                  class="h-4 w-4 rounded border-slate-300 bg-white text-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900"
                />
                <span>Remember me</span>
              </label>

              <Link
                v-if="canResetPassword"
                href="route('password.request')"
                class="text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
              >
                Forgot password?
              </Link>
            </div>

            <!-- Submit -->
            <button
              type="submit"
              :disabled="form.processing"
              class="mt-1 inline-flex w-full items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 via-emerald-400 to-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-emerald-500/30 transition hover:translate-y-0.5 hover:shadow-2xl hover:shadow-emerald-500/40 disabled:cursor-not-allowed disabled:opacity-70"
            >
              <span v-if="!form.processing">Log in</span>
              <span v-else class="inline-flex items-center gap-2">
                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                  <circle
                    class="opacity-25"
                    cx="12"
                    cy="12"
                    r="10"
                    stroke="currentColor"
                    stroke-width="4"
                    fill="none"
                  />
                  <path
                    class="opacity-90"
                    fill="currentColor"
                    d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4z"
                  />
                </svg>
                Signing you in...
              </span>
            </button>
          </form>

          <!-- Divider and register link -->
          <div
            class="mt-5 border-t border-slate-200 pt-4 text-center text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400"
          >
            <p>
              Need an account?
              <Link
                href="/register"
                class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
              >
                Create one for the TaskFlow demo
              </Link>
            </p>

            <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
              Built with Laravel, Vue, and Inertia as a real world portfolio project.
            </p>
          </div>
        </div>
      </div>
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

.animate-orb-float {
  animation: orb-float 18s ease-in-out infinite;
}

.animate-orb-float-delayed {
  animation: orb-float-delayed 22s ease-in-out infinite;
}

.animate-orb-breathe {
  animation: orb-breathe 26s ease-in-out infinite;
}
</style>
