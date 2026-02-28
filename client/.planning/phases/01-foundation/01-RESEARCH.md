# Phase 1: Foundation - Research

**Researched:** 2026-02-28
**Domain:** Vue 3 + Vite + TypeScript + Tailwind CSS v4 + Biome project scaffolding with typed API contracts, TanStack Query, and Axios
**Confidence:** HIGH

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions

#### Package manager
- npm (ships with Node, no extra install)
- Semver ranges (default ^ prefixes) for dependency versions
- `.nvmrc` file pinning Node 22 LTS

#### Project structure
- Standard Vue convention: `src/components/`, `src/views/`, `src/composables/`, `src/types/`, `src/lib/`
- API layer (Axios instance, TanStack Query client) lives in `src/lib/`
- Single-file components with `<script setup lang="ts">`
- Path alias: `@` maps to `src/` (configured in both tsconfig and Vite)

### Claude's Discretion
- Biome configuration and rule strictness
- Initial app shell content (what App.vue renders)
- Tailwind CSS v4 configuration approach
- Exact tsconfig compiler options
- .env file structure beyond VITE_API_BASE_URL

### Deferred Ideas (OUT OF SCOPE)

None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|-----------------|
| INFRA-01 | Project scaffolded with Vue 3 + Vite + TypeScript + Tailwind CSS v4 | `npm create vue@latest` scaffolds Vue 3 + Vite + TS; Tailwind v4 added via `@tailwindcss/vite` plugin |
| INFRA-02 | Biome configured for linting and formatting (replaces ESLint/Prettier) | Biome 2.4.4 supports TypeScript fully and Vue experimentally via `html.experimentalFullSupportEnabled`; install with `npm install -D @biomejs/biome` then `npx biome init` |
| INFRA-03 | API base URL configurable via `VITE_API_BASE_URL` environment variable | Vite exposes `VITE_`-prefixed vars to client code via `import.meta.env`; typed in `src/vite-env.d.ts` by augmenting `ImportMetaEnv` |
| INFRA-04 | TypeScript interfaces defined for all API response shapes (`Applicant`, `Program`, `ScoreResult`, `ApiError`) | Interfaces go in `src/types/api.ts`; field names (`osszpontszam`, `alappont`, `tobbletpont`) must be verified character-by-character against actual API output |
| INFRA-05 | Axios HTTP client instance configured with base URL from environment | Axios 1.13.6 configured with `baseURL: import.meta.env.VITE_API_BASE_URL`; instance in `src/lib/http.ts` |
| DATA-01 | TanStack Query (Vue) configured as the data fetching/caching layer | `@tanstack/vue-query` 5.92.9; `VueQueryPlugin` registered in `main.ts` via `app.use(VueQueryPlugin)`; QueryClient optionally created separately for custom defaults |
</phase_requirements>

---

## Summary

Phase 1 scaffolds the complete project skeleton that all later phases build on. The work is entirely infrastructure — no views, no routing, no real data fetching. The goal is a project that boots, compiles with zero TypeScript errors, lints clean under Biome, resolves `VITE_API_BASE_URL` in the browser, has the four required TypeScript interfaces in `src/types/api.ts`, and has TanStack Query registered in `main.ts` with an Axios instance reading the env var as its base URL.

All technologies are stable, well-documented, and have been verified against npm as of 2026-02-28: Vue 3.5.29, Vite 7.3.1, TypeScript 5.x (via `@vue/tsconfig`), Tailwind CSS 4.2.1 with `@tailwindcss/vite` 4.2.1, Biome 2.4.4, `@tanstack/vue-query` 5.92.9, Axios 1.13.6. The one non-trivial decision is Biome's Vue support: as of v2.3.0 it is experimental, but v2.4 significantly improved it and it is the appropriate choice given the locked decision to use Biome over ESLint/Prettier.

The key risk for this phase is infrastructure setup correctness: `VITE_API_BASE_URL` missing the prefix, Biome not configured to handle `.vue` files, TypeScript Hungarian field names mistyped in interfaces. All are easy to prevent with the right setup order.

