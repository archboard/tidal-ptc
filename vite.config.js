import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwind from '@tailwindcss/vite'

export default ({ mode }) => {
  // process.env = {
  //   ...process.env,
  //   ...loadEnv(mode, process.cwd(), 'APP_'),
  // }

  return defineConfig({
    plugins: [
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
