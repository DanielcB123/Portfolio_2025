<script setup>
import { Head, Link } from '@inertiajs/vue3'
import AuthPageShell from '@/Components/Auth/AuthPageShell.vue'
import AuthCardHeader from '@/Components/Auth/AuthCardHeader.vue'
import DemoUserPicker from '@/Components/Auth/DemoUserPicker.vue'
import { useLoginForm } from '@/Composables/useLoginForm'
import { AUTH_PLACEHOLDERS } from '@/constants/authPlaceholders'

defineProps({
  status: {
    type: String,
    default: '',
  },
  demoUsers: {
    type: Array,
    default: () => [],
  },
  demoPassword: {
    type: String,
    default: 'password',
  },
})

const { form, fillDemoUser, submit } = useLoginForm()
</script>

<template>
  <AuthPageShell>
    <Head title="Log in" />

    <AuthCardHeader
      title="TaskFlow"
      subtitle="Log in to explore the demo workspace."
      badge="TF"
      tagline="Focus, ship, repeat."
    />

    <div
      v-if="status"
      class="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-500/10 px-3 py-2 text-xs font-medium text-emerald-700 dark:text-emerald-300"
    >
      {{ status }}
    </div>

    <DemoUserPicker
      :users="demoUsers"
      :demo-password="demoPassword"
      context="taskflow"
      @select="(user) => fillDemoUser(user, demoPassword)"
    />

    <form @submit.prevent="submit" class="space-y-4">
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
          :placeholder="AUTH_PLACEHOLDERS.email"
        />
        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ form.errors.email }}
        </p>
      </div>

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
          :placeholder="AUTH_PLACEHOLDERS.password"
        />
        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500 dark:text-red-400">
          {{ form.errors.password }}
        </p>
      </div>

      <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
        <label class="inline-flex items-center gap-2">
          <input
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 bg-white text-emerald-500 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-900"
          />
          <span>Remember me</span>
        </label>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="mt-1 inline-flex w-full items-center justify-center rounded-lg bg-gradient-to-r from-blue-500 via-emerald-400 to-cyan-400 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-lg shadow-emerald-500/30 transition hover:translate-y-0.5 hover:shadow-2xl hover:shadow-emerald-500/40 disabled:cursor-not-allowed disabled:opacity-70"
      >
        <span v-if="!form.processing">Sign in</span>
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

    <div
      class="mt-5 border-t border-slate-200 pt-4 text-center text-[11px] text-slate-500 dark:border-slate-800 dark:text-slate-400"
    >
      <p>
        Or
        <Link
          :href="route('incident.login')"
          class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
        >
          go to the Incident Command login
        </Link>
      </p>

      <p class="mt-2">
        New here?
        <Link
          :href="route('register')"
          class="font-medium text-emerald-600 hover:text-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200"
        >
          Create a TaskFlow account
        </Link>
      </p>

      <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
        Built with Laravel, Vue, and Inertia as a real world portfolio project.
      </p>
    </div>
  </AuthPageShell>
</template>