**Primary recommendation:** Scaffold with `npm create vue@latest` (selecting TypeScript, declining ESLint/Prettier), then layer in Biome, Tailwind v4, TanStack Query, and Axios as separate install steps. Establish `src/lib/` as the API layer directory per the locked decision (note: `create-vue` uses `src/api/` by convention; rename to `src/lib/` after scaffolding).

---

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Vue | 3.5.29 | UI framework | Composition API + `<script setup>` is the current standard for Vue 3 |
| Vite | 7.3.1 | Build tool / dev server | Official Vue build tool; requires Node 20.19+ or 22.12+; development machine has Node 22.18.0 ✓ |
| TypeScript | 5.x (via `@vue/tsconfig`) | Type safety | Required for API interfaces; `vue-tsc` compiles SFCs |
| Tailwind CSS | 4.2.1 | Utility-first styling | v4 uses `@tailwindcss/vite` plugin; zero PostCSS config; single `@import "tailwindcss"` |
| `@tailwindcss/vite` | 4.2.1 | Tailwind Vite plugin | First-party; replaces entire PostCSS pipeline; must match Tailwind major version |
| `@vitejs/plugin-vue` | 6.0.4 | Vue SFC compilation | Required Vite plugin; installed by `create-vue` automatically |
| Biome | 2.4.4 | Lint + format (replaces ESLint/Prettier) | Single tool, Rust-based, ~15x faster than ESLint; locked decision |
| `@tanstack/vue-query` | 5.92.9 | Server state / data fetching layer | Locked decision; wraps Axios; handles loading/error/caching |
| Axios | 1.13.6 | HTTP client | Throws on non-2xx (critical for 422 flow); typed generics; base URL wrapper |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| `vue-tsc` | latest (bundled with `@vue/language-tools`) | Type-check `.vue` files | Run `vue-tsc --noEmit` for type-check; CI gate |
| `@vue/tsconfig` | latest | Base `tsconfig.json` for Vue 3 projects | Extend with `"extends": "@vue/tsconfig/tsconfig.dom.json"` |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Biome | ESLint + Prettier | ESLint has mature Vue plugin (`eslint-plugin-vue`); Prettier has full Vue template support. Biome's Vue support is experimental. Biome is ~15x faster and the locked decision. |
| `@tailwindcss/vite` | PostCSS pipeline | PostCSS is how Tailwind v3 worked; v4 ships the Vite plugin as the preferred path |
| `@tanstack/vue-query` | Hand-rolled composables | Custom composables duplicate loading/error/stale management; locked decision |
| Axios | Native `fetch` | `fetch` does not throw on 4xx; requires manual `response.ok` check everywhere; Axios throws automatically |

**Installation:**

```bash
# Step 1: Scaffold
npm create vue@latest .
# Prompts: TypeScript → Yes, JSX → No, Vue Router → No, Pinia → No,
#          Vitest → No, E2E → No, ESLint → No, Prettier → No, DevTools → No

# Step 2: Tailwind CSS v4
npm install tailwindcss @tailwindcss/vite

# Step 3: Biome
npm install -D @biomejs/biome
npx biome init

# Step 4: TanStack Query + Axios
npm install @tanstack/vue-query axios
```

---

## Architecture Patterns

### Recommended Project Structure

Per the locked decision, the API layer lives in `src/lib/` (not `src/api/` which `create-vue` default would scaffold). After `npm create vue@latest`, delete the generated `src/api/` (if any) and create `src/lib/` instead.

```
src/
├── lib/                 # API layer: Axios instance, QueryClient (locked decision)
│   ├── http.ts          # Axios instance reading VITE_API_BASE_URL
│   └── query.ts         # QueryClient instance (optional, can inline in main.ts)
├── types/               # TypeScript interfaces — API contracts
│   └── api.ts           # Applicant, Program, ScoreResult, ApiError
├── composables/         # Vue reactivity wrappers (future phases)
├── components/          # Vue SFCs (future phases)
├── views/               # Page-level components (future phases)
├── App.vue              # Root shell — minimal, router-view placeholder or "Hello"
├── main.ts              # Bootstrap: createApp, VueQueryPlugin, mount
└── vite-env.d.ts        # ImportMetaEnv type augmentation for VITE_ vars
```

