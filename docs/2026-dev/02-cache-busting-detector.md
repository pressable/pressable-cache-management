# Pillar 2 — Cache-Busting Attribute Detector

## Objective

Identify runtime and optional code-level behaviors that reduce cache hit rates, and attribute them to likely sources.

## User Stories

### Story 1: Admin sees top cache-busting causes

**As an** admin  
**I want** a ranked list of cache-busting attributes  
**So that** I can quickly focus on biggest problems.

**Acceptance criteria**

- Top causes list includes frequency and affected URL count.
- Causes grouped by category (cookies, vary, query params, purge patterns).
- “Likely source” shown when attributable.

### Story 2: Developer can inspect evidence

**As a** developer  
**I want** raw evidence tied to each finding  
**So that** I can verify and fix root causes.

**Acceptance criteria**

- Evidence includes observed headers/params (PII-safe).
- Event timestamps and URL sample available.
- Exportable JSON for support debugging.

## Functional Design

## Detection Categories

1. **Cookie busters**
   - Anonymous response sets cookies that force bypass.
2. **Query busters**
   - Tracking/noise params causing fragmented cache keys.
3. **Vary busters**
   - Varying on high-cardinality headers/cookies.
4. **Purge busters**
   - Repeated global flush events.
5. **No-cache directives**
   - `no-store`, `private`, `max-age=0` on public pages.

## Attribution Strategy

- Capture active plugin/theme list and hook context where available.
- Use lightweight heuristics:
  - cookie name patterns
  - response header signatures
  - known plugin compatibility map
- Confidence score: High/Medium/Low.

## Data Model

- `wp_pcm_busters`
  - `id`, `detected_at`, `category`, `signature`, `confidence`, `count`
- `wp_pcm_buster_evidence`
  - `buster_id`, `url`, `evidence_json`, `template_type`

## Admin UX

- New tab: **Cache Busters**.
- Leaderboard + trend sparkline.
- Drill-down includes evidence and remediation links.

## Implementation Tasks + Completion Insights

1. **Detector framework**
   - Add detector interface and registry.
   - **Done when:** detector list executes in single pass over scan snapshots.

2. **Category detectors (v1)**
   - Cookie, query, vary, no-cache, purge detectors.
   - **Done when:** each detector emits normalized `BusterEvent` objects.

3. **Attribution heuristics**
   - Build signature matcher map.
   - **Done when:** events include confidence-scored likely source.

4. **Aggregation pipeline**
   - Hourly aggregation for leaderboard/trends.
   - **Done when:** UI can query top busters by date range.

5. **UI + exports**
   - Add filters and JSON export endpoint.
   - **Done when:** user can export a filtered evidence set.

## Validation

- Simulate known cookie and vary busters in test harness.
- Ensure masked evidence never stores full cookie values.
- Validate attribution fallback when source unknown.

## Rollout

- Enabled with Cacheability Advisor dependency.
- Start runtime-only; add optional code-aware scan behind explicit opt-in.
