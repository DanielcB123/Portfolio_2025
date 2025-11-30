<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import confetti from 'canvas-confetti';
import { toast } from 'vue3-toastify';

const props = defineProps({
  incidents: {
    type: Array,
    default: () => [],
  },
});

const mobileMenuOpen = ref(false);
const toggleMobileMenu = () => mobileMenuOpen.value = !mobileMenuOpen.value;
const closeMobileMenu = () => mobileMenuOpen.value = false;

const incidents = computed(() => props.incidents || []);

// Filters and state
const timeFilter = ref('24h'); // 24h | 7d | 30d | all
const severityFilter = ref('all'); // all | SEV1 | SEV2 | SEV3
const statusFilter = ref('all'); // all | investigating | mitigating | monitoring | resolved
const search = ref('');
const selectedIncidentId = ref(null);
const now = ref(new Date());
const isCreateOpen = ref(false);
const newIncident = ref({
  title: '',
  system: '',
  severity: 'SEV2',
  summary: '',
  impacted_users: '',
  impacted_regions_raw: '',
  tags_raw: '',
});
const isStatusModalOpen = ref(false);
const statusChange = ref({
  incidentId: null,
  fromStatus: '',
  toStatus: '',
  note: '',
  actor: '',
});
const isFollowUpModalOpen = ref(false);
const newFollowUp = ref({
  label: '',
  owner: '',
});
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


let tickTimer = null;

onMounted(() => {
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

  if (incidents.value.length && !selectedIncidentId.value) {
    selectedIncidentId.value = incidents.value[0].id;
  }

  tickTimer = window.setInterval(() => {
    now.value = new Date();
  }, 60 * 1000);
});

onBeforeUnmount(() => {
  if (tickTimer) window.clearInterval(tickTimer);
});

// Helpers

function openFollowUpModal() {
  // default owner can mirror incident owner if you want
  newFollowUp.value = {
    label: '',
    owner: selectedIncident.value?.owner || '',
  };
  isFollowUpModalOpen.value = true;
}

function closeFollowUpModal() {
  isFollowUpModalOpen.value = false;
  newFollowUp.value = { label: '', owner: '' };
}

function submitNewFollowUp() {
  if (!selectedIncident.value) return;

  const payload = {
    incident_id: selectedIncident.value.id,
    label: newFollowUp.value.label,
    owner: newFollowUp.value.owner || null,
  };

  router.post(
    `/incident-command/incidents/${selectedIncident.value.id}/follow-ups`,
    payload,
    {
      preserveScroll: true,
      preserveState: false,
      onSuccess: () => {
        toast.success('Follow up created');
        closeFollowUpModal();
      },
      onError: () => {
        toast.error('Could not create follow up. Please check the form.');
      },
    }
  );
}


function fireResolvedConfetti() {
  if (typeof window === 'undefined') return;

  const duration = 2000; 
  const end = Date.now() + duration;

  (function frame() {
    confetti({
      particleCount: 4,
      angle: 60,
      spread: 55,
      origin: { x: 0 },
    });
    confetti({
      particleCount: 4,
      angle: 120,
      spread: 55,
      origin: { x: 1 },
    });

    if (Date.now() < end) {
      requestAnimationFrame(frame);
    }
  })();
}


function openCreateIncident() {
  isCreateOpen.value = true;
}

function closeCreateIncident() {
  isCreateOpen.value = false;
}

function openStatusChangeModal(incident, newStatus) {
  if (!incident || !newStatus || incident.status === newStatus) return;

  statusChange.value = {
    incidentId: incident.id,
    fromStatus: incident.status,
    toStatus: newStatus,
    note: '',
    actor: incident.owner || 'On call engineer',
  };

  isStatusModalOpen.value = true;
}

function submitStatusChange() {
  const sc = statusChange.value;
  if (!sc.incidentId || !sc.toStatus) return;

  const shouldFireConfetti =
    sc.toStatus === 'resolved' && sc.fromStatus !== 'resolved';

  const payload = {
    status: sc.toStatus,
    status_actor: sc.actor || null,
    status_note: sc.note || null,
  };

  router.patch(`/incident-command/incidents/${sc.incidentId}`, payload, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      if (shouldFireConfetti) {
        fireResolvedConfetti();
      }

      toast.success('Incident status updated');

      isStatusModalOpen.value = false;
      statusChange.value = {
        incidentId: null,
        fromStatus: '',
        toStatus: '',
        note: '',
        actor: '',
      };
    },
    onError: () => {
      toast.error('Could not update status. Please try again.');
    },
  });
}



function submitNewIncident() {
  const payload = {
    title: newIncident.value.title,
    system: newIncident.value.system,
    severity: newIncident.value.severity,
    summary: newIncident.value.summary,
    impacted_users: newIncident.value.impacted_users || null,
    impacted_regions: (newIncident.value.impacted_regions_raw || '')
      .split(',')
      .map(r => r.trim())
      .filter(Boolean),
    tags: (newIncident.value.tags_raw || '')
      .split(',')
      .map(t => t.trim())
      .filter(Boolean),
  };

  router.post('/incident-command/incidents', payload, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      toast.success('New incident created');

      newIncident.value = {
        title: '',
        system: '',
        severity: 'SEV2',
        summary: '',
        impacted_users: '',
        impacted_regions_raw: '',
        tags_raw: '',
      };
      isCreateOpen.value = false;
    },
    onError: () => {
      toast.error('Could not create incident. Please check the form.');
    },
  });
}


