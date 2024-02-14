import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers'
import * as plugins from '@/plugins'
import components from '@/components'
import get from 'just-safe-get'
import flashesNotifications from '@/plugins/flashesNotifications.js'
import '../css/app.css'

createInertiaApp({
  title: title => title ? `${title} | ${import.meta.env.APP_NAME}` : import.meta.env.APP_NAME,
  progress: {
    delay: 0,
    color: '#14b8a6',
  },
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob('./pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const app = createApp({ render: () => h(App, props) })
      .use(plugin)

    // Register all the plugins
    Object.values(plugins).forEach(app.use)

    // Register global components
    Object.keys(components).forEach(componentName => {
      app.component(componentName, components[componentName])
    })

    // Mount the app
    app.mount(el)

    el.removeAttribute('data-page')
    flashesNotifications(get(props, 'initialPage.props.flash'))
  },
})
