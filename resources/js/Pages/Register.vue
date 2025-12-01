<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ref, onMounted } from 'vue'

const props = defineProps({
  teams: { type: Array, default: () => [] },
});

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  team_id: '',
});


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
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <Head title="Create account" />

    <!-- Subtle grid background -->
    <div
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.35)_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.7)_0,_rgba(15,23,42,1)_55%)]"
    ></div>
    <div
      class="pointer-events-none absolute inset-0 opacity-30 mix-blend-soft-light [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:56px_56px] dark:[background-image:linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)]"
    ></div>

    <!-- CONTENT -->
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

        <!-- Glow behind card -->
        <div
          class="pointer-events-none absolute -inset-10 rounded-[32px] bg-gradient-to-tr from-blue-500/25 via-emerald-400/15 to-cyan-400/20 blur-3xl"
        ></div>

        <!-- Form card -->
        <div
          class="relative z-10 rounded-2xl border border-slate-200 bg-white/95 px-6 py-7 shadow-2xl shadow-slate-200/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-slate-950/80"
        >
          <!-- Header -->
          <div class="mb-6 flex items-center justify-between">
            <div>
              <h1 class="text-base sm:text-lg font-semibold text-slate-900 dark:text-slate-50">
                Create your TaskFlow account
              </h1>
              <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                Spin up a demo workspace and showcase your workflow.
              </p>
            </div>

            <span
              class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-emerald-400 text-xs font-bold text-slate-950"
            >
              TF
            </span>
          </div>

          <form @submit.prevent="submit" class="space-y-4">
            <!-- Name -->
            <div>
              <label
                class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200"
              >Name</label>
              <input
                v-model="form.name"
                type="text"
                autocomplete="name"
                placeholder="Daniel Burgess"
                class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
              />
              <p v-if="form.errors.name" class="mt-1 text-xs text-red-500 dark:text-red-400">
                {{ form.errors.name }}
              </p>
            </div>

            <!-- Email -->
            <div>
              <label
                class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200"
              >Email</label>
              <input
                v-model="form.email"
                type="email"
                autocomplete="email"
                placeholder="you@example.com"
                class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
              />
              <p v-if="form.errors.email" class="mt-1 text-xs text-red-500 dark:text-red-400">
                {{ form.errors.email }}
              </p>
            </div>

          <div v-if="props.teams.length">
            <label class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200">
              Team
            </label>
            <select
              v-model="form.team_id"
              class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm
                    text-slate-900 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400
                    dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50"
            >
              <option value="" disabled>Select a team</option>

              <option
                v-for="team in props.teams"
                :key="team.id"
                :value="String(team.id)"
              >
                {{ team.name }}
              </option>
            </select>


          </div>


            <!-- Password -->
            <div>
              <label
                class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200"
              >Password</label>
              <input
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                placeholder="At least 8 characters"
                class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
              />
              <p v-if="form.errors.password" class="mt-1 text-xs text-red-500 dark:text-red-400">
                {{ form.errors.password }}
              </p>
            </div>

            <!-- Confirm password -->
            <div>
              <label
                class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200"
              >Confirm password</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                placeholder="Repeat your password"
                class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
              />
            </div>

            <!-- Submit -->
            <button
              :disabled="form.processing"
              type="submit"
              class="mt-1 inline-flex w-full items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 via-emerald-400 to-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-emerald-500/30 transition hover:translate-y-0.5 hover:shadow-2xl hover:shadow-emerald-500/40 disabled:cursor-not-allowed disabled:opacity-70"
            >
              <span v-if="!form.processing">Create account</span>
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
                Creating your workspace...
              </span>
            </button>
          </form>

          <!-- Footer -->
          <div
            class="mt-5 border-t border-slate-200 pt-4 text-center text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400"
          >
            <p>
              Already have an account?
              <Link
                href="/login"
                class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
              >
                Log in
              </Link>
            </p>
            <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
              This TaskFlow demo is a real portfolio build with Laravel, Vue, and Inertia.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animations reused from welcome and login to keep continuity */
@keyframes orb-float {
  0% { transform: translate3d(0, 0, 0) scale(1); }
  50% { transform: translate3d(10px, 20px, 0) scale(1.04); }
  100% { transform: translate3d(0, 0, 0) scale(1); }
}

@keyframes orb-float-delayed {
  0% { transform: translate3d(0, 0, 0) scale(1); }
  50% { transform: translate3d(-12px, -16px, 0) scale(1.06); }
  100% { transform: translate3d(0, 0, 0) scale(1); }
}

@keyframes orb-breathe {
  0% { transform: translate3d(0, 10px, 0) scale(0.98); opacity: 0.25; }
  50% { transform: translate3d(0, -6px, 0) scale(1.06); opacity: 0.4; }
  100% { transform: translate3d(0, 10px, 0) scale(0.98); opacity: 0.25; }
}

.animate-orb-float { animation: orb-float 18s ease-in-out infinite; }
.animate-orb-float-delayed { animation: orb-float-delayed 22s ease-in-out infinite; }
.animate-orb-breathe { animation: orb-breathe 26s ease-in-out infinite; }
</style>
