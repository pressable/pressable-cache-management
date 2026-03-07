# Pillar 1 — Cacheability Advisor (Guidance Engine)

## Objective

Provide an automated, explainable cacheability assessment for key site URLs and template types, with prioritized, actionable recommendations.

## Primary Outcomes

- Reduce anonymous uncached responses.
- Improve cache hit rate for high-traffic pages.
- Turn low-level header data into plain-language guidance.

## User Stories

### Story 1: Site owner sees a cacheability score by page type

**As a** site owner  
**I want** a score for homepage, posts, archives, and key commerce URLs  
**So that** I can quickly identify where caching is weak.

**Acceptance criteria**

- Score shown as 0–100 for each template type.
- Last scan timestamp and sample size displayed.
- “Rescan now” button performs async scan and updates status.

### Story 2: Developer sees why a URL is not cacheable

**As a** developer  
**I want** root-cause reasons for cache misses  
**So that** I can fix specific blockers.

**Acceptance criteria**

- Findings include rule IDs and plain-language explanation.
- Findings reference headers/cookies/query params observed.
- Severity labels: Critical, Warning, Opportunity.

### Story 3: Team gets concrete remediation suggestions

**As a** support engineer  
**I want** recommended fixes sorted by impact and effort  
**So that** I can prioritize implementation.

**Acceptance criteria**

- Each finding has at least one remediation action.
- Actions tagged by expected impact (High/Med/Low) and risk (Low/Med/High).
- Links to playbooks where available.

## Functional Design

## Scan Pipeline

1. Build URL sample set:
   - homepage
   - top N posts/pages by recent traffic (fallback latest content)
   - archives/search
   - WooCommerce key paths if active
2. Perform anonymous HTTP probes (no auth cookies).
3. Capture response metadata:
   - status
   - headers (`Cache-Control`, `Vary`, `Set-Cookie`, `Surrogate-Control`, Batcache markers)
   - effective URL (redirected or canonicalized)
4. Evaluate rule engine.
5. Persist snapshot and score in custom DB table.

## Scoring Model (v1)

- Start at 100.
- Deduct per triggered rule with weighted penalties.
- Clamp at 0.
- Separate sub-scores:
  - Header hygiene
  - URL normalization
  - Cookie cleanliness
  - Variation control

## Data Model

- `wp_pcm_scan_runs`
  - `id`, `started_at`, `finished_at`, `status`, `sample_count`, `initiated_by`
- `wp_pcm_scan_urls`
  - `run_id`, `url`, `template_type`, `status_code`, `score`
- `wp_pcm_findings`
  - `run_id`, `url`, `rule_id`, `severity`, `evidence_json`, `recommendation_id`

## Admin UX

- New menu item/tab: **Cacheability Advisor**.
- Cards by template type with score + trend.
- Findings table filterable by severity/template.
- URL drill-down panel:
  - response summary
  - triggered rules
  - recommended actions

## Implementation Tasks + Completion Insights

1. **Scaffold storage + services**
   - Add migration for scan tables.
   - Add repository class and models.
   - **Done when:** scan runs can be created/read with status lifecycle.

2. **Build probe client**
   - Wrapper around `wp_remote_get` with standardized headers and timeout handling.
   - Strip auth cookies.
   - **Done when:** probes return deterministic metadata payload.

3. **Create rule engine framework**
   - Rule interface (`evaluate($responseContext): Finding[]`).
   - Register initial rules (cookie on anonymous, no-store, volatile vary).
   - **Done when:** each response produces zero or more findings.

4. **Score calculator**
   - Weighted scoring service.
   - **Done when:** URL and template scores are calculated and persisted.

5. **Admin UI**
   - React/Vue-free WP admin markup (consistent with existing plugin style).
   - AJAX endpoints for run status + findings list.
   - **Done when:** user can scan and inspect findings without page reload.

6. **Guidance content mapping**
   - Recommendation library keyed by rule ID.
   - **Done when:** each finding displays actionable text.

## Validation

- Unit tests for rule engine and score calculation.
- Integration tests for probe parsing.
- Manual QA on staging site with known cache blockers.

## Rollout

- Feature flag: `pcm_enable_cacheability_advisor`.
- Start with admin-only scan trigger.
- Add scheduled scans in phase 2.
