/*PCM_PLAYBOOK_META
{"playbook_id":"pb_object_cache_low_hit","version":"1.0.0","severity":"warning","title":"Low Object Cache Hit Ratio","rule_ids":["low_hit_ratio","object_cache_low_hit_ratio"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# Low Object Cache Hit Ratio

## Problem summary
Object cache hit ratio is below recommended threshold.

## Quick Fix
- Reduce aggressive flush triggers.

## Technical
- Evaluate key churn and TTL strategy.
- Validate cache groups and invalidation scope.

## Verify
- Monitor hit ratio over 24h/7d after changes.

