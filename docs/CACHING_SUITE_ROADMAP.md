# Pressable Cache Management — Full Caching Suite Roadmap

## Vision

Evolve this plugin from a cache controls panel into a **Pressable Caching Suite** that can:

1. Detect cacheability bottlenecks automatically.
2. Explain *why* pages miss cache in plain language.
3. Recommend and apply safe fixes (with guardrails).
4. Validate performance impact over time.

---

## Product Pillars

## 1) Cacheability Advisor (Guidance Engine)

Build an advisor that scans representative pages and generates actionable recommendations.

### Core capabilities

- **Page sampling**
  - Homepage, top landing pages, key post types, search, cart/checkout/account paths.
- **Header analysis**
  - Capture `Cache-Control`, `Set-Cookie`, `Vary`, CDN headers, and Batcache signal headers.
- **Decision explanation**
  - Show a “cacheability verdict” with root causes:
    - “Response sets cookies for anonymous users.”
    - “Vary header includes volatile values.”
    - “URL includes tracking query params not normalized by redirects.”
- **Fix guidance**
  - Prioritized recommendations with effort and risk labels.

### UX ideas

- “Cacheability Score” (0–100) by template type.
- Severity badges: Critical, Warning, Opportunity.
- “Why this matters” + “How to fix” text for each finding.

---

## 2) Cache-Busting Attribute Detector

Create a scanner that identifies common cache killers in themes/plugins and runtime responses.

### Runtime checks

- Anonymous responses setting session/user cookies.
- Highly dynamic query strings (`utm_*`, `fbclid`, `gclid`, `nocache`, random params).
- Unbounded `Vary` values (e.g., user-agent or cookie-based variations on public pages).
- Frequent unplanned purges and broad “flush all” events.

### Optional code-aware checks

- Detect direct `nocache_headers()`, `DONOTCACHEPAGE`, and custom no-cache hooks.
- Detect `session_start()` usage in front-end requests.
- Flag plugin/theme code that injects per-request nonce fragments into otherwise static pages.

### Output

- “Top cache-busting sources” leaderboard (plugin/theme/function/request pattern).
- Time-series trends to show whether cacheability is improving.

---

## 3) Object Cache + Memcached Intelligence

Add a diagnostics tab that reads object-cache details and translates low-level stats into guidance.

### Data to collect

- Backend status: Memcached reachable, extension loaded, persistent connection in use.
- Hit/miss ratio (overall and by time window).
- Evictions, memory usage, slab pressure, item age distribution.
- Key group hot spots (if available from drop-in/plugin telemetry).

### Recommendations

- If hit ratio is low: point to likely causes (key churn, over-fragmented cache keys, TTL strategy).
- If evictions are high: recommend memory sizing and key expiration review.
- If large low-value groups dominate: suggest group-level TTL or selective bypass.

---

## 4) PHP OPcache Awareness

Report OPcache status and translate it into practical guidance.

### Checks

- Is OPcache enabled?
- Memory consumption (`opcache.memory_consumption`, used/free/wasted).
- Interned strings usage.
- Revalidation settings (`validate_timestamps`, `revalidate_freq`).
- File cache status and restart counters.

### Guidance examples

- “OPcache is enabled but near memory saturation; consider increasing memory to reduce recompilation.”
- “Frequent restarts suggest pressure or deployment behavior causing churn.”

> Important: avoid exposing sensitive file paths or internals to non-admin roles.

---

## 5) custom-redirects.php Assistant

Help users create and maintain cache-friendly redirects safely.

### Wizard workflow

1. **Discover candidates**
   - High-traffic URLs with query params.
   - Duplicate URL variants (`/page` vs `/page/`, uppercase/lowercase, tracking params).
2. **Generate rules**
   - Canonicalization redirects and query-param normalization patterns.
3. **Dry-run simulation**
   - Show before/after URL mapping and loop detection.
4. **Export options**
   - Copy-to-clipboard snippet.
   - Download generated `custom-redirects.php` file.
5. **Validation**
   - Test rules against a URL set and display expected status/location.

### Safety guardrails

- Validate syntax before export.
- Prevent redirect loops.
- Require confirmation for wildcard rules.

---

## 6) Smart Purge Strategy

Reduce over-purging and preserve hit ratio.

### Enhancements

- Purge scope suggestions: single URL, related archive, or full flush.
- “Purge impact estimator” showing likely cache churn impact.
- Cooldown/debounce logic for repeated edits/imports.
- Scheduled deferred purges for bulk updates.

---

## 7) Observability & Reporting

Give teams clear, shareable evidence of caching health.

### Dashboard widgets

- Cacheability score trend.
- Batcache hit ratio trend.
- Object cache hit/miss and evictions.
- Top cache-busting issues (current vs previous period).

### Export/reporting

- Weekly email digest to admins.
- JSON/CSV export for support/performance teams.
- Optional WP-CLI command set for automated audits.

---

## 8) Guided Remediation Playbooks

Each finding links to a contextual playbook with:

- Problem summary.
- Affected URLs.
- Recommended fixes by role (site owner, developer, host support).
- Verification steps (headers to re-check after changes).

---

## 9) Permissions, Safety, and Privacy

- Restrict diagnostics and system details to high-capability users.
- Avoid storing full cookies/PII in logs.
- Store only aggregated telemetry by default.
- Add explicit opt-in for advanced scans.

---


## Implementation Specs (2026)

Detailed implementation-ready specs for each pillar live in `docs/2026-dev/`.

- `docs/2026-dev/01-cacheability-advisor.md`
- `docs/2026-dev/02-cache-busting-detector.md`
- `docs/2026-dev/03-object-cache-memcached-intelligence.md`
- `docs/2026-dev/04-php-opcache-awareness.md`
- `docs/2026-dev/05-custom-redirects-assistant.md`
- `docs/2026-dev/06-smart-purge-strategy.md`
- `docs/2026-dev/07-observability-reporting.md`
- `docs/2026-dev/08-guided-remediation-playbooks.md`
- `docs/2026-dev/09-permissions-safety-privacy.md`

---

## Suggested Delivery Phases

## Phase 1 (High impact, low risk)

- Cacheability Advisor (runtime-only checks).
- Cache-busting detector for headers/query strings/cookies.
- custom-redirects.php rule generator + dry-run + export.

## Phase 2 (Deep diagnostics)

- Memcached intelligence panel.
- OPcache status panel.
- Smart purge recommendations.

## Phase 3 (Scale + automation)

- Trend reporting, weekly digest, exports.
- Playbooks and CLI automation.
- Optional code-aware static checks.

---

## Potential “Suite” Modules (Future)

- **Cache Rules Registry**: centralize exclusions, bypass rules, and rationale.
- **WooCommerce Mode**: commerce-specific dynamic path handling.
- **Staging-to-Production Diff**: compare cache-related settings before release.
- **A/B Verification**: compare cache headers before/after a rule change.
- **Support Snapshot Bundle**: one-click diagnostic package for Pressable support.

---

## Success Metrics

Track measurable outcomes:

- Increase in cache hit ratio.
- Reduction in full-cache flush frequency.
- Reduction in anonymous responses with `Set-Cookie`.
- Improved Time to First Byte (TTFB) on cacheable paths.
- Fewer support tickets tied to cache unpredictability.
