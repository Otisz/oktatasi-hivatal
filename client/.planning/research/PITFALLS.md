# Pitfalls Research

**Domain:** Vue 3 SPA consuming cross-origin REST API
**Researched:** 2026-02-28
**Confidence:** HIGH

## Critical Pitfalls

### Pitfall 1: Vite Proxy Only Works in Development — Production Has No Proxy

**What goes wrong:**
The `server.proxy` configuration in `vite.config.ts` silently disappears after `npm run build`. During development, API calls to `/api/...` are transparently proxied to `http://localhost:8000`. After building, the app ships as static files with no proxy layer — those same `/api/...` requests 404 in production because nothing forwards them.

**Why it happens:**
Vite's dev server provides the proxy, not the built output. Developers test with the proxy working and assume it persists into production. The documentation states this is explicitly dev-only behaviour.

**How to avoid:**
Use `VITE_API_BASE_URL` as the authoritative API base URL in all fetch calls, not path-only strings like `/api/v1/applicants`. Configure `.env.development` to point at the local Laravel server directly (cross-origin, relying on CORS headers the server must provide), or configure the proxy and ensure the server's CORS headers are production-ready from day one. In this project, the Laravel server handles CORS via its own middleware — the proxy is optional comfort, not a production requirement.

**Warning signs:**
- API calls succeed in `npm run dev` but fail immediately after `npm run build` + `npm run preview`.
- Network tab shows requests going to the frontend origin instead of the API server.

**Phase to address:**
Project scaffolding phase (Phase 1). Establish `.env.development` and `.env.production` with correct `VITE_API_BASE_URL` values before writing a single fetch call. All subsequent phases inherit the correct URL strategy.

---

### Pitfall 2: Fetch API Does Not Throw on 4xx/5xx — 422 Responses Silently Succeed

**What goes wrong:**
The Fetch API only rejects its Promise on network failure (DNS, connection refused, timeout). A 422 Unprocessable Entity response resolves successfully — `response.ok` is `false` but no exception is thrown. Developers coming from Axios expect the `catch` block to handle HTTP errors; with Fetch it does not.

For this project this is the primary business-logic pitfall: the score endpoint returns 422 with `{ "error": "..." }` for ineligible applicants. If the fetch wrapper does not check `response.ok`, the component receives a resolved response, tries to destructure `.data.osszpontszam`, gets `undefined`, and renders nothing — no error state, no Hungarian message.

**Why it happens:**
Axios throws on non-2xx; Fetch does not. Developers carry Axios mental models into Fetch code.

**How to avoid:**
After every `await fetch(...)` call, explicitly check `response.ok`. If `false`, read the response body to extract the error, then throw a typed error or return a discriminated union. A thin composable (`useApi`) that wraps this logic once is better than repeating the guard in every component.

```typescript
const response = await fetch(url);
if (!response.ok) {
  const body = await response.json();
  throw new ApiError(response.status, body);
}
return response.json();
```

**Warning signs:**
- Score view shows blank content with no error message for a 422 response.
- `response.status` is 422 but no `catch` block runs.
- `console.log(data)` shows `{ error: "..." }` instead of `{ data: { osszpontszam: ... } }` yet no error state is set.

**Phase to address:**
API integration phase. The fetch composable must enforce the `response.ok` guard before any component uses it. Include a test with a mocked 422 response to confirm the error path fires.

---

### Pitfall 3: TypeScript Trusting `response.json()` — Runtime Shape Mismatch Is Silent

**What goes wrong:**
`response.json()` returns `Promise<any>`. TypeScript happily accepts a type assertion like `const data = await response.json() as ApplicantListResponse`. If the API returns a different shape than expected — wrong field name, missing field, or `null` — TypeScript does not catch it. The component silently receives `undefined` for critical fields and renders nothing or crashes at runtime.

**Why it happens:**
`as SomeType` is a compile-time assertion, not a runtime validation. Developers treat it as a guarantee when it is only a promise.

**How to avoid:**
For this project's small, stable API, a pragmatic minimum is to define precise interfaces for every response shape, use narrow `as` assertions only at the API boundary, and defensively access nested fields. If the API shape is uncertain, add `zod` parsing at the boundary. Do not use `as unknown as T` double assertions — this is a code smell indicating a design problem.

