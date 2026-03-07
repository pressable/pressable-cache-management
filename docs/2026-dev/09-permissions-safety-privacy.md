# Pillar 9 — Permissions, Safety, and Privacy

## Objective

Ensure all diagnostics and automation features follow strict capability boundaries, safe defaults, and privacy-preserving data handling.

## User Stories

### Story 1: Admin controls who can access advanced diagnostics

**As an** administrator  
**I want** granular permissions for sensitive caching/system data  
**So that** only trusted roles can view or act on it.

**Acceptance criteria**

- Capability checks protect every route and action.
- Separate read vs write capabilities for high-impact actions.
- Permission matrix documented in settings/help.

### Story 2: Site remains privacy-safe when collecting telemetry

**As a** compliance-conscious owner  
**I want** telemetry to avoid PII by default  
**So that** diagnostics do not create privacy risk.

**Acceptance criteria**

- Cookie values and personal query params are masked/redacted.
- Data retention policy is configurable.
- Exported artifacts remain privacy-safe.

### Story 3: Risky actions require explicit confirmation

**As an** operator  
**I want** confirmation and guardrails before destructive changes  
**So that** accidental impact is minimized.

**Acceptance criteria**

- Global purge, redirect wildcard rules, and advanced scans require explicit confirmations.
- Actions logged with actor + timestamp.
- Optional “dry-run first” path where applicable.

## Functional Design

## Capability Model (proposed)

- `pcm_view_diagnostics`
- `pcm_run_scans`
- `pcm_manage_redirect_rules`
- `pcm_flush_cache_global`
- `pcm_export_reports`
- `pcm_manage_privacy_settings`

Map defaults to admin, with optional delegation.

## Privacy Controls

- Redaction pipeline for evidence payloads.
- Denylist for sensitive query keys (`email`, `token`, `auth`, etc.).
- Hashing/tokenization for identifiable values.
- Data retention settings (e.g., 30/60/90 days).

## Audit Logging

- Record all high-impact actions and privileged reads.
- Include actor, action, target, reason, timestamp.
- Display tamper-evident sequence IDs.

## Data Model

- `wp_pcm_audit_log`
  - `id`, `actor_id`, `action`, `target`, `context_json`, `created_at`
- `wp_pcm_privacy_settings`
  - retention_days, redaction_level, export_restrictions

## Admin UX

- New tab: **Security & Privacy**.
- Permission matrix viewer.
- Retention/redaction controls.
- Audit log viewer with filters and export.

## Implementation Tasks + Completion Insights

1. **Capability hardening pass**
   - Audit all endpoints/pages/actions.
   - **Done when:** every privileged action is gated and tested.

2. **Redaction middleware**
   - Standard sanitizer used by all collectors and exporters.
   - **Done when:** sensitive values cannot be persisted unmasked.

3. **Retention manager**
   - Scheduled cleanup by data type and policy.
   - **Done when:** old telemetry is auto-pruned.

4. **Audit log service**
   - Centralized log writer and viewer.
   - **Done when:** high-risk actions are traceable.

5. **Consent and notices**
   - Explain optional advanced scan implications.
   - **Done when:** admins explicitly opt into invasive diagnostics.

## Validation

- Permission unit/integration tests by role.
- Redaction tests with representative sensitive payloads.
- Security review for endpoint exposure and nonce handling.

## Rollout

- Ship capability and redaction baseline before enabling advanced diagnostics.
- Treat privacy controls as a hard dependency for telemetry-heavy features.
