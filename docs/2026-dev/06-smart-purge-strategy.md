# Pillar 6 — Smart Purge Strategy

## Objective

Minimize unnecessary global cache flushes by recommending the smallest safe purge scope and by batching noisy invalidation events.

## User Stories

### Story 1: Editor purges only what changed

**As an** editor  
**I want** purge suggestions tied to my content update  
**So that** I avoid hurting site-wide cache hit rates.

**Acceptance criteria**

- Suggests URL-level or section-level purge before global purge.
- Explains likely impact for each option.
- Default action favors narrowest valid scope.

### Story 2: Admin prevents purge storms

**As an** admin  
**I want** debounce/cooldown for repeated updates  
**So that** bulk edits do not repeatedly destroy cache warmup.

**Acceptance criteria**

- Repeated similar events are batched into one purge job.
- Cooldown windows are configurable.
- Event timeline shows batched actions.

## Functional Design

## Purge Scope Hierarchy

1. Single URL.
2. Related URLs (post + taxonomy/archive).
3. Section scope.
4. Global flush (last resort).

## Impact Estimation Inputs

- Historical traffic per URL/template.
- Prior warmup time to steady-state hit ratio.
- Current cache health score.

## Queueing Model

- Introduce `pcm_purge_jobs` queue with statuses.
- Deduplicate by normalized target + time window.
- Execute via async cron/queue runner.

## Data Model

- `wp_pcm_purge_events`
  - source action, object id, actor, timestamp
- `wp_pcm_purge_jobs`
  - scope, targets_json, reason, status, scheduled_at, executed_at
- `wp_pcm_purge_outcomes`
  - estimated_impact, observed_impact, notes

## Admin UX

- Purge decision modal with recommendation.
- Purge activity log with timeline and scope badges.
- Settings for debounce and escalation thresholds.

## Implementation Tasks + Completion Insights

1. **Event normalizer**
   - Normalize existing purge triggers into consistent events.
   - **Done when:** all purge sources create trackable events.

2. **Recommendation engine**
   - Scope selection logic.
   - **Done when:** each event receives suggested minimal scope.

3. **Job queue and dedupe**
   - Queue table + runner + locking.
   - **Done when:** repeated events collapse into single jobs.

4. **Impact estimator**
   - Baseline heuristic model.
   - **Done when:** recommendations include impact messaging.

5. **UI + settings**
   - Decision modal and history screen.
   - **Done when:** admins can tune batching behavior.

## Validation

- Integration tests on chained content updates.
- Verify no missed invalidation for dependent URLs.
- Performance test queue throughput.

## Rollout

- Shadow mode first: log recommendations but do current behavior.
- Enable active smart purge after confidence threshold is met.
