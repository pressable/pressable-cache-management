/*PCM_PLAYBOOK_META
{"playbook_id":"pb_query_gclid","version":"1.0.0","severity":"warning","title":"Tracking Query Fragmentation (gclid/fbclid)","rule_ids":["query_noise_gclid","query_noise_fbclid"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# Tracking Query Fragmentation (gclid/fbclid)

## Problem summary
Tracking params generate duplicate cache entries.

## Quick Fix
- Canonicalize URLs by stripping tracking params for cache keys.

## Technical
- Add redirect normalization for `gclid`, `fbclid`.
- Keep attribution logic in analytics, not in cache key.

## Verify
- Confirm redirects normalize tracking URLs.
- Confirm cache key fragmentation decreases.