function eventDotClass(type) {
  const t = (type || '').toLowerCase();

  if (t === 'detected' || t === 'reported' || t === 'alert') {
    return 'bg-rose-400';
  }

  if (t === 'triage' || t === 'investigating') {
    return 'bg-sky-400';
  }

  if (t === 'mitigation' || t === 'mitigating') {
    return 'bg-amber-400';
  }

  if (t === 'monitoring' || t === 'communication') {
    return 'bg-sky-300';
  }

  if (t === 'resolved') {
    return 'bg-emerald-400';
  }

  if (t === 'follow_up') {
    return 'bg-indigo-400';
  }

  // default / unknown
  return 'bg-slate-400';
}

function createIncident() {
  router.post('/incident-command/incidents', {
    title: 'Spike in 5xx from payment service',
    system: 'Payments',
    severity: 'SEV1',
    summary: 'New demo incident created from the dashboard.',
    impacted_users: '~18% of checkout traffic',
    impacted_regions: ['US-East', 'US-West'],
    tags: ['payments', 'checkout', '5xx'],
  }, {
    preserveScroll: true,
    preserveState: false,
  });
}


function changeStatus(incidentId, newStatus) {
  if (!incidentId || !newStatus) return;

  router.patch(`/incident-command/incidents/${incidentId}`, { status: newStatus }, {
    preserveScroll: true,
    preserveState: true,
  });
}