```typescript
interface ApplicantListResponse {
  data: Array<{
    id: string;
    program: {
      university: string;
      faculty: string;
      name: string;
    };
  }>;
}
```

**Warning signs:**
- `data.data` is `undefined` even though the network tab shows a valid response.
- TypeScript passes but runtime throws `Cannot read property 'osszpontszam' of undefined`.
- A field name in the type definition does not match the actual JSON key (e.g., `totalPoints` vs. `osszpontszam`).

**Phase to address:**
Project scaffolding phase. Define all API types in a `types/api.ts` file before implementing any component that uses them. Cross-check field names against the API contracts in `PROJECT.md`.

---

### Pitfall 4: CORS Preflight Blocked Because `changeOrigin` Is Misconfigured or Missing

**What goes wrong:**
If using the Vite proxy, omitting `changeOrigin: true` causes the proxy to forward requests with `Host: localhost:5173`. Some servers reject this. If not using the proxy (direct cross-origin calls in development), the browser sends a `GET` preflight or the server must return correct `Access-Control-Allow-Origin` headers. A misconfigured `changeOrigin` results in CORS errors that look like network failures.

**Why it happens:**
`changeOrigin` is not the default and its purpose (rewriting the `Host` header) is easy to overlook. Developers copy proxy config snippets without understanding why this option is needed.

**How to avoid:**
In `vite.config.ts`, always include `changeOrigin: true` when the proxy target is on a different origin. For this project, if direct cross-origin calls are used instead of the proxy, rely on Laravel's CORS middleware (which is already configured for the API) and verify headers in the browser network tab before building further. Do not rely on the proxy to solve a CORS problem the production server must solve anyway.

**Warning signs:**
- Browser console: `Access to fetch at '...' from origin 'http://localhost:5173' has been blocked by CORS policy`.
- Network tab shows the preflight OPTIONS request returning 403 or no CORS headers.
- Requests work in Insomnia/curl but fail in the browser.

**Phase to address:**
Project scaffolding phase. Verify CORS works end-to-end (browser tab, not just curl) before building any feature that depends on API data.

---

### Pitfall 5: Vue Router History Mode — Direct URL Access Returns 404

**What goes wrong:**
`createWebHistory()` gives clean URLs like `/applicants/uuid/score`. When a user visits that URL directly or refreshes the page, the static file server looks for a file at that path, finds nothing, and returns 404. The app never boots.

**Why it happens:**
History mode relies on the server serving `index.html` for all paths that do not match a static asset. A vanilla static server (e.g., `vite preview`, Nginx without configuration, plain `serve`) does not do this by default.

**How to avoid:**
For local preview: use `vite preview` which already handles this correctly by default (as of Vite 3+). For any real deployment: configure the server with a catch-all fallback to `index.html`. For this project, the app is a local development tool, so `vite preview` is sufficient.

Alternatively, `createWebHashHistory()` avoids this entirely (URLs become `/#/applicants/...`) but produces ugly URLs. History mode is the correct choice — just be aware of the server requirement.

**Warning signs:**
- Navigating within the app works but pressing F5 on `/applicants/uuid/score` returns a 404.
- Works on `localhost:5173` (Vite dev server) but breaks on `localhost:4173` (preview) without proper config.

**Phase to address:**
Routing phase. Configure `createWebHistory()` once with a fallback route (`/:pathMatch(.*)*`) to show a graceful 404 view within the app.

---

### Pitfall 6: Environment Variable Not Prefixed With `VITE_` — Invisible to Browser Code

**What goes wrong:**
Vite only exposes environment variables prefixed with `VITE_` to client-side code. A variable named `API_BASE_URL` in `.env` is accessible in `vite.config.ts` (Node context) but resolves to `undefined` in component code via `import.meta.env.API_BASE_URL`. The API base URL becomes `undefined`, all fetch URLs become `undefinedapi/v1/applicants`, and every request fails.

**Why it happens:**
The `VITE_` prefix requirement is a security feature, not a convention. Developers familiar with Create React App (`REACT_APP_`) or other tools may not know this rule.

