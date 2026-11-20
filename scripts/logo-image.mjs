/**
 * Renders the guest-layout logo + lava background as a square PNG.
 *
 *   npm run logo-image              # 2000x2000 -> storage/app/logo-lava.png
 *   npm run logo-image -- 3000      # custom size
 *   npm run logo-image -- 2000 solid  # solid primary background -> storage/app/logo-solid.png
 *
 * Needs the app served (Herd) and `npx playwright install chromium`. Override the target with LOGO_IMAGE_URL.
 */
import { chromium } from 'playwright'

const size = Number(process.argv[2] ?? 2000)
const url = process.env.LOGO_IMAGE_URL ?? 'https://tidal-ptc.test/login'
const solid = process.argv[3] === 'solid'
const out = new URL(`../storage/app/logo-${solid ? 'solid' : 'lava'}.png`, import.meta.url).pathname

const browser = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader'] })
// 2x device scale: lay out at half size so the logo and render resolution match the final pixels
const page = await browser.newPage({ viewport: { width: size / 2, height: size / 2 }, deviceScaleFactor: 2, ignoreHTTPSErrors: true })
await page.goto(url)

const lava = page.locator('div:has(> canvas)').first()
await lava.evaluate((el, solid) => {
  el.style.cssText = 'position:fixed;inset:0;margin:0;border-radius:0;z-index:99999;min-height:0'
  const logo = el.querySelector('div')
  logo.style.maxWidth = 'none'
  logo.style.background = '#000'
  if (solid) {
    el.querySelector('canvas').style.display = 'none'
    el.className = 'bg-primary-400'
  }
}, solid)
await page.waitForTimeout(1000)
await lava.screenshot({ path: out })
await browser.close()
console.log(`Saved ${size}x${size} to ${out}`)
