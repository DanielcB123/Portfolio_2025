import '../css/app.css'
import './bootstrap'
import 'vue3-toastify/dist/index.css'

import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import { createApp, h } from 'vue'

import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'
import { Ziggy } from './ziggy'

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
      .use(ZiggyVue, Ziggy)

    vueApp.mount(el)
  },
  progress: {
    color: '#4B5563',
  },
})
