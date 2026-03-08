/*PCM_PLAYBOOK_META
{"playbook_id":"pb_cookie_anonymous","version":"1.0.0","severity":"critical","title":"Anonymous Set-Cookie Blocks Caching","rule_ids":["cookie_on_anonymous","set_cookie_anonymous","anonymous_set_cookie"],"audiences":["site_owner","developer","host_support"]}
PCM_PLAYBOOK_META*/
# Anonymous Set-Cookie Blocks Caching

## Problem summary
Anonymous visitors receive `Set-Cookie`, forcing cache bypass.

## Typical causes
- Marketing/personalization plugins writing cookies on all requests.
- Theme code creating session-like identifiers for every user.

## Quick Fix
- Disable non-essential cookie features for anonymous users.
- Exclude cookie-setting logic from public pages.

## Technical
- Move cookie writes behind authenticated checks.
- Use `is_user_logged_in()` guards.
- Inspect response headers after each change.

## Verify
- Confirm public responses no longer return `Set-Cookie`.
- Confirm cache headers indicate cacheability.
- Re-run cacheability scan.