### Pattern 1: Vite Environment Variable Declaration

**What:** Augment `ImportMetaEnv` in `src/vite-env.d.ts` to get TypeScript autocomplete and compile-time safety for `VITE_API_BASE_URL`.

**When to use:** Every `VITE_`-prefixed env var used in client code must be declared here.

**Example:**
```typescript
// src/vite-env.d.ts
// Source: https://vite.dev/guide/env-and-mode

/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
```

**Critical rule:** Do NOT add any `import` statements to this file — it breaks the augmentation.

### Pattern 2: Axios Instance in `src/lib/http.ts`

**What:** A single Axios instance that reads `VITE_API_BASE_URL` exactly once. All API calls use this instance, not `axios` directly.

**When to use:** Phase 1 establishes this instance. Phase 2 composables import it.

**Example:**
```typescript
// src/lib/http.ts
import axios from 'axios'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})
```

### Pattern 3: TanStack Query Registration in `main.ts`

**What:** Register `VueQueryPlugin` as a Vue app plugin. The `QueryClient` can be configured here or in a separate `src/lib/query.ts` — per the locked decision the API layer lives in `src/lib/`, so a separate `query.ts` is appropriate.

**When to use:** `main.ts` only wires the plugin; `QueryClient` config lives in `src/lib/`.

**Example:**
```typescript
// src/main.ts
// Source: https://tanstack.com/query/latest/docs/framework/vue/installation

import { createApp } from 'vue'
import { VueQueryPlugin } from '@tanstack/vue-query'
import App from './App.vue'

createApp(App)
  .use(VueQueryPlugin)
  .mount('#app')
```

Optional: with explicit QueryClient for custom stale time:
```typescript
// src/lib/query.ts
import { QueryClient } from '@tanstack/vue-query'

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
    },
  },
})
```

```typescript
// src/main.ts (with explicit client)
import { createApp } from 'vue'
import { VueQueryPlugin } from '@tanstack/vue-query'
import { queryClient } from '@/lib/query'
import App from './App.vue'

createApp(App)
  .use(VueQueryPlugin, { queryClient })
  .mount('#app')
```

### Pattern 4: TypeScript Interfaces in `src/types/api.ts`

**What:** All API response shapes defined in one file. All other files import from here.

**When to use:** Define before writing any composable or component. Every API call typed against these interfaces.

**Example:**
```typescript
// src/types/api.ts
// Based on API contracts from CONTEXT.md code_context

export interface Program {
  university: string
  faculty: string
  name: string
}

export interface Applicant {
  id: string
  program: Program
}

export interface ScoreResult {
  osszpontszam: number
  alappont: number
  tobbletpont: number
}

export interface ApiError {
  error: string
}

// JSON:API-style envelope wrapper used by Laravel API Resources
export interface ApiResponse<T> {
  data: T
}
```

**Critical:** Field names `osszpontszam`, `alappont`, `tobbletpont` must be verified character-by-character against actual API output before finalising. These are Hungarian; typos are silent at the TypeScript level until runtime.

### Pattern 5: Biome Configuration for Vue + TypeScript

**What:** `biome.json` at project root replacing ESLint and Prettier. As of Biome 2.4.4, Vue support is experimental but significantly improved — full support enabled via `html.experimentalFullSupportEnabled: true`.

**When to use:** Created during Phase 1. Run `npx biome check .` to verify zero violations on the initial scaffold.

**Example:**
```json
{
  "$schema": "https://biomejs.dev/schemas/2.4.4/schema.json",
  "vcs": {
    "enabled": true,
    "clientKind": "git",
    "useIgnoreFile": true
  },
  "files": {
    "ignore": ["node_modules", "dist", ".nuxt"]
  },
  "formatter": {
    "enabled": true,
    "indentStyle": "space",
    "indentWidth": 2,
    "lineWidth": 100
  },
  "organizeImports": {
    "enabled": true
  },
  "linter": {
    "enabled": true,
    "rules": {
      "recommended": true
    }
  },
  "javascript": {
    "formatter": {
      "quoteStyle": "single",
      "trailingCommas": "all",
      "semicolons": "asNeeded"
    }
  },
  "html": {
    "experimentalFullSupportEnabled": true,
    "formatter": {
      "enabled": true,
      "indentWidth": 2
    }
  },
  "css": {
    "formatter": {
      "enabled": true
    }
  }
}
```

