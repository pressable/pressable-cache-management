# Pillar 3 — Object Cache + Memcached Intelligence

## Objective

Expose object-cache health and Memcached behavior in actionable terms for non-infrastructure users.

## User Stories

### Story 1: Admin can confirm object cache is healthy

**As an** admin  
**I want** to see Memcached connectivity and hit/miss trends  
**So that** I can identify if object cache is helping.

**Acceptance criteria**

- Health badge shows Connected/Degraded/Offline.
- Hit ratio shown with timeframe selector.
- Evictions and memory pressure visible with thresholds.

### Story 2: Developer gets tuning guidance

**As a** developer  
**I want** recommendations based on stats  
**So that** I can make high-value configuration changes.

**Acceptance criteria**

- Recommendations generated for low hit ratio/high evictions.
- Recommendation text references observed metric ranges.
- Includes “verify after change” checklist.

## Functional Design

## Data Collection

- Read from object cache drop-in APIs when available.
- Fallback to Memcached extension stats if accessible.
- Snapshot every 5–15 minutes via WP-Cron.

## Key Metrics

- Hit ratio.
- Evictions/min.
- Memory used/free.
- Reclaimed items.
- Hot key groups (if available).

## Health Heuristics (v1)

- Hit ratio < 70%: warning.
- Evictions sustained > threshold for 30m: critical.
- Connection failures > N in interval: critical.

## Data Model

- `wp_pcm_memcache_snapshots`
  - `taken_at`, `status`, `hit_ratio`, `evictions`, `bytes_used`, `bytes_limit`, `meta_json`
- `wp_pcm_memcache_recommendations`
  - `snapshot_id`, `rule_id`, `severity`, `message`

## Admin UX

- New tab: **Object Cache**.
- Summary cards (health, hit ratio, evictions, memory).
- Trend charts and recommendations list.
- Troubleshooting accordion for common failure modes.

## Implementation Tasks + Completion Insights

1. **Adapter layer**
   - `ObjectCacheStatsProviderInterface` with drop-in + extension adapters.
   - **Done when:** adapters return unified metrics payload.

2. **Snapshot scheduler**
   - Cron event and retention policy.
   - **Done when:** periodic snapshots persist with bounded table growth.

3. **Health evaluator**
   - Threshold rule set + recommendation mapping.
   - **Done when:** each snapshot has derived health and recommendations.

4. **UI implementation**
   - Build metrics cards and trend components in admin styles.
   - **Done when:** users can inspect 24h/7d ranges.

5. **Failure handling**
   - Graceful empty states when stats inaccessible.
   - **Done when:** plugin remains stable without Memcached access.

## Validation

- Mock adapter tests for varied metrics.
- Manual checks on environments with/without object cache.
- Verify no fatal if extension is absent.

## Rollout

- Read-only diagnostics first.
- Optional advanced mode to expose per-group hot spots.
