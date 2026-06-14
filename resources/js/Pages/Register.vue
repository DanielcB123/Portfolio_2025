<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthPageShell from '@/Components/Auth/AuthPageShell.vue'
import AuthCardHeader from '@/Components/Auth/AuthCardHeader.vue'
import { AUTH_PLACEHOLDERS } from '@/constants/authPlaceholders'
import { appRoute } from '@/utils/appRoute'

const props = defineProps({
  teams: { type: Array, default: () => [] },
})

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  team_id: '',
})

const submit = () => {
  form.post(appRoute('register'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <AuthPageShell>
    <Head title="Create account" />

    <AuthCardHeader
      title="TaskFlow"
      subtitle="Create an account for the demo workspace."
      badge="TF"
      tagline="Focus, ship, repeat."
    />

    <form @submit.prevent="submit" class="space-y-4">
      <div>
        <label class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200">
          Full name
        </label>
        <input
          v-model="form.name"
          type="text"
          autocomplete="name"
          required
          class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
          :placeholder="AUTH_PLACEHOLDERS.name"
        />
        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ form.errors.name }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200">
          Email
        </label>
        <input
          v-model="form.email"
          type="email"
          autocomplete="email"
          required
          class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
          :placeholder="AUTH_PLACEHOLDERS.email"
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
          class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50"
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

      <div>
        <label class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200">
          Password
        </label>
        <input
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          required
          class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
          :placeholder="AUTH_PLACEHOLDERS.newPassword"
        />
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ form.errors.password }}
        </p>
      </div>

      <div>
        <label class="mb-1 block text-xs font-medium text-slate-800 dark:text-slate-200">
          Confirm password
        </label>
        <input
          v-model="form.password_confirmation"
          type="password"
          autocomplete="new-password"
          required
          class="w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50 dark:placeholder-slate-500"
          :placeholder="AUTH_PLACEHOLDERS.confirmPassword"
        />
      </div>

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

    <div
      class="mt-5 border-t border-slate-200 pt-4 text-center text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400"
    >
      <p>
        Already have an account?
        <Link
          :href="route('login')"
          class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
        >
          Sign in
        </Link>
      </p>

      <p class="mt-2">
        Or
        <Link
          :href="route('incident.register')"
          class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
        >
          use the Incident Command register
        </Link>
      </p>

      <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
        This TaskFlow demo is a real portfolio build with Laravel, Vue, and Inertia.
      </p>
    </div>
  </AuthPageShell>
</template>