### Pattern 6: Tailwind CSS v4 Vite Plugin Setup

**What:** Tailwind v4 drops `tailwind.config.js` and the PostCSS pipeline in favour of a first-party Vite plugin. Two changes needed: `vite.config.ts` and the CSS entry file.

**Example:**
```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
```

```css
/* src/assets/main.css (or wherever your CSS entry is) */
@import "tailwindcss";
```

**Critical:** Do NOT add a `postcss.config.js` alongside `@tailwindcss/vite` — two pipelines will conflict.

### Pattern 7: `.nvmrc` and `.env` Files

```
# .nvmrc
22
```

```bash
# .env.development
VITE_API_BASE_URL=http://localhost:8000

# .env.production
VITE_API_BASE_URL=https://api.yourdomain.com
```

**Note:** `.env` files should not be committed with sensitive values. `VITE_API_BASE_URL` is a URL (not a secret) but creating `.env.example` with a placeholder is good practice.

### Anti-Patterns to Avoid

- **Selecting ESLint/Prettier during `create-vue`:** Locked decision is Biome. Select No for both. Removes the need to uninstall afterwards.
- **`tailwind.config.js` + PostCSS for v4:** Tailwind v4 with `@tailwindcss/vite` does not need PostCSS. Adding it creates a conflicting pipeline.
- **Env var without `VITE_` prefix:** `API_BASE_URL` is invisible to browser code; `import.meta.env.API_BASE_URL` returns `undefined`. All API calls fail silently with URLs like `undefinedapi/v1/applicants`.
- **`import` statements in `vite-env.d.ts`:** Breaks the `ImportMetaEnv` augmentation. Keep the file `import`-free.
- **`as any` on API response types:** Silent runtime failures when API shape drifts from interface definition.
- **Registering `VueQueryPlugin` after `mount()`:** Plugin must be registered before `.mount('#app')`.

---

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Server state caching | Custom reactive cache with Map + ref | `@tanstack/vue-query` | Background refetch, stale-while-revalidate, deduplication, devtools |
| HTTP base URL wrapper | Global variable or module-level `fetch` wrapper | Axios instance (`axios.create({ baseURL })`) | Error throwing on non-2xx, typed generics, interceptor support |
| TypeScript env var types | Manual `declare const VITE_API_BASE_URL: string` | `ImportMetaEnv` augmentation in `vite-env.d.ts` | IDE autocomplete, compile-time guard, idiomatic Vite pattern |
| Linting + formatting pipeline | `eslint.config.js` + `.prettierrc` + plugins | `biome.json` | Locked decision; single config, 15x faster, no plugin conflicts |

**Key insight:** Every "roll your own" alternative for these problems is an order of magnitude more configuration and maintenance than the dedicated tool.

---

## Common Pitfalls

### Pitfall 1: Missing `VITE_` Prefix on Environment Variable

**What goes wrong:** `API_BASE_URL` without the prefix is accessible in `vite.config.ts` (Node context) but returns `undefined` in browser code. All Axios calls become `undefinedapi/v1/applicants`. Every request fails with a network error.

**Why it happens:** Vite's `VITE_` requirement is a security feature, not a naming convention. Developers from CRA (`REACT_APP_`) or plain Node don't expect it.

**How to avoid:** Name it `VITE_API_BASE_URL` from the first commit. Declare it in `src/vite-env.d.ts` — if TypeScript can autocomplete it, it is correctly named.

**Warning signs:** `import.meta.env.VITE_API_BASE_URL` is `undefined` in browser devtools console.

---

### Pitfall 2: Biome Not Handling `.vue` Files

**What goes wrong:** Biome's default `biome init` configuration does not enable `html.experimentalFullSupportEnabled`. Running `npx biome check .` against `.vue` files will only process `<script>` tag content and may throw false positive errors or silently skip template linting.