**How to avoid:**
Name the variable `VITE_API_BASE_URL` consistently. Add a TypeScript declaration in `env.d.ts` to get autocomplete and catch typos:

```typescript
interface ImportMetaEnv {
  readonly VITE_API_BASE_URL: string;
}
```

**Warning signs:**
- `import.meta.env.VITE_API_BASE_URL` is `undefined` in browser devtools.
- All API requests go to `undefinedapi/v1/applicants` (literally).
- Works after `npm run dev` restart because the variable was added but the server was not restarted.

**Phase to address:**
Project scaffolding phase. Create `.env.development`, `.env.production`, and `env.d.ts` as part of initial setup.

---

## Technical Debt Patterns

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Inline fetch calls in every component | No composable setup time | Duplicated error handling, inconsistent loading states | Never — extract to composable from the start |
| `as any` on API responses | Avoids typing effort | Silent runtime crashes when API shape differs | Never — type all response shapes |
| Skip `response.ok` check and rely only on `.catch()` | Less code per call | 422 responses treated as success, error state never shown | Never |
| Skip AbortController | Simpler composable | Memory leaks and state updates on unmounted components | Acceptable for MVP where navigation is simple and fast |
| Use `createWebHashHistory()` to avoid server config | No server setup needed | Ugly URLs, incompatible with some OAuth redirect flows | Acceptable for local-only tools |
| Hardcode API URL in source | No env var setup | Cannot deploy to different environments without code change | Never |

## Integration Gotchas

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| Vite proxy | Using path-only URLs in fetch calls, assuming proxy persists to production | Use `VITE_API_BASE_URL` in all calls; proxy is a dev convenience only |
| Laravel CORS | Assuming CORS is handled and not verifying from a browser | Open the browser network tab and confirm `Access-Control-Allow-Origin` header is present on the actual response |
| Laravel 422 | Treating 422 as an error-by-default | Check `response.ok` explicitly; 422 is a valid business response with a JSON body |
| Laravel API resource wrapper | Expecting flat JSON, getting `{ "data": { ... } }` | Type all responses with the `data` wrapper from day one per PROJECT.md contracts |
| Vite env vars | Changing `.env` without restarting dev server | Always restart `npm run dev` after `.env` changes |

## Performance Traps

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| No request cancellation when navigating away | Memory leak warning in console; stale data populates new view | Use `AbortController` in composables, abort in `onUnmounted` | Any time a user navigates before a slow response arrives |
| Fetching applicant list on every score view mount | Redundant network calls; flickering list | Hoist list fetch to a parent or use a simple module-level cache | Noticeable at any response latency above 200ms |
| Tailwind full stylesheet in development | Large CSS in dev is fine; but forgetting to verify production build strips unused classes | Build once and verify styles match development | Any production build where dynamic classes are used |

## Security Mistakes

| Mistake | Risk | Prevention |
|---------|------|------------|
| Storing secrets in `VITE_` env vars | Secrets are baked into the JS bundle and visible to all users | This API has no secrets — `VITE_API_BASE_URL` is a URL, not a credential. Never put API keys in `VITE_` vars |
| Rendering API error strings as raw HTML with `v-html` | XSS if the API ever returns user-controlled content | The Hungarian error messages are server-controlled, not user-controlled, so `{{ error }}` text interpolation is safe and correct |
| Accepting any CORS origin on the server for convenience | Any origin can call the API | Laravel's CORS config should restrict to the frontend origin in production |

## UX Pitfalls

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| No loading state between route navigation | Blank page appears while fetch is in-flight | Show a loading indicator immediately on navigation, before data arrives |
| Showing a generic "Error" message instead of the Hungarian API error | Users do not understand why score calculation failed | Display `error.message` from the 422 response body directly — it is the authoritative human-readable explanation |
| No error boundary for unexpected 500 errors | White screen with no explanation | Catch non-422 errors separately and show a distinct "unexpected error" message |
| Applicant list with no empty state | If list is empty, nothing renders — looks broken | Add an explicit empty state component |

## "Looks Done But Isn't" Checklist

