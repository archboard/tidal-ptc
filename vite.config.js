import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default ({ mode }) => {
  process.env = {
    ...process.env,
    ...loadEnv(mode, process.cwd(), 'APP_'),
  }
  const domain = (new URL(process.env.APP_URL)).hostname

  return defineConfig({
    plugins: [
      laravel({
        input: [
          'resources/js/app.js',
        ],
        refresh: true,
        valetTls: domain.split('.').slice(-2).join('.'),
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