**Why it happens:** Vue support landed in v2.3.0 as experimental and is opt-in.

**How to avoid:** Add `"html": { "experimentalFullSupportEnabled": true }` to `biome.json`. Verify with `npx biome check src/App.vue` — should report clean (or only real violations).

**Warning signs:** `npx biome check .` reports zero violations even with an obviously broken `.vue` file template.

---

### Pitfall 3: TypeScript Hungarian Field Name Typos

**What goes wrong:** `osszpontszam`, `alappont`, `tobbletpont` in `src/types/api.ts` are silently mistyped. TypeScript compiles successfully but at runtime the score endpoint returns `undefined` for the misnamed fields. The score detail view renders blank.

**Why it happens:** Hungarian field names are opaque to non-Hungarian developers. `osszpontszam` vs `összpontszám` vs `osszpont` — the UTF-8 characters look similar in some editors.

**How to avoid:** Copy field names verbatim from the actual API response body (browser Network tab → Response). Do not transcribe from memory or PROJECT.md without verification.

**Warning signs:** `vue-tsc --noEmit` passes but score fields render as `undefined` in the browser.

---

### Pitfall 4: Tailwind v4 PostCSS Conflict

**What goes wrong:** If a `postcss.config.js` is left in the project (from `create-vue` or manual addition), it conflicts with `@tailwindcss/vite`. CSS may not be processed or classes may not be generated in the dev build.

**Why it happens:** Tailwind v3 used PostCSS; developers carry v3 muscle memory. `create-vue` does not scaffold PostCSS but community tutorials often include it.

**How to avoid:** Do not create a `postcss.config.js` when using `@tailwindcss/vite`. If one exists from a previous step, delete it.

**Warning signs:** Tailwind classes appear unstyled in the browser even after `@import "tailwindcss"` is present in the CSS entry file.

---

### Pitfall 5: `VueQueryPlugin` Registered After `mount()`

**What goes wrong:** If `app.use(VueQueryPlugin)` is called after `app.mount('#app')`, the plugin is not available during the initial render. Components using `useQuery` will error immediately.

**Why it happens:** Plugin registration order matters in Vue 3; `mount()` triggers the initial render.

**How to avoid:** Always call `app.use(...)` for all plugins before `app.mount('#app')`.

**Warning signs:** Console error "VueQuery: No QueryClient found" on first render.

---

### Pitfall 6: Dev Server Not Restarted After `.env` Change

**What goes wrong:** Vite reads `.env` files at startup. Changing `VITE_API_BASE_URL` without restarting `npm run dev` keeps the old value in the running process.

**How to avoid:** Always restart after `.env` changes. Add a note in the `.env.example` file.

**Warning signs:** `import.meta.env.VITE_API_BASE_URL` returns an old value in the browser console despite changing `.env`.

---

## Code Examples

Verified patterns from official sources:

### Axios Instance with Environment Variable

```typescript
// src/lib/http.ts
// Source: Axios docs + Vite env variable pattern

import axios from 'axios'

export const http = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})
```

### TanStack Query Registration

```typescript
// src/main.ts
// Source: https://tanstack.com/query/latest/docs/framework/vue/installation

import { createApp } from 'vue'
import { VueQueryPlugin } from '@tanstack/vue-query'
import App from './App.vue'
import '@/assets/main.css'

createApp(App)
  .use(VueQueryPlugin)
  .mount('#app')
```

### TypeScript API Interfaces

```typescript
// src/types/api.ts
// Based on API contracts from CONTEXT.md

export interface Program {
  university: string
  faculty: string
  name: string
}

export interface Applicant {
  id: string
  program: Program
}

export interface ScoreResult {
  osszpontszam: number  // VERIFY against actual API response
  alappont: number      // VERIFY against actual API response
  tobbletpont: number   // VERIFY against actual API response
}

export interface ApiError {
  error: string
}

export interface ApiResponse<T> {
  data: T
}
```

### Vite Environment Variable Type Declaration

```typescript
// src/vite-env.d.ts
// Source: https://vite.dev/guide/env-and-mode

/// <reference types="vite/client" />

interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string
}

interface ImportMeta {
  readonly env: ImportMetaEnv
}
```

