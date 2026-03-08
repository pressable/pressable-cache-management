/*PCM_PLAYBOOK_META
{"playbook_id":"pb_vary_user_agent","version":"1.0.0","severity":"warning","title":"Vary on User-Agent","rule_ids":["vary_user_agent","vary_high_cardinality_user_agent"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# Vary on User-Agent

## Problem summary
`Vary: User-Agent` creates too many variants.

## Quick Fix
- Replace broad UA vary with targeted device rules only if required.

## Technical
- Avoid per-browser variance for static pages.
- Prefer responsive frontend over UA-specific cache variants.

## Verify
- Ensure fewer variants and better cache-hit consistency.

