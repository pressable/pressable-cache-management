# Fast Follow — Caching Suite Gap Review (Post-Roadmap Audit)

## Audit Outcome

After a deep review of `docs/CACHING_SUITE_ROADMAP.md` and current `includes/` implementations, the Caching Suite is **partially implemented across all pillars** and not yet complete end-to-end.

This document has been updated to convert prior gap notes into a **resolved action register** with:
- clear scope,
- explicit priority,
- concrete deliverables,
- definition-of-done criteria,
- and recommended milestone sequencing.

---

## Current Completion Snapshot

| Pillar | Current state | Completion |
|---|---|---|
| 1) Cacheability Advisor | Storage/repository/run lifecycle scaffolding | Partial |
| 2) Cache-Busting Detector | Detector framework + runtime detector set | Partial |
| 3) Object Cache + Memcached Intelligence | Provider resolver + evaluator + snapshot facade | Partial |
| 4) PHP OPcache Awareness | Collector + recommendations | Partial |
| 5) custom-redirects.php Assistant | Repository + discovery + simulation + exporter | Partial |
| 6) Smart Purge Strategy | Event capture + recommendations + queue runner | Partial |
| 7) Observability & Reporting | Registry + rollups + export/digest services | Partial |
| 8) Guided Remediation Playbooks | Repository + lookup + renderer panel scaffold | Partial |
| 9) Permissions/Safety/Privacy | Capabilities + redaction + audit/retention services | Partial |

---

## Action Register (Resolved for Execution Planning)

Status legend:
- `DONE` = completed in codebase
- `OPEN` = not completed in codebase
- `NEXT` = should be scheduled immediately
- `LATER` = dependent on earlier enabling work

### Pillar 1 — Cacheability Advisor

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A1.1 | P0 | DONE | Implement scan orchestrator (sample → probe → evaluate → score → persist). | One async scan creates a run, persists URL results/findings, and marks final run status correctly. |
| A1.2 | P0 | DONE | Add normalized probe client wrapper around `wp_remote_get()`. | Probe output is deterministic with timeout/retry handling and sanitized headers. |
| A1.3 | P0 | DONE | Add scan endpoints (`start`, `status`, `findings`, `results`). | Endpoints are nonce/capability-protected and support polling without page reloads. |
| A1.4 | P1 | DONE | Build Advisor admin UI (scores, findings table, drill-down). | Admins can run scan, view template scores, and inspect finding evidence/actions. |
| A1.5 | P1 | DONE | Persist per-template trend history for reporting. | Trends available for 24h/7d/30d queries and export-ready shape. |

### Pillar 2 — Cache-Busting Attribute Detector

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A2.1 | P1 | DONE | Persist detector events with timestamp/confidence/source fields. | Event store supports leaderboard and trend queries. |
| A2.2 | P1 | DONE | Add “top cache-busting sources” query/API layer. | Can return top sources by date range and category. |
| A2.3 | P2 | LATER | Add opt-in static/code-aware checks (`nocache_headers`, `DONOTCACHEPAGE`, `session_start`). | Static scan reports candidates and confidence with explicit opt-in audit trace. |
| A2.4 | P1 | DONE | Wire detector metrics into reporting rollups. | Detector incidence appears in reporting trends and digest summaries. |

### Pillar 3 — Object Cache + Memcached Intelligence

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A3.1 | P1 | DONE | Add periodic snapshot persistence + retention. | Snapshot data retained by policy and queryable by 24h/7d windows. |
| A3.2 | P2 | LATER | Expand adapters for slab pressure/item age/group hotspots where available. | Metrics included when backend supports them; graceful fallback otherwise. |
| A3.3 | P1 | DONE | Build diagnostics UI (health, hit/miss, evictions, memory). | Admin panel renders health + recommendation cards and trend visuals. |
| A3.4 | P1 | DONE | Emit normalized metrics to rollups. | Hit ratio/evictions/memory pressure are written via reporting service (not placeholders). |

### Pillar 4 — PHP OPcache Awareness

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A4.1 | P1 | DONE | Persist OPcache snapshots daily with retention. | Historical OPcache snapshots available for trend queries. |
| A4.2 | P1 | DONE | Add admin-only OPcache diagnostics card/panel. | Capability-restricted panel shows memory/restart trendlines and recommendations. |
| A4.3 | P2 | DONE | Add configurable warning/critical thresholds. | Threshold changes update health classification consistently. |
| A4.4 | P0 | DONE | Enforce redaction/safe-output policy for exports. | No sensitive OPcache internals are exported to unauthorized users. |