function changeFollowUpStatus(followUpId, newStatus) {
  if (!followUpId || !newStatus) return;

  router.patch(`/incident-command/follow-ups/${followUpId}`, { status: newStatus }, {
    preserveScroll: true,
    preserveState: true,
  });
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

function followUpStatusLabel(status) {
  if (status === 'open') return 'Open';
  if (status === 'in_progress') return 'In progress';
  if (status === 'done') return 'Done';
  return status;
}

function relativeTime(iso) {
  const d = new Date(iso);
  const diffMs = now.value.getTime() - d.getTime();
  const diffMin = Math.round(diffMs / 60000);
  const diffHours = Math.round(diffMs / (60 * 60000));

  if (diffMin < 1) return 'just now';
  if (diffMin < 60) return `${diffMin} min ago`;
  if (diffHours < 24) return `${diffHours} h ago`;

  const diffDays = Math.round(diffHours / 24);
  return `${diffDays} d ago`;
}

function matchesTimeWindow(incident) {
  if (timeFilter.value === 'all') return true;

  const started = new Date(incident.startedAt).getTime();
  const windowMap = {
    '24h': 24,
    '7d': 24 * 7,
    '30d': 24 * 30,
  };
  const hoursBack = windowMap[timeFilter.value] ?? 24;
  const cutoff = now.value.getTime() - hoursBack * 60 * 60 * 1000;
  return started >= cutoff;
}

function statusLabel(status) {
  switch (status) {
    case 'investigating':
      return 'Investigating';
    case 'mitigating':
      return 'Mitigation in progress';
    case 'monitoring':
      return 'Monitoring';
    case 'resolved':
      return 'Resolved';
    default:
      return status;
  }
}

function severityColor(sev) {
  switch (sev) {
    case 'SEV1':
      return 'bg-rose-500/15 text-rose-300 border border-rose-500/40';
    case 'SEV2':
      return 'bg-amber-500/15 text-amber-200 border border-amber-500/40';
    case 'SEV3':
      return 'bg-sky-500/15 text-sky-200 border border-sky-500/40';
    default:
      return 'bg-slate-700/40 text-slate-100 border border-slate-500/40';
  }
}

function statusChipClasses(status) {
  if (status === 'resolved') {
    return 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/40';
  }
  if (status === 'monitoring') {
    return 'bg-sky-500/10 text-sky-200 border border-sky-500/40';
  }
  if (status === 'mitigating') {
    return 'bg-amber-500/10 text-amber-200 border border-amber-500/40';
  }
  return 'bg-rose-500/10 text-rose-200 border border-rose-500/40';
}

function incidentIsLive(incident) {
  return incident.status === 'investigating' || incident.status === 'mitigating';
}

// Derived data
const filteredIncidents = computed(() => {
  const q = search.value.trim().toLowerCase();

  return incidents.value
    .filter((incident) => {
      if (!matchesTimeWindow(incident)) return false;

      if (severityFilter.value !== 'all' && incident.severity !== severityFilter.value) {
        return false;
      }

      if (statusFilter.value !== 'all' && incident.status !== statusFilter.value) {
        return false;
      }

      if (!q) return true;

      const text = [
        incident.title,
        incident.system,
        incident.summary,
        incident.tags?.join(' ') ?? '',
        incident.key,
      ]
        .join(' ')
        .toLowerCase();

      return text.includes(q);
    })
    .sort(
      (a, b) =>
        new Date(b.startedAt).getTime() - new Date(a.startedAt).getTime()
    );
});

const selectedIncident = computed(() => {
  if (!selectedIncidentId.value) return filteredIncidents.value[0] ?? null;

  return (
    filteredIncidents.value.find((i) => i.id === selectedIncidentId.value) ??
    filteredIncidents.value[0] ??
    null
  );
});

// Summary stats
const stats = computed(() => {
  const all = incidents.value;
  const openStatuses = ['investigating', 'mitigating', 'monitoring'];
  const openIncidents = all.filter((i) => openStatuses.includes(i.status));
  const sev1Open = openIncidents.filter((i) => i.severity === 'SEV1');

  const resolved = all.filter((i) => i.status === 'resolved');
  let mttrMinutes = null;
  if (resolved.length) {
    const total = resolved.reduce((sum, i) => {
      const start = new Date(i.startedAt).getTime();
      const end = new Date(i.lastUpdatedAt || i.startedAt).getTime();
      return sum + (end - start) / 60000;
    }, 0);
    mttrMinutes = Math.round(total / resolved.length);
  }

  const followUpsOpen = all.reduce((sum, i) => {
    return (
      sum +
      (i.followUps?.filter((a) => a.status === 'open' || a.status === 'in_progress').length ||
        0)
    );
  }, 0);

  return {
    totalOpen: openIncidents.length,
    sev1Open: sev1Open.length,
    mttrMinutes,
    followUpsOpen,
  };
});

// Interactions
function selectIncident(id) {
  selectedIncidentId.value = id;
}
</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <div class="pointer-events-none absolute inset-0
       bg-[radial-gradient(circle_at_top,_rgba(18,163,184,0.2)_0,_transparent_55%),_linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.35)_55%),_radial-gradient(circle_at_bottom,_rgba(56,189,248,0.25)_0,_transparent_60%)]
       dark:bg-[radial-gradient(circle_at_top,_rgba(18,163,184,0.25)_0,_transparent_55%),_linear-gradient(to_bottom,_rgba(15,23,42,0.7)_0,_rgba(15,23,42,1)_55%),_radial-gradient(circle_at_bottom,_rgba(56,189,248,0.1)_0,_rgba(15,23,42,1)_60%)]"

    ></div>
    <div
      class="pointer-events-none absolute inset-0 opacity-30 dark:mix-blend-soft-light
             [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)]
             [background-size:56px_56px]
             dark:[background-image:linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)]"
    ></div>
    <!-- Header -->
    <div class="relative z-10">
  <header
    class="border-b py-2 border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80"
  >
    <div
      class="mx-auto max-w-6xl px-4 sm:px-6 py-2 flex items-center justify-between"
    >
      <!-- Left: Brand -->
      <div class="flex items-center gap-3">
        <div
          class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-cyan-500 text-xs font-semibold text-slate-950 shadow shadow-emerald-400/60"
        >
          IC
        </div>
        <div>
          <div class="text-[10px] sm:text-[11px] font-semibold uppercase tracking-[0.22em] text-emerald-500 dark:text-emerald-400">
            Incident Command Center
          </div>
          <div class="text-[10px] sm:text-[11px] text-slate-500 dark:text-slate-400">
            Operations grade dashboard demo
          </div>
        </div>
      </div>
            <!-- Mobile: New Incident + SEV1 moved below header -->
            <div class="px-4 flex items-center gap-3">
              <button
                @click="openCreateIncident"
                class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-semibold text-slate-950 shadow hover:bg-emerald-400"
              >
                <span class="text-sm">＋</span>
                <p class="hidden sm:flex">New incident</p>
              </button>

              <div
                v-if="stats.sev1Open > 0"
                class="inline-flex hidden sm:flex items-center gap-2 rounded-full border border-rose-300 bg-rose-50 px-3 py-1 text-[11px] text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-100"
              >
                <span class="relative flex h-2 w-2">
                  <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                  <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-400"></span>
                </span>
                {{ stats.sev1Open }} active SEV1
              </div>
            </div>
      <!-- Right: hamburger only on mobile -->
      <button
        type="button"
        class="md:hidden inline-flex items-center justify-center rounded-full border border-slate-300 bg-white px-2.5 py-1.5 text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
        @click="toggleMobileMenu"
      >
        <!-- Menu icon -->
        <svg
          v-if="!mobileMenuOpen"
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
        </svg>

        <!-- Close icon -->
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="h-5 w-5"
          fill="none"
          viewBox="0 0 24 24"
          stroke-width="1.5"
          stroke="currentColor"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>

      <!-- Desktop right controls -->
      <div class="hidden md:flex items-center gap-3">

        <!-- Theme toggle -->
        <button
          type="button"
          class="inline-flex items-center gap-2 rounded-full border border-slate-300/80 bg-white/70 px-3 py-1.5 text-[11px] text-slate-700 shadow dark:border-slate-700/70 dark:bg-slate-900/80 dark:text-slate-300 hover:border-emerald-400/80 dark:hover:border-emerald-400/80"
          @click="applyTheme(theme === 'dark' ? 'light' : 'dark')"
        >
          <span
            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 dark:text-amber-300"
          >
            <span v-if="theme === 'dark'">☾</span>
            <span v-else>☀</span>
          </span>
          <span v-if="theme === 'dark'">Dark mode</span>
          <span v-else>Light mode</span>
        </button>

        <button
          type="button"
          class="text-xs px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
          @click="handleLogout"
        >
          Logout
        </button>

        <Link
          href="/"
          class="text-[11px] inline-flex items-center gap-1 rounded-full border border-slate-300 px-3 py-1 text-slate-700 hover:border-emerald-400 hover:text-emerald-500 dark:border-slate-700 dark:text-slate-200"
        >
          ← Back to portfolio
        </Link>
      </div>
    </div>

    <!-- Mobile dropdown -->
    <div
      v-if="mobileMenuOpen"
      class="md:hidden border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 shadow-lg p-3 text-[12px]"
    >
      <button
        class="w-full flex justify-between items-center px-2 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800"
        @click="
          applyTheme(theme === 'dark' ? 'light' : 'dark');
          closeMobileMenu();
        "
      >
        <span>Light / Dark mode</span>
        <span v-if="theme === 'dark'">☾</span>
        <span v-else>☀</span>
      </button>

      <button
        class="w-full flex justify-between items-center px-2 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 mt-1"
        @click="
          handleLogout();
          closeMobileMenu();
        "
      >
        Logout
      </button>

      <Link
        href="/"
        class="w-full flex justify-between items-center px-2 py-2 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 mt-1"
        @click="closeMobileMenu"
      >
        <span>Back to portfolio</span>
        <span aria-hidden="true">←</span>
      </Link>
    </div>

    <!-- Mobile: New Incident + SEV1 moved below header -->
    <!-- <div class="md:hidden mt-3 px-4 flex items-center gap-3">
      <button
        @click="openCreateIncident"
        class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-semibold text-slate-950 shadow hover:bg-emerald-400"
      >
        <span class="text-sm">＋</span>
        New incident
      </button>

      <div
        v-if="stats.sev1Open > 0"
        class="inline-flex items-center gap-2 rounded-full border border-rose-300 bg-rose-50 px-3 py-1 text-[11px] text-rose-700 dark:border-rose-500/40 dark:bg-rose-500/10 dark:text-rose-100"
      >
        <span class="relative flex h-2 w-2">
          <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
          <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-400"></span>
        </span>
        {{ stats.sev1Open }} active SEV1
      </div>
    </div> -->

  </header>

    <main class="mx-auto max-w-6xl px-6 py-6 space-y-5">
      <!-- Summary cards -->
      <section
        class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4"
        aria-label="Incident summary"
      >
        <article
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
        >
          <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
            Open incidents
          </div>
          <div class="mt-2 flex items-end gap-2">
            <div class="text-2xl font-semibold">
              {{ stats.totalOpen }}
            </div>
            <span class="text-[11px] text-slate-500">
              in active investigation or monitoring
            </span>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
        >
          <div class="flex items-center justify-between">
            <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
              Critical impact
            </div>
            <span class="h-1.5 w-1.5 rounded-full bg-rose-400 animate-pulse"></span>
          </div>
          <div class="mt-2 flex items-end gap-2">
            <div class="text-2xl font-semibold text-rose-600 dark:text-rose-300">
              {{ stats.sev1Open }}
            </div>
            <span class="text-[11px] text-slate-500">
              active SEV1 incidents
            </span>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
        >
          <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
            MTTR demo
          </div>
          <div class="mt-2 flex items-end gap-2">
            <div class="text-2xl font-semibold">
              {{ stats.mttrMinutes ?? '–' }}
            </div>
            <span class="text-[11px] text-slate-500">
              min average resolution
            </span>
          </div>
        </article>

        <article
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/5 dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
        >
          <div class="text-[11px] uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
            Follow ups
          </div>
          <div class="mt-2 flex items-end gap-2">
            <div class="text-2xl font-semibold text-emaerald-600 dark:text-emerald-300">
              {{ stats.followUpsOpen }}
            </div>
            <span class="text-[11px] text-slate-500">
              open action items
            </span>
          </div>
        </article>
        
      </section>

      <!-- Filters bar -->
      <section
        class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-lg shadow-slate-900/5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
      >
        <div class="flex flex-wrap items-center gap-2 text-[11px]">
          <span class="uppercase tracking-[0.18em] text-slate-500 dark:text-slate-500">
            Filters
          </span>

          <!-- Time -->
          <div class="flex items-center gap-1">
            <span class="text-slate-500">Time</span>
            <button
              v-for="opt in ['24h', '7d', '30d', 'all']"
              :key="opt"
              type="button"
              class="rounded-full px-2 py-1 border text-[11px]"
              :class="timeFilter === opt
                ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'
                : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
              @click="timeFilter = opt"
            >
              {{ opt === 'all' ? 'All time' : opt }}
            </button>
          </div>

          <!-- Severity -->
          <div class="flex items-center gap-1">
            <span class="text-slate-500">Severity</span>
            <button
              type="button"
              class="rounded-full px-2 py-1 border text-[11px]"
              :class="severityFilter === 'all'
                ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'
                : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
              @click="severityFilter = 'all'"
            >
              All
            </button>
            <button
              v-for="sev in ['SEV1', 'SEV2', 'SEV3']"
              :key="sev"
              type="button"
              class="rounded-full px-2 py-1 border text-[11px]"
              :class="severityFilter === sev
                ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'
                : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
              @click="severityFilter = sev"
            >
              {{ sev }}
            </button>
          </div>

          <!-- Status -->
          <div class="flex items-center gap-1">
            <span class="text-slate-500">Status</span>
            <select
              v-model="statusFilter"
              class="rounded-full border border-slate-300 bg-white px-2 py-1 text-[11px] text-slate-700 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
            >
              <option value="all">All</option>
              <option value="investigating">Investigating</option>
              <option value="mitigating">Mitigating</option>
              <option value="monitoring">Monitoring</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
        </div>

        <!-- Search -->
        <div class="flex items-center gap-2">
          <div class="relative w-full md:w-60">
            <input
              v-model="search"
              type="search"
              placeholder="Search incidents by title, key, system"
              class="w-full rounded-full border border-slate-300 bg-white pl-8 pr-3 py-1.5 text-[11px] text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:placeholder:text-slate-500"
            />
            <span
              class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 dark:text-slate-500"
            >
              🔎
            </span>
          </div>
        </div>
      </section>

      <!-- Main grid: list + details -->
      <section class="grid w-full max-w-full gap-4 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1.4fr)]">
        <!-- Incident list -->
        <div
          class="w-full max-w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/5 flex flex-col dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
        >
          <div class="mb-2 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
            <span>
              {{ filteredIncidents.length }} incident{{ filteredIncidents.length === 1 ? '' : 's' }} in view
            </span>
          </div>

          <div class="space-y-2 overflow-y-auto max-h-[565px] pr-1 scroll-thin">
            <button
              v-for="incident in filteredIncidents"
              :key="incident.id"
              type="button"
              class="w-full text-left rounded-xl border px-3 py-2.5 transition flex flex-col gap-1.5"
              :class="[
                'border-slate-200 bg-white hover:border-emerald-400/60 hover:bg-emerald-50/40 dark:border-slate-800 dark:bg-slate-900/80 dark:hover:bg-slate-900',
                selectedIncident && selectedIncident.id === incident.id
                  ? 'ring-1 ring-emerald-400/70'
                  : '',
              ]"
              @click="selectIncident(incident.id)"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-mono"
                    :class="severityColor(incident.severity)"
                  >
                    {{ incident.severity }}
                  </span>
                  <span class="text-xs font-medium text-slate-900 truncate max-w-[180px] dark:text-slate-100">
                    {{ incident.title }}
                  </span>
                </div>
                <span
                  class="rounded-full px-2 py-0.5 text-[10px]"
                  :class="statusChipClasses(incident.status)"
                >
                  {{ statusLabel(incident.status) }}
                </span>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-slate-500 dark:text-slate-400">
                <span class="truncate">
                  <span class="text-slate-500">System</span>
                  <span class="mx-1">·</span>
                  <span class="text-slate-800 dark:text-slate-200">{{ incident.system }}</span>
                </span>
                <span class="whitespace-nowrap text-slate-500">
                  Started {{ relativeTime(incident.startedAt) }}
                </span>
              </div>

              <p class="text-[11px] text-slate-500 line-clamp-2 dark:text-slate-400">
                {{ incident.summary }}
              </p>

              <div class="flex flex-wrap gap-1 text-[10px] text-slate-500 dark:text-slate-400">
                <span
                  v-for="tag in incident.tags"
                  :key="tag"
                  class="rounded-full bg-slate-100 px-2 py-0.5 dark:bg-slate-800/80"
                >
                  {{ tag }}
                </span>
              </div>
            </button>

            <div
              v-if="filteredIncidents.length === 0"
              class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-[11px] text-slate-500 dark:border-slate-700/80 dark:bg-slate-900/60 dark:text-slate-400"
            >
              No incidents match the current filters. Adjust the severity, status, or time window
              to explore the incident data.
            </div>
          </div>
        </div>

        <!-- Incident details -->
        <div
          class="w-full max-w-full rounded-2xl border border-slate-200 bg-white p-4 shadow-lg shadow-slate-900/5 flex flex-col dark:border-slate-800 dark:bg-slate-900/80 dark:shadow-slate-950/60"
        >
          <template v-if="selectedIncident">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3">
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span
                    class="rounded-full px-2 py-0.5 text-[10px] font-mono"
                    :class="severityColor(selectedIncident.severity)"
                  >
                    {{ selectedIncident.severity }}
                  </span>
                  <span
                    v-if="incidentIsLive(selectedIncident)"
                    class="inline-flex items-center gap-1 rounded-full bg-rose-50 px-2 py-0.5 text-[10px] text-rose-700 border border-rose-200 dark:bg-rose-500/15 dark:text-rose-200 dark:border-rose-500/40"
                  >
                    <span class="h-1.5 w-1.5 rounded-full bg-rose-400 animate-pulse"></span>
                    Live
                  </span>
                  <span class="text-[11px] text-slate-500">
                    {{ selectedIncident.key }}
                  </span>
                </div>
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  {{ selectedIncident.title }}
                </h2>
                <p class="text-[11px] text-slate-600 dark:text-slate-300">
                  {{ selectedIncident.summary }}
                </p>
              </div>

              <div class="flex flex-col items-end gap-1 text-[11px] text-slate-500 dark:text-slate-400">
                <span>
                  Owner
                  <span class="text-slate-900 dark:text-slate-200">{{ selectedIncident.owner }}</span>
                </span>
                <span>
                  Started
                  <span class="text-slate-900 dark:text-slate-200">{{ relativeTime(selectedIncident.startedAt) }}</span>
                </span>
                <span>
                  Updated
                  <span class="text-slate-900 dark:text-slate-200">{{
                    relativeTime(selectedIncident.lastUpdatedAt || selectedIncident.startedAt)
                  }}</span>
                </span>
              </div>
            </div>

            <!-- Quick status change -->
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
              <span class="text-slate-500">
                Status:
              </span>
              <button
                v-for="opt in ['investigating', 'mitigating', 'monitoring', 'resolved']"
                :key="opt"
                type="button"
                class="rounded-full border px-2 py-0.5"
                :class="opt === selectedIncident.status
                  ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'
                  : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
                @click="openStatusChangeModal(selectedIncident, opt)"
              >
                {{ statusLabel(opt) }}
              </button>
            </div>

            <!-- Meta chips -->
            <div class="mt-3 flex flex-wrap gap-2 text-[10px] text-slate-700 dark:text-slate-300">
              <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800/80">
                System: {{ selectedIncident.system }}
              </span>
              <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800/80">
                Regions: {{ selectedIncident.impactedRegions.join(', ') }}
              </span>
              <span class="rounded-full bg-slate-100 px-3 py-1 dark:bg-slate-800/80">
                Impact: {{ selectedIncident.impactedUsers }}
              </span>
            </div>

            <!-- Content grid -->
            <div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
              <!-- Timeline -->
              <div
                class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/40"
              >
                <div
                  class="mb-2 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400"
                >
                  <span class="uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">
                    Timeline of events
                  </span>
                  <span>
                    {{ selectedIncident.timeline.length }} step
                    {{ selectedIncident.timeline.length === 1 ? '' : 's' }}
                  </span>
                </div>

                <!-- scrollable wrapper -->
                <div class="max-h-[395px] overflow-y-auto pr-1 scroll-thin">
                  <ol
                    class="relative border-l border-slate-200 pl-3 space-y-3 text-[11px] dark:border-slate-700/70"
                  >
                    <li
                      v-for="item in selectedIncident.timeline"
                      :key="item.id"
                      class="relative pl-3"
                    >
                      <span
                        class="absolute -left-2.5 top-1 h-2.5 w-2.5 rounded-full border border-white dark:border-slate-900"
                        :class="eventDotClass(item.type)"
                      ></span>

                      <div class="flex items-center justify-between gap-2">
                        <p class="font-medium text-slate-900 dark:text-slate-100">
                          {{ item.label }}
                        </p>
                        <span class="whitespace-nowrap text-slate-500 dark:text-slate-500">
                          {{ relativeTime(item.at) }}
                        </span>
                      </div>
                      <p class="mt-0.5 text-slate-700 dark:text-slate-300">
                        {{ item.detail }}
                      </p>
                      <p class="mt-0.5 text-slate-500">
                        {{ item.actor }}
                      </p>
                    </li>
                  </ol>
                </div>
              </div>


              <!-- Follow ups and resilience -->
              <div class="space-y-3">
                <div
                  class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-[11px]
                        dark:border-slate-800 dark:bg-slate-950/40"
                >
                  <div class="mb-2 flex items-center justify-between">
                    <span class="uppercase tracking-[0.16em] text-slate-500 dark:text-slate-500">
                      Follow up work
                    </span>
                    <span class="text-slate-500 dark:text-slate-400">
                      {{ selectedIncident.followUps.length }} item{{ selectedIncident.followUps.length === 1 ? '' : 's' }}
                    </span>


                    
                  </div>



                  <ul class="space-y-1.5">
                    <li
                      v-for="a in selectedIncident.followUps"
                      :key="a.id"
                      class="rounded-lg border border-slate-200 bg-white px-3 py-2 flex flex-col gap-1.5 dark:border-slate-800 dark:bg-slate-950/60"
                    >
                      <!-- Top row: bullet + text -->
                      <div class="flex items-start gap-2">
                        <span
                          class="mt-1 h-1.5 w-1.5 rounded-full flex-shrink-0"
                          :class="{
                            'bg-emerald-400': a.status === 'done',
                            'bg-sky-400': a.status === 'in_progress',
                            'bg-amber-400': a.status === 'open',
                            'bg-slate-500': !['open', 'in_progress', 'done'].includes(a.status),
                          }"
                        ></span>

                        <div class="flex-1 min-w-0">
                          <p class="text-slate-900 text-[11px] break-words dark:text-slate-200">
                            {{ a.label }}
                          </p>
                          <p class="text-[11px] text-slate-500 dark:text-slate-500">
                            Owner: {{ a.owner }}
                          </p>
                        </div>
                      </div>

                      <!-- Second row: status quick toggles -->
                      <div class="flex flex-wrap gap-1 mt-1">
                        <button
                          v-for="opt in ['open', 'in_progress', 'done']"
                          :key="opt"
                          type="button"
                          class="rounded-full px-2 py-0.5 text-[10px] border"
                          :class="opt === a.status
                            ? 'border-emerald-400 bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200'
                            : 'border-slate-300 bg-white text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
                          @click="changeFollowUpStatus(a.id, opt)"
                        >
                          {{ followUpStatusLabel(opt) }}
                        </button>
                      </div>
                    </li>
                  </ul>
                  <div class="flex items-center gap-2 mt-2">
                    <button
                      type="button"
                      @click="openFollowUpModal"
                      class="rounded-full bg-emerald-500 px-3 py-1 text-[11px] font-semibold
                            text-slate-950 hover:bg-emerald-400"
                    >
                      New follow up
                    </button>
                  </div>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 text-[11px] dark:border-slate-800 dark:bg-slate-950/40">
                  <p class="uppercase tracking-[0.16em] text-slate-500 mb-1.5 dark:text-slate-500">
                    How this demonstrates my experience
                  </p>
                  <p class="text-slate-700 mb-1.5 dark:text-slate-300">
                    In the real version of this tool, incidents would be driven by database models
                    and API endpoints. The same patterns I used for TaskFlow apply:
                  </p>
                  <ul class="list-disc pl-4 space-y-0.5 text-slate-600 dark:text-slate-400">
                    <li>Normalized incident, event, and follow up tables</li>
                    <li>Read optimized queries for the list plus detail view</li>
                    <li>Server side filters backed by indexes for severity and time</li>
                    <li>Streaming updates via polling or websockets for live incidents</li>
                  </ul>
                </div>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="flex h-full items-center justify-center text-[11px] text-slate-500 dark:text-slate-400">
              Choose an incident from the left to view its impact, timeline, and follow up work.
            </div>
          </template>
        </div>
      </section>
    </main>
    </div>
    <!-- Create incident modal -->
    <transition name="fade">
      <div
        v-if="isCreateOpen"
        class="fixed inset-0 z-[200] flex items-start justify-center overflow-y-auto bg-slate-900/40 backdrop-blur-sm dark:bg-slate-950/70"
      >
        <div
          class="w-full sm:mt-32 max-w-lg rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-900/20 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/80"
        >
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                Open new incident
              </h2>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Capture the key context so it shows up in the command center.
              </p>
            </div>
            <button
              type="button"
              @click="closeCreateIncident"
              class="text-slate-400 hover:text-slate-900 text-sm dark:hover:text-slate-100"
            >
              ✕
            </button>
          </div>

          <form class="space-y-3" @submit.prevent="submitNewIncident">
            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-1">
                <label class="text-[11px] text-slate-700 dark:text-slate-300">Title</label>
                <input
                  v-model="newIncident.title"
                  type="text"
                  required
                  class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                  placeholder="Spike in 5xx from payment service"
                />
              </div>

              <div class="space-y-1">
                <label class="text-[11px] text-slate-700 dark:text-slate-300">System</label>
                <input
                  v-model="newIncident.system"
                  type="text"
                  required
                  class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                  placeholder="Payments, auth, search..."
                />
              </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-1">
                <label class="text-[11px] text-slate-700 dark:text-slate-300">Severity</label>
                <select
                  v-model="newIncident.severity"
                  class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                >
                  <option value="SEV1">SEV1 - critical</option>
                  <option value="SEV2">SEV2 - major</option>
                  <option value="SEV3">SEV3 - minor</option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="text-[11px] text-slate-700 dark:text-slate-300">Impacted users</label>
                <input
                  v-model="newIncident.impacted_users"
                  type="text"
                  class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                  placeholder="About 20 percent of checkouts"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="text-[11px] text-slate-700 dark:text-slate-300">Summary</label>
              <textarea
                v-model="newIncident.summary"
                rows="3"
                class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                placeholder="What is happening, what is currently known, suspected root cause..."
              ></textarea>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
              <div class="space-y-1">
                <label class="text-[11px] text-slate-700 dark:text-slate-300">Impacted regions</label>
                <input
                  v-model="newIncident.impacted_regions_raw"
                  type="text"
                  class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                  placeholder="US-East, US-West"
                />
                <p class="text-[10px] text-slate-500 dark:text-slate-500">
                  Comma separated. Will show as chips.
                </p>
              </div>

              <div class="space-y-1">
                <label class="text-[11px] text-slate-700 dark:text-slate-300">Tags</label>
                <input
                  v-model="newIncident.tags_raw"
                  type="text"
                  class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                  placeholder="payments, 5xx, checkout"
                />
                <p class="text-[10px] text-slate-500 dark:text-slate-500">
                  Comma separated tags for search and filter.
                </p>
              </div>
            </div>

            <div class="mt-3 flex justify-end gap-2">
              <button
                type="button"
                @click="closeCreateIncident"
                class="rounded-full border border-slate-300 px-3 py-1.5 text-[11px] text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="rounded-full bg-emerald-500 px-4 py-1.5 text-[11px] font-semibold text-slate-950 hover:bg-emerald-400"
              >
                Create incident
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

    <!-- Status change modal -->
    <transition name="fade">
      <div
        v-if="isStatusModalOpen"
        class="fixed inset-0 z-[210] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm dark:bg-slate-950/70"
      >
        <div
          class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-900/20 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/80"
        >
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                Update incident status
              </h2>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Add a short note so the timeline tells the story of this change.
              </p>
            </div>
            <button
              type="button"
              @click="isStatusModalOpen = false"
              class="text-slate-400 hover:text-slate-900 text-sm dark:hover:text-slate-100"
            >
              ✕
            </button>
          </div>

          <div class="mb-3 text-[11px] text-slate-700 dark:text-slate-300">
            <p>
              Changing from
              <span class="font-semibold">
                {{ statusLabel(statusChange.fromStatus) || statusChange.fromStatus }}
              </span>
              to
              <span class="font-semibold text-emerald-600 dark:text-emerald-300">
                {{ statusLabel(statusChange.toStatus) || statusChange.toStatus }}
              </span>
              for
              <span class="font-mono text-slate-900 dark:text-slate-100">
                {{ selectedIncident?.key }}
              </span>
            </p>
          </div>

          <form class="space-y-3" @submit.prevent="submitStatusChange">
            <div class="space-y-1">
              <label class="text-[11px] text-slate-700 dark:text-slate-300">Actor</label>
              <input
                v-model="statusChange.actor"
                type="text"
                class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                placeholder="On call engineer, SRE, support lead..."
              />
            </div>

            <div class="space-y-1">
              <label class="text-[11px] text-slate-700 dark:text-slate-300">What changed</label>
              <textarea
                v-model="statusChange.note"
                rows="3"
                class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                placeholder="Mitigation applied, rollback completed, traffic shifted, alerts cleared..."
              ></textarea>
            </div>

            <div class="mt-3 flex justify-end gap-2">
              <button
                type="button"
                @click="isStatusModalOpen = false"
                class="rounded-full border border-slate-300 px-3 py-1.5 text-[11px] text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="rounded-full bg-emerald-500 px-4 py-1.5 text-[11px] font-semibold text-slate-950 hover:bg-emerald-400"
              >
                Save status change
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>
    <!-- New follow up modal -->
    <transition name="fade">
      <div
        v-if="isFollowUpModalOpen"
        class="fixed inset-0 z-[205] flex items-center justify-center bg-slate-900/40 backdrop-blur-sm dark:bg-slate-950/70"
      >
        <div
          class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl shadow-slate-900/20 dark:border-slate-800 dark:bg-slate-900 dark:shadow-slate-950/80"
        >
          <div class="flex items-start justify-between gap-3 mb-3">
            <div>
              <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                Add follow up action
              </h2>
              <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Capture post incident work so it does not get lost once the incident is resolved.
              </p>
            </div>
            <button
              type="button"
              @click="closeFollowUpModal"
              class="text-slate-400 hover:text-slate-900 text-sm dark:hover:text-slate-100"
            >
              ✕
            </button>
          </div>

          <form class="space-y-3" @submit.prevent="submitNewFollowUp">
            <div class="space-y-1">
              <label class="text-[11px] text-slate-700 dark:text-slate-300">
                Follow up action
              </label>
              <input
                v-model="newFollowUp.label"
                type="text"
                class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                placeholder="Run post incident review, improve alerts, update playbook..."
                required
              />
            </div>

            <div class="space-y-1">
              <label class="text-[11px] text-slate-700 dark:text-slate-300">
                Owner (optional)
              </label>
              <input
                v-model="newFollowUp.owner"
                type="text"
                class="w-full rounded-md border border-slate-300 bg-white px-2 py-1.5 text-[11px] text-slate-900 focus:outline-none focus:ring-1 focus:ring-emerald-400 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-50"
                placeholder="On call engineer, SRE lead..."
              />
            </div>

            <div class="mt-3 flex justify-end gap-2">
              <button
                type="button"
                @click="closeFollowUpModal"
                class="rounded-full border border-slate-300 px-3 py-1.5 text-[11px] text-slate-700 hover:border-slate-400 dark:border-slate-700 dark:text-slate-200 dark:hover:border-slate-500"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="rounded-full bg-emerald-500 px-4 py-1.5 text-[11px] font-semibold text-slate-950 hover:bg-emerald-400"
              >
                Save follow up
              </button>
            </div>
          </form>
        </div>
      </div>
    </transition>

  </div>
</template>


<style scoped>

.scroll-thin {
  /* Firefox */
  scrollbar-width: thin;
  scrollbar-color: rgba(148, 163, 184, 0.5) transparent;
}

/* WebKit (Chrome, Edge, Safari) */
.scroll-thin::-webkit-scrollbar {
  width: 6px;
}

.scroll-thin::-webkit-scrollbar-track {
  background: transparent;
}

.scroll-thin::-webkit-scrollbar-thumb {
  background-color: rgba(148, 163, 184, 0.5); /* slate-400-ish, slightly opaque */
  border-radius: 9999px;
}

</style>