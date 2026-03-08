/*PCM_PLAYBOOK_META
{"playbook_id":"pb_no_store_public","version":"1.0.0","severity":"critical","title":"No-Store on Public Pages","rule_ids":["no_store_public","cache_control_no_store","cache_control_not_public"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# No-Store on Public Pages

## Problem summary
Public pages send `Cache-Control: no-store/private/max-age=0`.

## Quick Fix
- Identify plugin/theme headers setting restrictive directives.
- Remove no-store/private directives from anonymous public responses.

## Technical
- Audit `send_headers` callbacks and middleware.
- Ensure personalized routes only apply restrictive headers where needed.

## Verify
- Confirm public pages return cache-friendly directives.
- Compare hit-rate trend before/after.