### Biome Configuration

```json
// biome.json
// Source: https://biomejs.dev/guides/configure-biome/ + Biome v2.4 docs

{
  "$schema": "https://biomejs.dev/schemas/2.4.4/schema.json",
  "vcs": {
    "enabled": true,
    "clientKind": "git",
    "useIgnoreFile": true
  },
  "files": {
    "ignore": ["node_modules", "dist"]
  },
  "formatter": {
    "enabled": true,
    "indentStyle": "space",
    "indentWidth": 2,
    "lineWidth": 100
  },
  "organizeImports": {
    "enabled": true
  },
  "linter": {
    "enabled": true,
    "rules": {
      "recommended": true
    }
  },
  "javascript": {
    "formatter": {
      "quoteStyle": "single",
      "trailingCommas": "all",
      "semicolons": "asNeeded"
    }
  },
  "html": {
    "experimentalFullSupportEnabled": true,
    "formatter": {
      "enabled": true,
      "indentWidth": 2
    }
  },
  "css": {
    "formatter": {
      "enabled": true
    }
  }
}
```

### Path Alias in `vite.config.ts`

```typescript
// vite.config.ts
// Source: create-vue scaffold + @tailwindcss/vite docs

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
```

### Tailwind CSS Entry

```css
/* src/assets/main.css */
/* Source: https://tailwindcss.com/docs/installation/using-vite */
@import "tailwindcss";
```

### `.nvmrc` File

```
22
```

### Environment Files

```bash
# .env.development
VITE_API_BASE_URL=http://localhost:8000

# .env.production
VITE_API_BASE_URL=https://api.oktatasi-hivatal.example.com
```

---

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `create-vue` scaffolds ESLint + Prettier | User opts out; Biome replaces both | Biome v1.x → v2.x era (2024-2025) | Single config file, 15x faster, no plugin management |
| Tailwind v3 via PostCSS | Tailwind v4 via `@tailwindcss/vite` | Tailwind v4.0 (Jan 2025) | Zero PostCSS config; `@import "tailwindcss"` only |
| `tailwind.config.js` with `content` paths | Automatic content detection | Tailwind v4.0 | No config file needed; Vite plugin handles it |
| Vue Router v4 | Vue Router v5 | Vue Router v5.0 (2024) | Optional file-based routing added; conventional usage unchanged |
| `@tanstack/vue-query` v4 | `@tanstack/vue-query` v5 | TanStack Query v5.0 (Oct 2023) | `isLoading` → `isPending` rename; options object API |
| Biome v1 (partial Vue support) | Biome v2.3+ (experimental full Vue support) | Biome v2.3.0 (2025) | `.vue` files now processed; `html.experimentalFullSupportEnabled` opt-in |

**Deprecated/outdated:**
- `vue-tsc` standalone install: now ships as part of `@vue/language-tools`; usually a devDependency via `create-vue`
- `postcss.config.js` for Tailwind: replaced by `@tailwindcss/vite` in v4
- ESLint + `eslint-plugin-vue` + Prettier: replaced by Biome (locked decision)
- Vue CLI: officially deprecated, replaced by `npm create vue@latest` (Vite-based)
- `createWebHashHistory()`: works but produces `/#/` URLs; `createWebHistory()` is the current standard

---

## Open Questions

1. **Hungarian API field names need live verification**
   - What we know: CONTEXT.md specifies `osszpontszam`, `alappont`, `tobbletpont` as the field names from the server's API resource
   - What's unclear: Whether the exact byte-for-byte spelling matches the Laravel Eloquent Resource output — `összpontszám` (with Unicode accents) vs `osszpontszam` (ASCII) is particularly ambiguous
   - Recommendation: Before finalising `src/types/api.ts`, open the browser Network tab, call `GET /api/v1/applicants/1/score`, and copy-paste field names from the raw JSON response. Do not type from memory.

