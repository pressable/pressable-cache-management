/*PCM_PLAYBOOK_META
{"playbook_id":"pb_purge_storm","version":"1.0.0","severity":"critical","title":"Repeated Global Purges","rule_ids":["repeated-global-purges","purge_storm"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# Repeated Global Purges

## Problem summary
Frequent global flushes prevent cache warmup.

## Quick Fix
- Enable cooldown/debounce and prefer URL-level purges.

## Technical
- Audit all purge triggers and remove unnecessary global calls.
- Batch repetitive events into one queued purge.

## Verify
- Confirm purge frequency drops.
- Confirm hit-rate recovery after batching.

