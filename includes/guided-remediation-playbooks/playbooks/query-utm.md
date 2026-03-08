/*PCM_PLAYBOOK_META
{"playbook_id":"pb_query_utm","version":"1.0.0","severity":"warning","title":"UTM Parameter Normalization","rule_ids":["query_noise_utm","query_noise_utm_campaign"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# UTM Parameter Normalization

## Problem summary
UTM params create non-canonical variants.

## Quick Fix
- Redirect UTM variants to canonical path.

## Technical
- Implement normalization in custom redirects ruleset.
- Keep marketing attribution independent from page response variance.

## Verify
- Test sample UTM URLs and confirm canonical destination.

