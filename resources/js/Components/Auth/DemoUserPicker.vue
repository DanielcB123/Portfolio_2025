<script setup>
import { computed, ref, useId } from 'vue'

const props = defineProps({
  users: {
    type: Array,
    default: () => [],
  },
  demoPassword: {
    type: String,
    default: 'password',
  },
  context: {
    type: String,
    default: 'taskflow',
    validator: (value) => ['taskflow', 'incident'].includes(value),
  },
})

const emit = defineEmits(['select'])

const selectId = useId()
const selectedEmail = ref('')

const copy = computed(() => {
  if (props.context === 'incident') {
    return {
      heading: 'Try an on-call demo account',
      placeholder: 'Select an on-call demo user',
      helper: 'Pick a user to auto-fill the login form and explore Incident Command.',
    }
  }

  return {
    heading: 'Try a demo account',
    placeholder: 'Select a seeded demo user',
    helper: 'Pick a user to auto-fill the login form and explore the TaskFlow workspace.',
  }
})

const groupedUsers = computed(() => {
  const groups = new Map()

  for (const user of props.users) {
    const team = user.team || 'Demo team'
    if (!groups.has(team)) {
      groups.set(team, [])
    }
    groups.get(team).push(user)
  }

  return Array.from(groups.entries()).map(([team, members]) => ({
    team,
    members,
  }))
})

const onSelect = (event) => {
  const email = event.target.value
  selectedEmail.value = email

  const user = props.users.find((candidate) => candidate.email === email)
  if (user) {
    emit('select', user)
  }
}
</script>

<template>
  <div
    v-if="users.length"
    class="mb-4 rounded-xl border border-slate-200 bg-slate-50/80 p-3 dark:border-slate-700/70 dark:bg-slate-900/60"
  >
    <div class="mb-2 flex items-center justify-between gap-2">
      <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
        {{ copy.heading }}
      </p>
      <span class="text-[10px] text-slate-400 dark:text-slate-500">
        Password: {{ demoPassword }}
      </span>
    </div>

    <label :for="selectId" class="sr-only">Choose a demo account</label>
    <select
      :id="selectId"
      :value="selectedEmail"
      class="block w-full rounded-lg border border-slate-300 bg-white/90 px-3 py-2 text-sm text-slate-900 outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-50"
      @change="onSelect"
    >
      <option value="" disabled>
        {{ copy.placeholder }}
      </option>
      <optgroup
        v-for="group in groupedUsers"
        :key="group.team"
        :label="group.team"
      >
        <option
          v-for="user in group.members"
          :key="user.email"
          :value="user.email"
        >
          {{ user.name }} ({{ user.email }})
        </option>
      </optgroup>
    </select>

    <p class="mt-2 text-[10px] text-slate-400 dark:text-slate-500">
      {{ copy.helper }} All demo accounts share the same password.
    </p>
  </div>
</template>
