# Pillar 4 — PHP OPcache Awareness

## Objective

Give administrators clear visibility into OPcache state and practical tuning insights without requiring phpinfo access.

## User Stories

### Story 1: Admin can see OPcache status at a glance

**As an** admin  
**I want** to know whether OPcache is enabled and healthy  
**So that** I can avoid hidden PHP performance issues.

**Acceptance criteria**

- Status displays enabled/disabled and health level.
- Memory and restart counters shown.
- Sensitive path details are hidden.

### Story 2: Developer receives targeted tuning hints

**As a** developer  
**I want** hints based on memory/restart behavior  
**So that** I can tune OPcache effectively.

**Acceptance criteria**

- Guidance for high wasted memory, frequent restarts, low free memory.
- Notes for timestamp validation trade-offs.
- Post-change verification checklist included.

## Functional Design

## Data Collection

- Use `opcache_get_status(false)` if available.
- Read ini values with `ini_get` for key directives.
- Poll snapshots on demand and optionally scheduled.

## Metrics

- Enabled flag.
- `used_memory`, `free_memory`, `wasted_memory`.
- `num_cached_scripts`, `max_cached_keys`.
- `opcache_hit_rate` (if available).
- Restart counters and reason fields.

## Recommendation Rules (v1)

- Free memory < 10%: suggest increasing memory budget.
- Wasted memory > 10% sustained: investigate invalidation churn.
- Frequent restarts in 24h: identify deployment/invalidation patterns.

## Data Model

- `wp_pcm_opcache_snapshots`
  - `taken_at`, `enabled`, `memory_json`, `stats_json`, `ini_json`
- `wp_pcm_opcache_recommendations`
  - `snapshot_id`, `rule_id`, `severity`, `message`

## Admin UX

- New tab: **PHP OPcache**.
- Summary cards: status, hit rate, memory pressure, restarts.
- Expandable section for ini directives with plain-language definitions.

## Implementation Tasks + Completion Insights

1. **Collector service**
   - Safe wrapper around OPcache functions.
   - **Done when:** no warnings/fatals when OPcache absent.

2. **Normalizer + storage**
   - Normalize raw arrays into typed snapshot.
   - **Done when:** snapshots persist and can be charted.

3. **Recommendation engine**
   - Rule-based generator.
   - **Done when:** meaningful hints appear for threshold breaches.

4. **Admin rendering**
   - Cards, trend lines, config table.
   - **Done when:** users can interpret OPcache health quickly.

5. **Security pass**
   - Capability gates + redaction of sensitive details.
   - **Done when:** non-admins cannot access OPcache diagnostics.

## Validation

- Unit tests with mocked OPcache payloads.
- Manual test with OPcache disabled and enabled.
- Verify no sensitive file path leakage.

## Rollout

- Default enabled for admins only.
- Add optional “advanced details” toggle.
