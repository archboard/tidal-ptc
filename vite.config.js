import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwind from '@tailwindcss/vite'

// ponytail: v-calendar 3.1.2 (latest) crashes with "reading 'dayIndex'" when the popover
// calendar unmounts (days = []) while attributes recompute. Drop once upstream ships a fix.
const patchVCalendar = () => ({
  name: 'patch-v-calendar-empty-days',
  transform (code, id) {
    if (!id.includes('v-calendar') || !code.includes('const startDayIndex = days[0].dayIndex;')) {
      return
    }

    return code.replace(
      'const startDayIndex = days[0].dayIndex;',
      'if (!days.length) return null;\n    const startDayIndex = days[0].dayIndex;',
    )
  },
})

export default ({ mode }) => {
  // process.env = {
  //   ...process.env,
  //   ...loadEnv(mode, process.cwd(), 'APP_'),
  // }

  return defineConfig({
    // The dev pre-bundler skips plugin transforms, so keep v-calendar out of it for the patch above
    optimizeDeps: { exclude: ['v-calendar'] },
    plugins: [
      patchVCalendar(),
      tailwind(),
      laravel({
        input: ['resources/js/app.js'],
        refresh: true,
      }),
      vue({
        template: {
          transformAssetUrls: {
            base: null,
            includeAbsolute: false,
          },
        }
      })
    ],
    // If you need to add a non-Valet certificate:
    // server: {
    //   https: {
    //     key: process.env.APP_SSL_KEY,
    //     cert: process.env.APP_SSL_CERT,
    //   },
    //   domain,
    //   hmr: {
    //     host: domain,
    //   }
    // }
  })
}
