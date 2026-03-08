/*PCM_PLAYBOOK_META
{"playbook_id":"pb_opcache_high_waste","version":"1.0.0","severity":"warning","title":"High OPcache Wasted Memory","rule_ids":["high_wasted_memory","opcache_wasted_memory"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# High OPcache Wasted Memory

## Problem summary
OPcache wasted memory is elevated and may trigger restarts.

## Quick Fix
- Review invalidation/deploy cadence and memory settings.

## Technical
- Tune memory budget and timestamp validation strategy.
- Track restart counters after deployment changes.

## Verify
- Ensure wasted percentage trends down.

