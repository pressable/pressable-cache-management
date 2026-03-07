# Pillar 8 — Guided Remediation Playbooks

## Objective

Convert findings into role-specific, step-by-step remediation guides that improve time-to-fix and consistency.

## User Stories

### Story 1: Non-technical owner can follow a guided fix

**As a** site owner  
**I want** simple instructions connected to a detected issue  
**So that** I can take meaningful action without deep cache expertise.

**Acceptance criteria**

- Each finding links to a playbook with plain language.
- Steps include expected outcome and risk notes.
- Verification checklist confirms success.

### Story 2: Developer gets advanced implementation details

**As a** developer  
**I want** deeper technical steps and edge cases  
**So that** I can implement robust fixes.

**Acceptance criteria**

- Playbooks include technical appendix and examples.
- Include rollback guidance.
- Includes links to related WordPress hooks/functions.

## Functional Design

## Playbook Structure

- Problem summary.
- Typical causes.
- Role-specific fix steps:
  - Site owner
  - Developer
  - Host/support
- Validation steps (headers, behavior, score changes).
- Escalation criteria.

## Mapping Model

- Rule IDs map to playbook IDs.
- Findings UI fetches relevant playbook dynamically.
- Allow versioned updates to playbooks.

## Data Storage Options

- v1: Markdown files bundled in plugin.
- v2: Optional remote-updated playbook catalog with signature validation.

## Admin UX

- “View fix guide” button on each finding.
- Side panel with tabs: Quick Fix, Technical, Verify.
- Mark-step-complete checklist state in browser/local storage.

## Implementation Tasks + Completion Insights

1. **Playbook schema**
   - Define YAML frontmatter/JSON metadata for severity, audience, rule mapping.
   - **Done when:** playbooks are machine-indexable.

2. **Initial playbook authoring**
   - Top 10 cache-busting scenarios first.
   - **Done when:** every high-severity rule has a linked playbook.

3. **Renderer + parser**
   - Safe markdown renderer in admin.
   - **Done when:** playbooks render consistently and securely.

4. **Finding integration**
   - Rule-to-playbook lookup service.
   - **Done when:** users can open guide from finding details.

5. **Verification helpers**
   - Built-in checklist and re-scan trigger.
   - **Done when:** users can validate fix completion from same flow.

## Validation

- Content QA for clarity and correctness.
- XSS/security checks for markdown rendering.
- Rule coverage audit (all critical rules linked).

## Rollout

- Start with static bundled playbooks.
- Introduce remote updates only with strict integrity checks.
