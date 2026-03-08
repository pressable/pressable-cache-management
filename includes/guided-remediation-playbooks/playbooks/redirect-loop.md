/*PCM_PLAYBOOK_META
{"playbook_id":"pb_redirect_loop","version":"1.0.0","severity":"critical","title":"Redirect Loop Detected","rule_ids":["redirect_loop_detected","loop_conflict"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# Redirect Loop Detected

## Problem summary
Two or more rules create circular redirects.

## Quick Fix
- Disable newest conflicting rule and retest.

## Technical
- Use simulation engine to inspect hop chain.
- Resolve overlapping prefix/exact rules and keep deterministic priority.

## Verify
- Test affected URLs, confirm single redirect to final destination.