- [ ] **CORS:** Verify from an actual browser tab (not curl) that API responses include `Access-Control-Allow-Origin` for the frontend origin.
- [ ] **422 handling:** Confirm the score view renders the Hungarian error message when `response.status === 422`, not blank content.
- [ ] **Production env:** Confirm `VITE_API_BASE_URL` in `.env.production` points at the production server, not `localhost`.
- [ ] **History mode fallback:** Confirm pressing F5 on `/applicants/uuid/score` does not return a 404.
- [ ] **TypeScript types:** Confirm all API response fields match `PROJECT.md` contracts — check field names character by character (Hungarian names like `osszpontszam` are easy to mistype).
- [ ] **Dynamic Tailwind classes:** Confirm no conditional class is built by string concatenation — check the production build looks identical to dev.
- [ ] **Loading state:** Confirm a loading indicator appears during the network request, not just after data arrives.

## Recovery Strategies

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| Proxy-only API URLs in production | MEDIUM | Add `VITE_API_BASE_URL` env var, update all fetch calls to use it, redeploy |
| Missing `response.ok` check — 422 never shown | LOW | Add guard to the fetch composable in one place; all callers benefit immediately |
| Wrong TypeScript field names on API types | LOW | Fix type definition; TypeScript will surface all usages that need updating |
| Dynamic Tailwind classes purged in prod | LOW | Replace string concatenation with a lookup object mapping values to full class strings |
| History mode 404 on refresh | LOW | Configure server fallback or switch to hash history |
| `VITE_` prefix missing from env var | LOW | Rename variable and update all references; restart dev server |

## Pitfall-to-Phase Mapping

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Vite proxy disappears in production | Phase 1: Scaffolding | `npm run build && npm run preview` — all API calls use the configured base URL |
| Fetch does not throw on 422 | Phase 2: API integration | Manually trigger a 422 (pick an ineligible applicant) — confirm error state renders |
| TypeScript type mismatch on API responses | Phase 1: Scaffolding | `tsc --noEmit` passes with zero errors after defining types |
| CORS preflight blocked | Phase 1: Scaffolding | Browser network tab shows successful response with CORS headers |
| Vue Router history mode 404 | Phase 2: Routing | Refresh the browser on a deep route — no 404 |
| Missing `VITE_` prefix | Phase 1: Scaffolding | `import.meta.env.VITE_API_BASE_URL` is defined at runtime |
| Dynamic Tailwind classes purged | Any phase using conditional classes | Production build visually matches development build |

## Sources

- Vite Server Options (proxy configuration): https://vite.dev/config/server-options
- Vite proxy only works in dev: https://www.thatsoftwaredude.com/content/14128/working-with-vite-proxy
- Fetch does not throw on 4xx/5xx: https://dev.to/kresohr/the-fetch-api-trap-when-http-errors-dont-land-in-catch-40l6
- Fetch error handling patterns: https://jasonwatmore.com/post/2021/10/09/fetch-error-handling-for-failed-http-responses-and-network-errors
- Vue Router history mode 404: https://router.vuejs.org/guide/essentials/history-mode.html
- Vue Router history mode fix guide: https://markaicode.com/vue-router-history-mode-404-fix-complete-guide/
- Vite env variables: https://vite.dev/guide/env-and-mode
- Vite env variables not loading checklist: https://t-salad.com/en/vue3-vite-env-not-working-en/
- TypeScript type assertions anti-pattern: https://betterstack.com/community/guides/scaling-nodejs/type-assertions/
- Vue 3 TypeScript best practices: https://vuejs.org/guide/typescript/overview.html
- Tailwind dynamic class purging: https://vue-land.github.io/faq/missing-tailwind-classes
- Tailwind safelist: https://blogs.perficient.com/2025/08/19/understanding-tailwind-css-safelist-keep-your-dynamic-classes-safe/
- AbortController in Vue 3: https://coreui.io/answers/how-to-cancel-requests-in-vue/
- CORS with Vite: https://rubenr.dev/cors-vite-vue/
- Axios 422 interceptor discussion: https://laracasts.com/discuss/channels/vue/how-to-tell-axios-interceptors-error-handler-to-ignore-422

---
*Pitfalls research for: Vue 3 + Vite + TypeScript SPA consuming cross-origin Laravel REST API*
*Researched: 2026-02-28*
