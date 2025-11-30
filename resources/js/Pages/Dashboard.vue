<script setup>
// Dashboard.vue
import { onMounted, ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import { useTasks } from '@/Composables/useTasks';
import TaskColumn from '@/Components/TaskColumn.vue';
import CreateTaskModal from '@/Components/CreateTaskModal.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import { toast } from 'vue3-toastify';



const todoColumnRef       = ref(null);
const inProgressColumnRef = ref(null);
const doneColumnRef       = ref(null);
const activeColumn        = ref(null);

const isDark              = ref(false);

const props = defineProps({
  auth: { type: Object, required: false, default: () => ({}) },
  teamMembers: {
    type: Array,
    default: () => [],
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const showCreate = ref(false);
const showCommandPalette = ref(false);

// local filter for "All" vs "My tasks"
const assignedFilter = ref('all'); // 'all' | 'me'

const currentUserId = computed(() => props.auth?.user?.id ?? null);
const currentUser   = computed(() => props.auth?.user ?? null);

const currentMember = computed(() => {
  if (!currentUser.value) return null;
  return props.teamMembers.find(m => m.id === currentUser.value.id) ?? null;
});

const filteredUserId = computed(() => {
  // "My tasks"
  if (assignedFilter.value === 'me' && currentUserId.value) {
    return Number(currentUserId.value);
  }

  // Specific teammate from command palette (a number)
  if (
    assignedFilter.value !== 'all' &&
    assignedFilter.value !== 'me' &&
    assignedFilter.value != null &&
    assignedFilter.value !== ''
  ) {
    return Number(assignedFilter.value);
  }

  // "All" or nothing selected
  return null;
});




// useTasks
const {
  tasks,
  todoTasks,
  inProgressTasks,
  doneTasks,
  isLoading,
  isCreating,
  filterAssigned,
  search,
  loadTasks,
  createTask,
  updateTaskInline,
  moveTask,
  deleteTask,
  assignTask,
} = useTasks();

/**
 * Filtered columns (client-side "My tasks")
 */
const filteredTodoTasks = computed(() => {
  const uid = filteredUserId.value;
  const q = search.value.trim().toLowerCase();

  return todoTasks.value.filter((task) => {
    const assignedId =
      task.assigned_to != null ? Number(task.assigned_to) : null;
    const relId =
      task.assigned_user?.id != null ? Number(task.assigned_user.id) : null;

    const matchesUser =
      uid == null ? true : assignedId === uid || relId === uid;

    const title = (task.title || '').toLowerCase();
    const desc  = (task.description || '').toLowerCase();
    const matchesSearch = !q ? true : title.includes(q) || desc.includes(q);

    return matchesUser && matchesSearch;
  });
});


const filteredInProgressTasks = computed(() => {
  const uid = filteredUserId.value;
  const q = search.value.trim().toLowerCase();

  return inProgressTasks.value.filter((task) => {
    const assignedId =
      task.assigned_to != null ? Number(task.assigned_to) : null;
    const relId =
      task.assigned_user?.id != null ? Number(task.assigned_user.id) : null;

    const matchesUser =
      uid == null ? true : assignedId === uid || relId === uid;

    const title = (task.title || '').toLowerCase();
    const desc  = (task.description || '').toLowerCase();
    const matchesSearch = !q ? true : title.includes(q) || desc.includes(q);

    return matchesUser && matchesSearch;
  });
});

const filteredDoneTasks = computed(() => {
  const uid = filteredUserId.value;
  const q = search.value.trim().toLowerCase();

  return doneTasks.value.filter((task) => {
    const assignedId =
      task.assigned_to != null ? Number(task.assigned_to) : null;
    const relId =
      task.assigned_user?.id != null ? Number(task.assigned_user.id) : null;

    const matchesUser =
      uid == null ? true : assignedId === uid || relId === uid;

    const title = (task.title || '').toLowerCase();
    const desc  = (task.description || '').toLowerCase();
    const matchesSearch = !q ? true : title.includes(q) || desc.includes(q);

    return matchesUser && matchesSearch;
  });
});



async function ensureApiKey() {
  const existing = window.localStorage.getItem('taskflow_api_key');
  if (existing) return existing;

  try {
    const { data } = await axios.get('/api-token');
    if (data.success && data.api_key) {
      window.localStorage.setItem('taskflow_api_key', data.api_key);
      return data.api_key;
    }
  } catch (e) {
    toast.error('Could not get API key. Please log in again.');
  }

  return null;
}

onMounted(async () => {
  const key = await ensureApiKey();
  if (!key) return;

  await loadTasks();

  const stored = window.localStorage.getItem('theme');
  const prefersDark =
    window.matchMedia &&
    window.matchMedia('(prefers-color-scheme: dark)').matches;

  const initial =
    stored === 'dark' || stored === 'light'
      ? stored
      : prefersDark
        ? 'dark'
        : 'light';

  console.log('initial theme:', initial);
  applyTheme(initial);
});


function handleSwitchColumn(status) {
  if (status === 'all') {
    activeColumn.value = null;
    return;
  }

  activeColumn.value = status;

  const map = {
    todo: todoColumnRef,
    in_progress: inProgressColumnRef,
    done: doneColumnRef,
  };

  const refObj = map[status];

  if (!refObj || !refObj.value) {
    console.log('No column ref for status:', status, map);
    return;
  }

  const el = refObj.value.$el ?? refObj.value;

  if (el && el.scrollIntoView) {
    el.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
    });
  }
}

function applyTheme(theme) {
  const root = document.documentElement;

  if (theme === 'dark') {
    root.classList.add('dark');
    window.localStorage.setItem('theme', 'dark');
    isDark.value = true;
  } else {
    root.classList.remove('dark');
    window.localStorage.setItem('theme', 'light');
    isDark.value = false;
  }
  console.log('applyTheme ->', theme, 'html classes:', root.className);
}

function toggleTheme() {
  applyTheme(isDark.value ? 'light' : 'dark');
}


function handleMove({ id, status, position }) {
  moveTask(id, status, position);
}

function handleEdit({ task, fields }) {
  updateTaskInline(task.id, fields);
}

function handleDelete({ task }) {
  deleteTask(task.id);
}

function handleAssign({ task, userId }) {
  assignTask(task.id, userId);
}

async function handleCreate(payload) {
  const created = await createTask(payload);
  if (created) {
    showCreate.value = false;
  }
}

async function handleLogout() {
  try {
    await axios.post('/logout');
  } catch (e) {
    toast.error('There was a problem logging you out. Please try again.');
    return;
  }

  window.localStorage.removeItem('taskflow_api_key');
  toast.success('You have been logged out');
  window.location.href = '/';
}

/**
 * Command palette / filters
 */
function handleSetSearch(value) {
  search.value = value || '';
}

function handleSetFilterAssigned(value) {
  assignedFilter.value = value;
}


function handleMarkTaskDone(taskId) {
  updateTaskInline(taskId, { status: 'done' });
}
</script>


<template>
  <!-- App background -->
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >



    <!-- Decorative background layer -->
    <div class="pointer-events-none absolute inset-0 overflow-hidden">
      <!-- Animated gradient mesh, light and dark handled with Tailwind classes -->
      
      <div
        class="mesh-layer
               bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.35)_0,_transparent_25%),radial-gradient(circle_at_bottom,_rgba(45,212,191,0.25)_0,_transparent_60%)]
               dark:bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.6)_0,_transparent_25%),radial-gradient(circle_at_bottom,_rgba(56,189,248,0.15)_0,_rgba(15,23,42,1)_60%)]
               animate-[mesh-move_45s_ease-in-out_infinite_alternate]"
      ></div>

      <!-- Subtle technical grid overlay -->
      <div
        class="grid-overlay
              opacity-30
              [background-image:linear-gradient(to_right,rgba(148,163,184,0.45)_1px,transparent_1px),linear-gradient(to_bottom,rgba(148,163,184,0.45)_1px,transparent_1px)]
              [background-size:56px_56px]
              dark:opacity-30
              dark:mix-blend-soft-light
              dark:[background-image:linear-gradient(to_right,rgba(15,23,42,0.85)_1px,transparent_1px),linear-gradient(to_bottom,rgba(15,23,42,0.85)_1px,transparent_1px)]"
      ></div>

    </div>

    <!-- Main content, lifted above background -->
    <div class="relative max-w-6xl mx-auto px-4 py-6 space-y-4">
      <Link
        href="/"
        class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-slate-300/80 bg-white/80 px-4 py-1.5 text-xs text-slate-700 shadow shadow-slate-200/60 backdrop-blur transition hover:border-emerald-400/80 hover:text-emerald-600 hover:bg-white dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300 dark:shadow-slate-900/40 dark:hover:border-emerald-400/80 dark:hover:text-emerald-300 dark:hover:bg-slate-900"
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
      <!-- Header bar -->
      <header
        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between
               bg-white/80 dark:bg-slate-900/80 backdrop-blur
               rounded-2xl border border-slate-200/60 dark:border-slate-800 px-4 py-3"
      >
        <!-- Left: title / badge -->
        <div class="flex items-center gap-2">
          <span class="text-lg font-semibold dark:text-slate-100">TaskFlow</span>
          <span
            class="text-xs px-2 py-1 rounded-full bg-blue-100/80 text-blue-700 whitespace-nowrap"
          >
            MediaHaus Squad
          </span>
        </div>

        <!-- Right: search + controls -->
        <div
          class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 w-full sm:w-auto"
        >
          <!-- Search and command palette -->
          <div class="flex items-center gap-2 w-full sm:w-auto">
            <input
              v-model="search"
              type="search"
              placeholder="Search tasks..."
              class="w-full sm:w-48 text-sm rounded-lg border border-slate-300 text-slate-600 dark:text-slate-400 dark:border-slate-700 bg-white dark:bg-slate-900 px-2 py-1 focus:outline-none focus:ring-2 focus:ring-blue-400"
              @keyup.enter="loadTasks"
            />
            <button
              type="button"
              class="inline-flex items-center gap-1 text-[11px] px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-500 dark:text-slate-300"
              @click="showCommandPalette = true"
            >
              <span class="text-xs text-slate-400">⌘K</span>
              <span class="hidden xs:inline">Command palette</span>
            </button>
          </div>

          <!-- New button + user info -->
          <div class="flex items-center justify-between sm:justify-end gap-2">
            <button
              type="button"
              class="text-sm px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 active:scale-95 transition"
              @click="showCreate = true"
              :disabled="isCreating"
            >
              <span v-if="!isCreating">+ New</span>
              <span v-else class="flex items-center gap-1">
                <span
                  class="h-3 w-3 rounded-full border-2 border-t-transparent border-white animate-spin"
                />
                Creating...
              </span>
            </button>

            <div class="flex items-center gap-2">
              <div
                v-if="currentMember"
                class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] font-semibold text-white"
                :style="{ backgroundColor: currentMember.avatar_color || '#0f766e' }"
              >
                {{ currentMember.name.slice(0, 1).toUpperCase() }}
              </div>

              <span
                v-if="currentMember"
                class="hidden sm:inline text-[11px] text-slate-500 dark:text-slate-300 truncate max-w-[120px]"
              >
                {{ currentMember.name }}
              </span>

              <!-- THEME TOGGLE BUTTON -->
              <button
                type="button"
                class="text-xs dark:text-white px-2 py-1 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition flex items-center gap-1"
                @click="toggleTheme"
                :aria-label="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
              >
                <span v-if="isDark">🌞</span>
                <span v-else>🌙</span>
                <span class="hidden sm:inline">
                  {{ isDark ? 'Light' : 'Dark' }}
                </span>
              </button>

              <button
                type="button"
                class="text-xs px-3 py-1.5 dark:text-white rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 active:scale-95 transition"
                @click="handleLogout"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </header>

      <div
        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between text-xs text-slate-500 mt-2
               bg-white/80 dark:bg-slate-900/80 backdrop-blur
               rounded-2xl border border-slate-200/60 dark:border-slate-800 px-4 py-3"
      >
        <div class="flex items-center gap-2">
          <span>Filter:</span>

          <button
            type="button"
            class="px-2 py-0.5 rounded-full border text-[11px]"
            :class="assignedFilter === 'all'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700'"
            @click="handleSetFilterAssigned('all')"
          >
            All
          </button>
          <button
            type="button"
            class="px-2 py-0.5 rounded-full border text-[11px]"
            :class="assignedFilter === 'me'
              ? 'bg-blue-600 text-white border-blue-600'
              : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-700'"
            @click="handleSetFilterAssigned('me')"
          >
            My tasks
          </button>
        </div>

        <div class="text-[11px] text-slate-400">
          Press Cmd+K or Ctrl+K for the command palette
        </div>
      </div>

      <!-- Columns -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mt-2">
        <!-- To Do -->
        <TaskColumn
          v-if="!activeColumn || activeColumn === 'todo'"
          ref="todoColumnRef"
          title="To Do"
          status="todo"
          :tasks="filteredTodoTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
          @open-create="showCreate = true"
        />

        <!-- In Progress -->
        <TaskColumn
          v-if="!activeColumn || activeColumn === 'in_progress'"
          ref="inProgressColumnRef"
          title="In Progress"
          status="in_progress"
          :tasks="filteredInProgressTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
        />

        <!-- Done -->
        <TaskColumn
          v-if="!activeColumn || activeColumn === 'done'"
          ref="doneColumnRef"
          title="Done"
          status="done"
          :tasks="filteredDoneTasks"
          :is-loading="isLoading"
          :team-members="props.teamMembers"
          @move-task="handleMove"
          @edit-task="handleEdit"
          @delete-task="handleDelete"
          @assign-task="handleAssign"
        />
      </div>
    </div>

    <CreateTaskModal
      :show="showCreate"
      :team-members="props.teamMembers"
      @close="showCreate = false"
      @submit="handleCreate"
    />

    <CommandPalette
      v-model:show="showCommandPalette"
      :tasks="tasks"
      :team-members="props.teamMembers"
      :current-user-id="currentUserId"
      @create-task="showCreate = true"
      @set-search="handleSetSearch"
      @set-filter-assigned="handleSetFilterAssigned"
      @switch-column="handleSwitchColumn"
      @mark-task-done="handleMarkTaskDone"
    />
  </div>
</template>



<style scoped>
.mesh-layer {
  position: absolute;
  inset: -20%;
  filter: blur(40px);
  opacity: 0.9;
  transform: translate3d(0, 0, 0);
}

/* Grid overlay base layout, colors are handled by Tailwind classes */
.grid-overlay {
  position: absolute;
  inset: 0;
  pointer-events: none;
}

/* Gradient mesh motion, reused like the welcome/login page concept */
@keyframes mesh-move {
  0% {
    transform: translate3d(-4%, -3%, 0) scale(1);
  }
  25% {
    transform: translate3d(3%, -2%, 0) scale(1.03);
  }
  50% {
    transform: translate3d(4%, 3%, 0) scale(1.05);
  }
  75% {
    transform: translate3d(-2%, 4%, 0) scale(1.02);
  }
  100% {
    transform: translate3d(-4%, -3%, 0) scale(1.04);
  }
}
</style>
