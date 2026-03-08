/*PCM_PLAYBOOK_META
{"playbook_id":"pb_vary_cookie","version":"1.0.0","severity":"warning","title":"High-Cardinality Vary: Cookie","rule_ids":["vary_cookie","vary_high_cardinality_cookie","volatile_vary"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# High-Cardinality Vary: Cookie

## Problem summary
`Vary: Cookie` fragments cache keys and reduces hit rates.

## Quick Fix
- Remove `Vary: Cookie` from anonymous responses.

## Technical
- Restrict vary rules to endpoints truly requiring per-cookie variation.
- Use explicit variation keys instead of blanket cookie vary.

## Verify
- Confirm vary header no longer includes `Cookie` on public pages.
- Validate stable hit ratio improvement.

