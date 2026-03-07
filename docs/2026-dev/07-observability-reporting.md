# Pillar 7 — Observability & Reporting

## Objective

Provide trend-based insight into caching health and shareable reporting for site teams and support.

## User Stories

### Story 1: Admin tracks caching improvements over time

**As an** admin  
**I want** dashboard trends for key cache metrics  
**So that** I can confirm improvements after changes.

**Acceptance criteria**

- Trend views for 24h/7d/30d.
- Key metrics include cacheability score, batcache hits, object cache hit ratio, evictions.
- Baseline vs current comparisons displayed.

### Story 2: Team receives scheduled summaries

**As a** team lead  
**I want** weekly digest emails  
**So that** stakeholders can monitor progress asynchronously.

**Acceptance criteria**

- Digest includes top regressions and wins.
- Supports configurable recipients and send day.
- Links back to relevant plugin dashboards.

### Story 3: Support exports diagnostics

**As a** support engineer  
**I want** JSON/CSV exports  
**So that** I can investigate incidents externally.

**Acceptance criteria**

- Exports support date-range and metric filters.
- Exports respect capability restrictions.
- Personally identifiable info is omitted.

## Functional Design

## Metric Catalog (v1)

- Cacheability score trend.
- Cache-buster incidence trend.
- Purge frequency by scope.
- Object cache hit/miss/evictions.
- OPcache memory pressure and restarts.

## Aggregation

- Nightly rollups into compact summary tables.
- Keep raw events with retention policy (e.g., 30–90 days).

## Reporting Engine

- Template-driven digest builder.
- Alert logic for threshold breaches (optional phase 2).

## Data Model

- `wp_pcm_metric_rollups`
  - `metric_key`, `bucket_start`, `bucket_size`, `value`, `dimensions_json`
- `wp_pcm_reports`
  - `id`, `type`, `generated_at`, `params_json`, `artifact_path`

## Admin UX

- New tab: **Reports**.
- Prebuilt charts + compare period switch.
- Export controls and email digest settings.

## Implementation Tasks + Completion Insights

1. **Metric registry**
   - Define canonical metric keys and units.
   - **Done when:** all feature modules write to shared registry.

2. **Rollup jobs**
   - Scheduled aggregation and retention cleanup.
   - **Done when:** trend queries use rollups, not raw heavy scans.

3. **Chart endpoints**
   - Date-range APIs for dashboard visualization.
   - **Done when:** dashboard loads quickly on large sites.

4. **Export service**
   - CSV/JSON generation with redaction policy.
   - **Done when:** filtered exports are downloadable and valid.

5. **Digest scheduler**
   - Cron + email template renderer.
   - **Done when:** weekly digests send with metric summary.

## Validation

- Snapshot tests for report templates.
- Data consistency checks between raw and rollup values.
- Permission tests for export endpoints.

## Rollout

- Launch dashboard first, then digest, then external export APIs.
