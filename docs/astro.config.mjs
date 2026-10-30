// @ts-check
import { defineConfig } from 'astro/config';
import starlight from '@astrojs/starlight';
import tailwindcss from '@tailwindcss/vite';
import starlightLlmsTxt from 'starlight-llms-txt';
import starlightLinksValidator from 'starlight-links-validator';

import { SITE_URL, GITHUB_URL } from './src/config.ts';

export default defineConfig({
  site: SITE_URL,
  trailingSlash: 'always',
  integrations: [
    starlight({
      title: 'Tidal PTC',
      description: 'Documentation for Tidal PTC, the open source parent-teacher conference scheduler with PowerSchool SSO and sync.',
      logo: { light: './src/assets/archboard-light.svg', dark: './src/assets/archboard-dark.svg', alt: 'Archboard' },
      favicon: '/favicon.png',
      social: [{ icon: 'github', label: 'GitHub', href: GITHUB_URL }],
      editLink: { baseUrl: `${GITHUB_URL}/edit/main/docs/` },
      lastUpdated: true,
      customCss: ['./src/styles/global.css'],
      components: {
        Head: './src/components/Head.astro',
        Footer: './src/components/Footer.astro',
      },
      head: [
        { tag: 'meta', attrs: { property: 'og:image', content: `${SITE_URL}/og.png` } },
        { tag: 'meta', attrs: { name: 'twitter:card', content: 'summary_large_image' } },
      ],
      plugins: [
        starlightLinksValidator(),
        starlightLlmsTxt({
          projectName: 'Tidal PTC',
          description: 'Tidal PTC is an open source (AGPL-3.0) web application by Archboard for scheduling parent-teacher conferences. Admins and teachers create time slots; parents and guardians sign in (usually through PowerSchool SSO) and book them.',
          details: '- It can be self-hosted (Docker or a VM) or bought as managed Cloud or Private dedicated hosting.\n- Each district is a tenant; a tenant has schools, and users act within a current school.\n- User types are staff, guardian (contact) and student. Staff abilities come from per-school permissions.',
          promote: ['getting-started/**', 'installation/**'],
          demote: ['reference/changelog'],
          // One copy of each screenshot in llms-full.txt, none in llms-small.txt
          customSelectors: { all: ['img.dark-only'], small: ['figure.screenshot'] },
        }),
      ],
      sidebar: [
        { label: 'Getting started', items: [{ autogenerate: { directory: 'getting-started' } }] },
        { label: 'Installation', items: [{ autogenerate: { directory: 'installation' } }] },
        { label: 'Integrations', items: [{ autogenerate: { directory: 'integrations' } }] },
        { label: 'District admin guide', items: [{ autogenerate: { directory: 'district-admin' } }] },
        { label: 'School staff guide', items: [{ autogenerate: { directory: 'staff' } }] },
        { label: 'Teacher guide', items: [{ autogenerate: { directory: 'teachers' } }] },
        { label: 'Parent and guardian guide', items: [{ autogenerate: { directory: 'guardians' } }] },
        { label: 'Cloud customers', items: [{ autogenerate: { directory: 'cloud' } }] },
        { label: 'Reference', collapsed: true, items: [{ autogenerate: { directory: 'reference' } }] },
      ],
    }),
  ],
  vite: { plugins: [tailwindcss()] },
});
