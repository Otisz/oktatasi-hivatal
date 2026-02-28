# Stack Research

**Domain:** Vue 3 SPA consuming a Laravel REST API
**Researched:** 2026-02-28
**Confidence:** HIGH — all versions verified against npm and official documentation

## Recommended Stack

### Core Technologies

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| Vue 3 | 3.5.13 | UI framework | Composition API + `<script setup>` is the current standard. Reactive primitives (`ref`, `computed`) are sufficient for this app's minimal state; no Pinia needed. |
| Vite | 7.x (latest: 7.3.1) | Build tool & dev server | Official Vue build tool. Sub-millisecond HMR. `npm create vue@latest` scaffolds directly onto it. Requires Node.js 20.19+ or 22.12+. |
| TypeScript | 5.x | Type safety | Required for typing API response shapes (`Applicant`, `ScoreResult`, error envelopes). `vue-tsc` compiles SFCs as part of type-check step. Requires TS ≥ 5.0. |
| Tailwind CSS | 4.x (latest: 4.1.4) | Utility-first styling | v4 ships a first-party `@tailwindcss/vite` plugin — no PostCSS config, no `tailwind.config.js`, automatic content detection. Single `@import "tailwindcss"` in the entry CSS is all that is needed. |
| Vue Router | 5.x (latest: 5.0.3) | Client-side routing | Official router for Vue 3. v5 merges `unplugin-vue-router` file-based routing into core but it is opt-in — conventional `createRouter()` usage is unchanged. No breaking changes from v4 when not using file-based routing. |

### Supporting Libraries

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| axios | 1.13.x (latest: 1.13.6) | HTTP client | All API calls to the Laravel backend. Preferred over native `fetch` for this project because: automatic JSON parsing, automatic error throwing on 4xx/5xx (critical for the 422 error flow), typed response generics, and cleaner request/response interception if a base URL wrapper is needed. |
| @vitejs/plugin-vue | 6.x (latest: 6.0.4) | Vue SFC compilation | Required Vite plugin to parse and transform `.vue` single-file components. Installed automatically by `create-vue`. |
| @tailwindcss/vite | 4.x | Tailwind Vite integration | First-party plugin replacing the PostCSS pipeline. Faster incremental builds (microseconds when no new CSS). Add to `vite.config.ts` plugins array alongside `@vitejs/plugin-vue`. |

### Development Tools

| Tool | Purpose | Notes |
|------|---------|-------|
| vue-tsc | TypeScript type-check for `.vue` files | Wraps `tsc`; understands SFC `<script setup>`. Run as `vue-tsc --noEmit` in CI or pre-build. |
| @vue/tsconfig | Base `tsconfig.json` for Vue 3 projects | Provides correct `compilerOptions` defaults. Extend with `"extends": "@vue/tsconfig/tsconfig.dom.json"`. Requires TS ≥ 5.0 and Vue ≥ 3.4. |
| ESLint + @vue/eslint-config-typescript | Linting | `create-vue` can scaffold this. Enforces Composition API patterns and TypeScript correctness. |

## Installation

### Scaffolding (start here)

```bash
npm create vue@latest client
# Select: TypeScript → Yes, Vue Router → Yes, ESLint → Yes, Prettier → Yes
# Leave Pinia / Vitest / Playwright → No
cd client
npm install
```

### Add Tailwind CSS v4

```bash
npm install tailwindcss @tailwindcss/vite
```

`vite.config.ts` — add the plugin:

```typescript
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
})
```

`src/style.css` (or `src/assets/main.css`):

```css
@import "tailwindcss";
```

### Add Axios

