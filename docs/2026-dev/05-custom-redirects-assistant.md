# Pillar 5 — custom-redirects.php Assistant

## Objective

Provide a guided, safe workflow to generate cache-friendly redirect rules and export a valid `custom-redirects.php`.

## User Stories

### Story 1: Admin generates canonicalization rules from real traffic patterns

**As an** admin  
**I want** the plugin to suggest redirect candidates  
**So that** duplicate URL variants are normalized.

**Acceptance criteria**

- Candidate list includes source URL patterns and destination canonical forms.
- Candidates can be accepted/rejected individually.
- Rules show expected impact and confidence.

### Story 2: Developer validates rules before deployment

**As a** developer  
**I want** dry-run simulation and loop detection  
**So that** I avoid redirect regressions.

**Acceptance criteria**

- Simulation test input supports single URL and batch mode.
- Loop and conflicting-rule detection is built in.
- Validation results display status and destination per test URL.

### Story 3: User exports valid file output

**As a** site owner  
**I want** copy/download output of `custom-redirects.php`  
**So that** I can apply rules safely.

**Acceptance criteria**

- Export passes syntax validation.
- Includes header comments with generation timestamp.
- Can regenerate from saved rule set.

## Functional Design

## Candidate Sources

- Query-param normalization (`utm_*`, `fbclid`, `gclid`).
- Slash and case canonicalization patterns.
- Known duplicate permalink variants.

## Rule Model

- `id`, `enabled`, `match_type` (exact/prefix/regex), `source_pattern`, `target_pattern`, `status_code`, `notes`.
- Deterministic execution order with priority.

## Validation Engine

- Syntax validation.
- Conflict detection (two rules target overlapping source sets).
- Loop detection via graph traversal with hop cap.
- Dangerous wildcard warning prompts.

## Export Format

- Build deterministic PHP array/config block.
- Include checksum and schema version comment.
- Preserve rule IDs for diff-friendly updates.

## Data Model

- `wp_pcm_redirect_rules`
  - `id`, `enabled`, `priority`, `match_type`, `source`, `target`, `code`, `created_by`, `updated_at`
- `wp_pcm_redirect_simulations`
  - `run_id`, `input_url`, `result_status`, `result_url`, `warnings_json`

## Admin UX

- New tab: **Redirect Assistant**.
- Steps: Discover → Curate → Validate → Export.
- Save draft sets and compare versions.

## Implementation Tasks + Completion Insights

1. **Rule editor + persistence**
   - CRUD endpoints and validation.
   - **Done when:** users can build/save ordered rule sets.

2. **Candidate discovery**
   - Heuristics from observed URLs and known patterns.
   - **Done when:** top candidates appear with confidence scores.

3. **Simulation engine**
   - Apply rules in-memory and report outcomes.
   - **Done when:** loop/conflict warnings are surfaced.

4. **Exporter**
   - Generate `custom-redirects.php` string with schema metadata.
   - **Done when:** exported output passes `php -l` checks.

5. **Versioning and rollback aids**
   - Save named rule versions.
   - **Done when:** prior export can be rehydrated for edits.

## Validation

- Unit tests for matcher and loop detection.
- Golden-file tests for deterministic export output.
- Manual QA with representative URL sets.

## Rollout

- Initially export-only (no auto-write to filesystem).
- Auto-write capability can be added later behind explicit permission checks.
