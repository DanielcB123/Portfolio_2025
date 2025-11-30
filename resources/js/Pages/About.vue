<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
  <div
    class="relative min-h-screen overflow-hidden bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-50"
  >
    <Head title="About Daniel" />

    <!-- Glowing gradient orbs -->
    <div class="pointer-events-none absolute inset-0">
      <div
        class="absolute -top-32 -left-16 h-72 w-72 rounded-full bg-gradient-to-br from-blue-500 via-emerald-400 to-cyan-400 opacity-40 blur-3xl animate-orb-float"
      ></div>
    </div>

    <!-- Subtle grid background -->
    <div
      class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.04)_0,_rgba(15,23,42,0.35)_55%)] dark:bg-[radial-gradient(circle_at_top,_rgba(148,163,184,0.12)_0,_transparent_55%),linear-gradient(to_bottom,_rgba(15,23,42,0.7)_0,_rgba(15,23,42,1)_55%)]"
    ></div>
    <div
      class="pointer-events-none absolute inset-0 opacity-30 mix-blend-soft-light [background-image:linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] [background-size:56px_56px] dark:[background-image:linear-gradient(to_right,#020617_1px,transparent_1px),linear-gradient(to_bottom,#020617_1px,transparent_1px)]"
    ></div>

    <!-- Content -->
    <div class="relative z-10 flex min-h-screen flex-col">
      <!-- Top bar -->
      <header class="px-6 pt-10 sm:px-10 sm:pt-8">
        <div class="mx-auto flex w-full max-w-6xl items-center justify-between">
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

          <div
            class="inline-flex items-center gap-2 rounded-full border border-slate-300/70 bg-white/80 px-3 py-1.5 text-[10px] uppercase tracking-[0.18em] text-slate-600 shadow-lg shadow-slate-200/70 dark:border-slate-700/70 dark:bg-slate-950/70 dark:text-slate-400 dark:shadow-slate-900/80"
          >
            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 animate-ping-once"></span>
            <span>About</span>
          </div>
        </div>
      </header>

      <!-- Main layout -->
      <main class="flex-1 px-6 pb-16 pt-6 sm:px-10 sm:pt-8">
        <div
          class="mx-auto flex w-full max-w-6xl flex-col gap-10 lg:flex-row lg:items-start"
        >
          <!-- Left column: story -->
          <section class="about-fade-1 space-y-6 lg:w-3/5">
            <div class="space-y-3">
              <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">
                Behind the code
              </p>
              <h1
                class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight leading-tight text-slate-900 dark:text-slate-50"
              >
                From job sites and outages to resilient products and teams.
              </h1>
            </div>
            <!-- Large portrait -->
            <div class="relative about-fade-1">
              <!-- Glow behind image -->
              <div
                class="pointer-events-none absolute -inset-4 rounded-3xl bg-gradient-to-br from-emerald-400/20 via-cyan-400/15 to-blue-500/20 blur-2xl"
              ></div>

              <img
                src="/images/image2.png"  
                alt="Photo of Daniel Burgess"
                class="relative w-50 h-auto max-h-[480px] object-cover rounded-3xl border border-slate-200 shadow-2xl dark:border-slate-700 dark:shadow-slate-950/80"
              />
            </div>
            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
              I grew up around hands on work, having spent years in the carpenters union before graduating university 
              with a degree in computer science and moving into software. That background shaped how I think about
              building things. You design for the people on the ground, you expect edge cases, and you fix what 
              breaks and keep moving forward.
            </p>

            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
              In my recent roles I’ve worked on large, real-world operational systems, from a nationwide SaaS 
              warranty platform coordinating service providers across all 50 states to HVAC, electrical, plumbing, 
              and solar applications used daily by field teams. I’ve built and maintained the dispatch flows, 
              mobile tools, internal portals, and all the glue that connects them. I’ve shipped features under 
              live traffic, handled late-night outages, and cared as much about reliability and logs as I do about 
              clean UX.
            </p>

            <p class="text-sm sm:text-base text-slate-600 dark:text-slate-300 leading-relaxed">
              My current focus is on building reliable tools that help teams understand their operations and act with confidence. I like work where backend design, UI clarity, and meaningful data come together to solve real problems instead of creating more noise.
            </p>

            <div
              class="mt-4 inline-flex flex-wrap gap-2 text-[11px] text-slate-600 dark:text-slate-300"
            >
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/80 dark:bg-slate-900/80"
              >
                Based in the US, open to remote
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/80 dark:bg-slate-900/80"
              >
                Practical, calm under pressure
              </span>
              <span
                class="rounded-full border border-slate-300/80 bg-white/80 px-3 py-1 dark:border-slate-700/80 dark:bg-slate-900/80"
              >
                Comfortable in legacy or greenfield
              </span>
            </div>
          </section>

          <!-- Right column: cards and timeline -->
          <section class="about-fade-2 relative lg:w-2/5">
            <!-- Halo -->
            <div
              class="pointer-events-none absolute -inset-6 rounded-[32px] bg-gradient-to-tr from-emerald-500/15 via-cyan-500/10 to-blue-500/15 blur-2xl"
            ></div>

            <div class="relative space-y-4">
              <!-- Card: What I value on a team -->
              <article
                class="group rounded-2xl border border-slate-200/80 bg-white/90 p-4 text-slate-800 shadow-2xl shadow-slate-200/80 backdrop-blur-lg transition-transform duration-300 hover:border-emerald-400/80 hover:shadow-emerald-500/30 dark:border-slate-700/70 dark:bg-slate-950/90 dark:text-slate-50 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/40"
              >
                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    What I look for in a team
                  </h2>
                  <span
                    class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-700 dark:text-emerald-300"
                  >
                    Collaboration first
                  </span>
                </div>

                <p class="mt-2 text-xs text-slate-600 dark:text-slate-300"> I value constructive reviews, and the space to move quickly. Short 
                  feedback loops with design, product, and ops keep features aligned with how the work actually happens. </p>

                <ul class="mt-3 space-y-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                  <li>• Honest communication and calm debugging</li>
                  <li>• Interest in tests and monitoring, not just hotfixes</li>
                  <li>• Curiosity about the people who actually use the tools</li>
                </ul>
              </article>

              <!-- Card: How I like to work -->
              <article
                class="group rounded-2xl border border-slate-200/80 bg-white/90 p-4 text-slate-800 shadow-2xl shadow-slate-200/80 backdrop-blur-lg transition-transform duration-300 hover:border-emerald-400/80 hover:shadow-emerald-500/30 dark:border-slate-700/70 dark:bg-slate-950/90 dark:text-slate-50 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/40"
              >
                <div class="flex items-center justify-between gap-4">
                  <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                    How I like to work
                  </h2>
                  <span
                    class="inline-flex items-center gap-1 rounded-full bg-blue-500/10 px-2 py-0.5 text-[10px] font-medium text-blue-700 dark:text-blue-300"
                  >
                    Builder mindset
                  </span>
                </div>

                <p class="mt-2 text-xs text-slate-600 dark:text-slate-300">
                  I move from high level shape to concrete steps. Sketch flows, outline tables and
                  interfaces, then break work into pieces that can ship in real increments.
                </p>

                <ul class="mt-3 space-y-1.5 text-[11px] text-slate-600 dark:text-slate-300">
                  <li>• Start with data and user stories together</li>
                  <li>• Keep pull requests focused and reviewable</li>
                  <li>• Leave comments and docs for future changes</li>
                </ul>
              </article>

              <!-- Mini timeline -->
              <article
                class="group rounded-2xl border border-slate-200/80 bg-white/90 p-4 text-slate-800 shadow-2xl shadow-slate-200/80 backdrop-blur-lg transition-transform duration-300 hover:border-emerald-400/80 hover:shadow-emerald-500/30 dark:border-slate-700/70 dark:bg-slate-950/90 dark:text-slate-50 dark:shadow-slate-950/80 dark:hover:shadow-emerald-500/40"
              >
                <h2 class="text-sm font-semibold text-slate-900 dark:text-slate-50">
                  A quick timeline
                </h2>

                <ol class="mt-3 space-y-3 text-[11px] text-slate-600 dark:text-slate-300">
                  <li class="flex gap-3">
                    <div
                      class="mt-1 h-5 w-5 flex-none rounded-full bg-emerald-500/10 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300 flex items-center justify-center"
                    >
                      01
                    </div>
                    <div>
                      <p class="font-medium text-slate-900 dark:text-slate-100">
                        Construction and hands on work
                      </p>
                      <p class="text-slate-500 dark:text-slate-400">
                        Learned how real projects slip, recover, and still ship on time.
                      </p>
                    </div>
                  </li>
                  <li class="flex gap-3">
                    <div
                      class="mt-1 h-5 w-5 flex-none rounded-full bg-cyan-500/10 text-[10px] font-semibold text-cyan-700 dark:bg-cyan-500/20 dark:text-cyan-300 flex items-center justify-center"
                    >
                      02
                    </div>
                    <div>
                      <p class="font-medium text-slate-900 dark:text-slate-100">
                        Enterprise operations software
                      </p>
                      <p class="text-slate-500 dark:text-slate-400">
                        Built tools for service companies with live dispatch, call center, billing, and
                        technician apps.
                      </p>
                    </div>
                  </li>
                  <li class="flex gap-3">
                    <div
                      class="mt-1 h-5 w-5 flex-none rounded-full bg-indigo-500/10 text-[10px] font-semibold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300 flex items-center justify-center"
                    >
                      03
                    </div>
                    <div>
                      <p class="font-medium text-slate-900 dark:text-slate-100">
                        Nationwide SaaS warranty platform
                      </p>
                      <p class="text-slate-500 dark:text-slate-400">
                        Built tools connecting customers, manufacturers, and service providers across all 50 states.
                      </p>
                    </div>
                  </li>
                  <li class="flex gap-3">
                    <div
                      class="mt-1 h-5 w-5 flex-none rounded-full bg-blue-500/10 text-[10px] font-semibold text-blue-700 dark:bg-blue-500/20 dark:text-blue-300 flex items-center justify-center"
                    >
                      04
                    </div>
                    <div>
                      <p class="font-medium text-slate-900 dark:text-slate-100">
                        Civic and product focused work
                      </p>
                      <p class="text-slate-500 dark:text-slate-400">
                        Now applying the same discipline to platforms that help people understand
                        data, make choices, and get real work done.
                      </p>
                    </div>
                  </li>
                </ol>
              </article>
            </div>
          </section>
        </div>
      </main>
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

/* Staggered content entrance */
@keyframes about-fade-up {
  0% {
    opacity: 0;
    transform: translateY(18px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.about-fade-1,
.about-fade-2,
.about-fade-3,
.about-fade-4 {
  opacity: 0;
  animation-name: about-fade-up;
  animation-duration: 0.7s;
  animation-timing-function: ease-out;
  animation-fill-mode: forwards;
}

.about-fade-1 {
  animation-delay: 0.1s;
}

.about-fade-2 {
  animation-delay: 0.25s;
}

.about-fade-3 {
  animation-delay: 0.4s;
}

.about-fade-4 {
  animation-delay: 0.55s;
}
</style>