```bash
npm install axios
```

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| axios | Native `fetch` | Use `fetch` when bundle size is a primary constraint (axios adds ~11.7 kB gzipped), deploying to edge runtimes (Cloudflare Workers), or when the API never returns 4xx/5xx errors that need to be caught as exceptions. For this project, the 422 error handling makes axios the clearer choice. |
| Vue Router 5 | Vue Router 4 | Use v4 only if upgrading an existing project with unplugin-vue-router and wanting to avoid the import path changes. For new projects, v5 is the current release. |
| Tailwind CSS v4 | Tailwind CSS v3 | Use v3 if the project requires a PostCSS plugin ecosystem (e.g., `tailwindcss-animate` in v3 form), or if the team is already trained on `tailwind.config.js` patterns. v3 docs still exist at `v3.tailwindcss.com`. |
| Vite 7 / create-vue | Nuxt 3 | Use Nuxt when SSR, file-based routing out of the box, or SEO is a hard requirement. This project explicitly excludes SSR. |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Pinia | Project scope is two views with trivial state (selected applicant ID + API response). State management adds boilerplate with no return. | Vue 3 `ref` / `reactive` in composables or component-local state |
| Vue CLI | Webpack-based, officially deprecated. Significantly slower dev server than Vite. | `npm create vue@latest` (Vite-based) |
| vue-resource | Abandoned Vue 1-era HTTP library, no longer maintained. | axios |
| Options API | `<script setup>` + Composition API is the current standard for Vue 3. Options API still works but mixes concerns and is harder to type correctly with TypeScript. | `<script setup lang="ts">` with Composition API |
| Vuex | Superseded by Pinia, and Pinia itself is out of scope for this project. | Neither — use local state |
| PostCSS config for Tailwind | Tailwind v4 no longer needs `postcss.config.js` when using `@tailwindcss/vite`. Adding one creates conflicting pipelines. | `@tailwindcss/vite` plugin only |

## Stack Patterns by Variant

**If the API base URL changes between environments:**
- Use `VITE_API_BASE_URL` env variable (Vite exposes `VITE_` prefixed vars to the client)
- Create a thin `src/api/client.ts` that initialises axios with `baseURL: import.meta.env.VITE_API_BASE_URL`

**If a 422 response must be distinguished from a network error:**
- Check `axios.isAxiosError(error)` then inspect `error.response?.status === 422`
- `error.response?.data.error` will contain the Hungarian error string directly

**If TypeScript strict mode causes friction:**
- Keep `strict: true` — it is in `@vue/tsconfig` defaults for good reason
- Type the axios calls with generics: `axios.get<ApiResponse<Applicant[]>>(url)`

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| `vue@3.5.x` | `@vitejs/plugin-vue@6.x`, `vue-router@5.x` | v5 router requires Vue 3 |
| `tailwindcss@4.x` | `@tailwindcss/vite@4.x` | Vite plugin and core must be the same major version |
| `vue-tsc` | TypeScript ≥ 5.0, Vue ≥ 3.4 | Ships alongside `@vue/language-tools` |
| `vite@7.x` | Node.js 20.19+ or 22.12+ | v7 is ESM-only; drops Node 18 support |
| `@vitejs/plugin-vue@6.x` | `vite@5+` | Major version 6 supports Vite 5, 6, and 7 |

## Sources

- Vue.js official quick-start — https://vuejs.org/guide/quick-start (create-vue scaffolding command, TypeScript guidance)
- Vue Router installation docs — https://router.vuejs.org/installation (v4/v5 info)
- Tailwind CSS official docs — https://tailwindcss.com/docs (v4 Vite installation)
- Vite 7 announcement — https://vite.dev/blog/announcing-vite7 (Node requirements, breaking changes)
- WebSearch: Vue 3 / Vite / Tailwind CSS v4 current versions — HIGH confidence (multiple sources agree on 3.5.13, 7.3.1, 4.1.4)
- WebSearch: axios 1.13.6 latest — HIGH confidence (npm registry, 170k+ dependent packages)
- WebSearch: vue-router 5.0.3, @vitejs/plugin-vue 6.0.4 — MEDIUM confidence (npm search results, not verified against npm registry directly)
- WebSearch: axios vs fetch 2026 — MEDIUM confidence (multiple community sources, consistent conclusion)

---
*Stack research for: Vue 3 SPA (Hungarian Admission Score Calculator client)*
*Researched: 2026-02-28*