### Pillar 5 — custom-redirects.php Assistant

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A5.1 | P0 | DONE | Build full wizard UI (discover → edit → dry-run → export). | End-to-end user path available inside WP admin with validation messaging. |
| A5.2 | P1 | DONE | Add copy/download export actions + checksum display. | Generated file can be copied/downloaded with integrity metadata surfaced. |
| A5.3 | P1 | DONE | Add import/rehydration from prior export payload. | Existing exported rules can be reloaded and edited safely. |
| A5.4 | P1 | DONE | Strengthen conflict/loop/wildcard safety checks. | Loop/conflict/high-risk wildcard checks block unsafe export without explicit confirmation. |

### Pillar 6 — Smart Purge Strategy

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A6.1 | P0 | DONE | Integrate queued recommendations with real purge execution hooks (active mode). | Active mode executes scoped purges and logs audited outcomes. |
| A6.2 | P1 | DONE | Expand event capture sources (bulk/import/update/programmatic). | Major purge sources normalized into queue pipeline. |
| A6.3 | P1 | DONE | Measure observed purge impact (post-execution). | Impact report includes pre/post hit-ratio and churn signals. |
| A6.4 | P1 | DONE | Add admin controls for cooldown/debounce/scheduling windows. | Operators can tune queue behavior from settings UI. |

### Pillar 7 — Observability & Reporting

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A7.1 | P0 | DONE | Replace placeholder option reads with real pillar metric emitters. | Daily rollups are sourced from actual module outputs. |
| A7.2 | P1 | DONE | Build reports dashboard (trends + period-over-period). | Admins can view trends for key suite metrics without raw-data queries. |
| A7.3 | P1 | DONE | Add secure downloadable JSON/CSV export endpoints. | Export artifacts are permission-gated and privacy-redacted. |
| A7.4 | P2 | DONE | Add WP-CLI audit/report command set. | CLI commands generate trend snapshots and export output. |

### Pillar 8 — Guided Remediation Playbooks

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A8.1 | P1 | DONE | Link finding rule IDs directly to playbook view in UI. | Every mapped finding can open contextual playbook in one click. |
| A8.2 | P0 | DONE | Replace placeholder “Re-scan now” behavior with real scan trigger. | Playbook rescan action starts advisor scan and provides progress/complete feedback. |
| A8.3 | P1 | DONE | Expand playbook catalog for all high-severity rules. | High-severity findings have mapped playbooks and verification steps. |
| A8.4 | P2 | DONE | Add persistent completion-verification flow tied to post-fix checks. | Checklist completion and post-fix validation are tracked and reviewable. |

### Pillar 9 — Permissions, Safety, Privacy

| ID | Priority | Status | Action | Definition of done |
|---|---|---|---|---|
| A9.1 | P0 | DONE | Add centralized authorization guard for AJAX/REST/CLI surfaces. | Every privileged route is guarded by capability checks + nonce/auth patterns. |
| A9.2 | P1 | DONE | Build privacy settings UI (retention/redaction/advanced scan opt-in). | Admins can configure policy and those settings are enforced in data paths. |
| A9.3 | P1 | DONE | Add audit log viewer for privileged admins. | Risky actions and actor history are visible with tamper-evident sequencing. |
| A9.4 | P0 | DONE | Add permission/redaction coverage tests. | Automated tests fail on missing auth or unredacted sensitive fields. |

---

## Cross-Cutting Execution Priorities

1. **P0 Foundation (required for safe launch):** A1.1, A1.2, A1.3, A4.4, A5.1, A6.1, A7.1, A8.2, A9.1, A9.4.
2. **P1 Product completeness:** dashboard/UI work, snapshot persistence, event expansion, playbook coverage, privacy UX.
3. **P2 Enhancements:** static code-aware scanning, advanced thresholds, WP-CLI suite, deeper hotspot telemetry.

---

## Milestone Sequencing

### Milestone A — Advisor + Remediation Loop (P0)
- Deliver A1.1–A1.4 and A8.1–A8.2.
- Outcome: findings are discoverable, explainable, and re-testable from admin UI.

### Milestone B — Diagnostic Depth (P1)
- Deliver A3.1–A4.2 and A2.1–A2.4.
- Outcome: historical diagnostics and cache-buster trend visibility.

### Milestone C — Safe Automation + Reporting (P0/P1)
- Deliver A6.1–A7.3.
- Outcome: scoped smart purge, production-safe exports, and actionable reporting.

### Milestone D — Governance + Scale (P0/P1/P2)
- Deliver A9.1–A9.4 and remaining P2 actions.
- Outcome: hardened permissions/privacy posture and automation ergonomics.

---

## Exit Criteria (When this fast-follow plan is complete)

The fast-follow backlog is complete when all criteria below are true:
1. Every roadmap pillar has production-usable UI + backend wiring (no placeholder actions).
2. Metrics for advisor/cache-busters/object cache/OPcache/purge are persisted and trended.
3. Exports/digests/automation are capability-gated and privacy-safe by default.
4. Smart purge active mode is verified with measured impact telemetry.
5. High-severity findings have linked playbooks and verification workflow.
