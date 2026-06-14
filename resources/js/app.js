import '../css/app.css'
import './bootstrap'
import 'vue3-toastify/dist/index.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

import { ZiggyVue } from 'ziggy-js'
import { route as ziggyRoute } from 'ziggy-js'
import { Ziggy as ZiggyFallback } from './ziggy'
import { configureAppRoute } from './utils/appRoute'

const ziggyBase =
  typeof window !== 'undefined' && window.Ziggy ? window.Ziggy : ZiggyFallback

const ziggy =
  typeof window !== 'undefined'
    ? {
        ...ziggyBase,
        // Ziggy uses `url` as the full origin for absolute URLs; `port` is subdomain-only.
        url: window.location.origin,
        port: null,
      }
    : ziggyBase

configureAppRoute(ziggy)


const appName = import.meta.env.VITE_APP_NAME || 'Laravel'
console.log('Booting Inertia app', import.meta.env.MODE)

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./Pages/${name}.vue`,
      import.meta.glob('./Pages/**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    const vueApp = createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue, ziggy)

    vueApp.config.globalProperties.route = (name, params = {}, absolute = false) =>
      ziggyRoute(name, params, absolute, ziggy)

    vueApp.mount(el)
  },
  progress: {
    color: '#4B5563',
  },
})