2. **CORS must be verified from a browser before Phase 2 begins**
   - What we know: The Laravel server must return `Access-Control-Allow-Origin` headers for the frontend dev origin (`http://localhost:5173`). STATE.md flags this as a Phase 1 prerequisite.
   - What's unclear: Whether the server's CORS middleware is already configured for the dev origin.
   - Recommendation: After scaffolding, open `http://localhost:5173` in a browser, open DevTools → Network, trigger one API call (can be in App.vue temporarily), confirm the response has `Access-Control-Allow-Origin: *` or the specific origin. If blocked, the server must be updated before Phase 2.

3. **Biome Vue support is experimental — rule false positives may appear**
   - What we know: Biome 2.4.4 significantly improved Vue support with fewer false positives in `noUnusedVariables`, `useConst`, `useImportType`, `noUnusedImports` inside `.vue` files
   - What's unclear: Whether the recommended ruleset produces any false positives on the initial `create-vue` scaffold (which includes example SFCs)
   - Recommendation: After `npx biome init` and adding `experimentalFullSupportEnabled`, run `npx biome check .` on the scaffold. If false positives appear in `.vue` files, add specific rule overrides in `biome.json` rather than disabling Vue file processing entirely.

4. **`create-vue` scaffold structure vs locked `src/lib/` decision**
   - What we know: `create-vue` scaffolds with `src/components/`, `src/views/`, `src/router/`, `src/assets/` but may not create `src/lib/` or `src/types/` automatically
   - What's unclear: Exact scaffold output without running it (depends on selected options)
   - Recommendation: After scaffolding, manually create `src/lib/`, `src/types/`, and `src/composables/` as empty directories with `.gitkeep` files. Delete any scaffold-generated `src/api/` if present.

---

## Sources

### Primary (HIGH confidence)

- Vue.js quick start — https://vuejs.org/guide/quick-start — `npm create vue@latest` prompts and TypeScript guidance
- Vite env and mode — https://vite.dev/guide/env-and-mode — `VITE_` prefix requirement, `ImportMetaEnv` augmentation
- Tailwind CSS v4 Vite install — https://tailwindcss.com/docs/installation/using-vite — `@tailwindcss/vite` plugin setup
- TanStack Query Vue installation — https://tanstack.com/query/latest/docs/framework/vue/installation — `VueQueryPlugin` registration
- Biome configure guide — https://biomejs.dev/guides/configure-biome/ — `biome init`, `biome.json` structure
- Biome language support — https://biomejs.dev/internals/language-support/ — Vue experimental support status
- Biome v2.4 release — https://biomejs.dev/blog/biome-v2-4/ — improved Vue support, new Vue rules
- npm registry (live): `@tanstack/vue-query@5.92.9`, `@biomejs/biome@2.4.4`, `vue@3.5.29`, `vite@7.3.1`, `tailwindcss@4.2.1`, `axios@1.13.6`, `vue-router@5.0.3`

### Secondary (MEDIUM confidence)

- TanStack Query Vue DeepWiki — https://deepwiki.com/TanStack/query/3.2-vue-query — VueQueryPlugin setup with QueryClient options and Axios integration
- AppSignal Biome migration — https://blog.appsignal.com/2025/05/07/migrating-a-javascript-project-from-prettier-and-eslint-to-biomejs.html — `biome init` workflow and default `biome.json`
- Biome v2.3 release — https://biomejs.dev/blog/biome-v2-3/ — initial Vue support details

### Tertiary (LOW confidence)

- WebSearch: "Biome 2.4 biome.json Vue experimentalFullSupportEnabled recommended configuration" — community `biome.json` examples not from official docs; used only for configuration inspiration, verified against official docs

---

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all versions verified against npm registry on 2026-02-28
- Architecture: HIGH — derived from official `create-vue` scaffold + locked decisions from CONTEXT.md
- Biome Vue support: MEDIUM — experimental feature; improved in v2.4 but not production-stable; false positives possible
- Pitfalls: HIGH — `VITE_` prefix and env restart documented in Vite official docs; field name verification is common sense; Biome Vue config verified against Biome docs
- TanStack Query setup: HIGH — verified against TanStack docs and npm version

**Research date:** 2026-02-28
**Valid until:** 2026-03-28 (30 days — stable, well-maintained stack; Biome releases frequently but breaking changes unlikely)
