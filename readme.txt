=== Pressable Cache Management ===
Contributors: pressable
Tags: cache, batcache, object cache, edge cache, pressable
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 6.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt

Pressable cache management made easy.

== Description ==

Pressable Cache Management gives you full control over object cache, Batcache, and Edge Cache directly from the WordPress admin dashboard.

**Features:**

* Flush Object Cache (global)
* Flush Batcache for Individual Pages from the page preview toolbar
* Extend Batcache storage time by 24 hours
* Flush Cache automatically on Plugin/Theme Update
* Flush Cache automatically on Post/Page Edit
* Flush Cache automatically on Page Delete
* Flush Cache automatically on Comment Delete
* Flush Batcache for WooCommerce product pages
* Exclude specific pages from Batcache & Edge Cache
* Enable / Disable / Purge Edge Cache
* Show or Hide Pressable Branding
* Available in English, Spanish, French, Dutch, Chinese (Simplified), and Hindi

== Installation ==

1. Upload the `pressable-cache-management` folder to `/wp-content/plugins/`
2. Run `composer install` inside the plugin directory to install the auto-update library
3. Activate the plugin through the **Plugins** menu in WordPress
4. Navigate to **Cache Management** in the admin sidebar

== Changelog ==

= 6.6.1 =
* Batcache status check: a response served from a stored copy, in the Edge Cache (x-ac: HIT) or the preload cache (x-sc: HIT), is now skipped. Its headers date from when it was stored, so a copy saved while Batcache worked could show "Batcache Active" after Batcache stopped. The next request uses a new fbclid[] value; if every response is a stored copy, the status is "Status Unavailable" instead of "Batcache Active".
* Flush Cache on Page Delete: the toggle description said it flushes the specific page. It now says what the setting does: it flushes the whole object cache, which also clears Batcache, when a published post or page is trashed, permanently deleted or restored from the trash.

= 6.6.0 =
* Batcache status check: it now names what blocks caching. When the homepage is not being cached, one extra traced request watches every hook callback for the moment a Set-Cookie header, a cache-blocking Cache-Control header or a batcache_cancel() call appears, and reports the plugin, theme or must-use plugin responsible with its file, line, function and hook. It also covers code that runs while a plugin's main file loads and headers sent from an output-buffer callback. The traced request carries a one-time token and is never cached; no other request is affected (admin/custom-functions/batcache_trace.php).
* Batcache status check: when the trace cannot name the source of a cookie (for example it is set before plugins finish loading), the active plugins, the theme and must-use plugins are searched for the cookie name and the matches are listed. The search is capped at 4 seconds and cached for an hour.
* Batcache status check: new "Caching Blocked by a Header" status for a Cache-Control header with no-store, no-cache, private or max-age=0 (for example from a third-party page cache plugin), showing the header and who sends it. New "Batcache Switched Off" status when WP_CACHE is false, the Batcache drop-in is missing or replaced, or max_age is 0; this is read from a visitor's request because the platform turns WP_CACHE off for staff admin sessions. Active third-party page cache plugins (WP Rocket, W3 Total Cache, LiteSpeed Cache and others) are listed as a likely cause.
* Batcache status check, accuracy: a "Batcache-Set" or same-second "Batcache-Hit" answer no longer counts as working when the response says max-age=0 or s-maxage=0. Batcache stores such a page but the copy expires at once, so the badge used to show "Batcache Active" on sites where nothing was cached.
* Batcache status check: when Batcache works but a no-store or private header still keeps Edge Cache from storing the page, a warning names the header and its source.
* Batcache status check, less load: opening the settings page no longer calls the server every time. The badge and alert are rendered from the stored result, and a new check runs only when that result is missing, older than 30 minutes (active) or 5 minutes (not active), after a cache flush or a plugin/theme change, or when the refresh button is clicked.
* Batcache tooltip rewritten to describe the check, the traced request and how long results are kept.

= 6.5.1 =
* WooCommerce Cart Caching: when WooCommerce is not running, the card now says what to do. If WooCommerce is installed but inactive it shows "WooCommerce is installed but not active" with an Activate WooCommerce link (one click, for users who can activate plugins); if it is not installed it explains that Cart Caching is for WooCommerce stores, with an Install WooCommerce link. Previously it only said "WooCommerce is not active on this site."
* WooCommerce Cart Caching: the setting is now read even when WooCommerce is inactive. If cart caching was left on, the card shows "Enabled — but WooCommerce is not active, so it has no effect" with Disable available, instead of hiding it.
* Cart Caching and Cache Preload: when either is on while Edge Cache is off, the card says it has no effect until Edge Cache is enabled again, and Disable stays available.
* Edge Cache tab: while Defensive Mode is on, Edge Cache was reported as off, so the tab offered "Enable Edge Cache", locked Purge, and told Preload and Cart Caching that Edge Cache must be enabled first. Defensive Mode runs on top of Edge Cache, so it is now treated as on.
* Cache Preload and Cart Caching: an "Authorization" error from WP Cloud now explains that the beta feature may not be available for the site yet and to contact Pressable Support, instead of showing only the raw error.

= 6.5.0 =
* New: WooCommerce Cart Caching (WP Cloud beta) on the Edge Cache tab. Normally a shopper with items in the cart bypasses Edge Cache on every page. With cart caching on, they get the cached page and WooCommerce fills in their cart with JavaScript. Cart and checkout are never cached and logged-in users still bypass the cache. The card shows whether it is on, has Enable/Disable buttons (Enable asks for confirmation), explains how to test the theme, and is inactive with a note when WooCommerce is not active or Edge Cache is off. It uses the edge-cache mu-plugin's query_ec_backend('woo_cart_cache') (admin/custom-functions/woo_cart_cache.php).
* The Edge Cache API helper used by Cache Preload is now shared by both features (pcm_edge_feature_request), with the same longer timeouts and read-back after a failed toggle.

= 6.4.2 =
* Edge Cache Preload: turning preload on or off failed with "cURL error 28: Operation timed out after 2001 milliseconds". The edge-cache mu-plugin gives the WP Cloud API 2 seconds, and the preload toggle takes longer because it schedules the crawl before answering. Toggle requests now allow 20 seconds and status requests 10. After any toggle error the current state is read back, and if the change was applied anyway the panel shows it instead of the error; a remaining timeout shows a plain-language message.
* Footer: the right side now reads "Send feedback · PCM v6.4.2"; the Pressable Support link was removed from the footer (the Batcache alert still links to support).

= 6.4.1 =
* Batcache status badge: when cookies block Batcache, one extra request is made as a returning visitor carrying those cookies. The alert now says whether the cookies are set only for first-time visitors (Batcache stores a page once a returning visitor loads it, so the site is cold after every flush until that happens) or on every request (Batcache can never store the page). Seen on brodo.com right after the 6.4.0 update flushed the cache: Stape Conversion Tracking's Cookie Keeper sets _sbp for new visitors.
* Batcache status badge: added Stape Conversion Tracking (GTM Server Side) Cookie Keeper (_sbp) to the known cookie list with the setting to turn off.

= 6.4.0 =
* New: Edge Cache Preload (WP Cloud beta) on the Edge Cache tab. The preloader crawls the site from the front page, stores pages in a preload cache at the origin data center and refreshes them after purges, so visitors do not wait for WordPress on pages that miss the edge. The panel shows whether it is on, the crawl state (scheduled, crawling, finished, error), the number of pages preloaded, and Enable/Disable buttons; it refreshes itself every 10 seconds while a crawl is running. Calls go through the edge-cache mu-plugin's query_ec_backend('preload'), the same signed channel Defensive Mode uses, so no extra credentials are needed (admin/custom-functions/cache_preload.php). Edge Cache must be enabled. The crawl renders every page it finds, so it uses PHP workers while running, and a cache flush drops the warmed pages and starts a new crawl.
* Batcache status badge: the check now runs on the server instead of in the browser. The browser fetch() read the Pressable edge cache, which can hold a copy of a Batcache miss (no x-nananana header) for up to a day, so the badge showed "Batcache Broken" on sites where Batcache worked. The server requests the homepage up to three times with a unique fbclid[] array value: the edge does not strip the array form (it does strip a plain fbclid), so every request reaches origin, while Batcache ignores any fbclid key, so the real homepage cache entry is tested.
* Batcache status badge: cookie interference is now detected directly. Batcache never stores a response that sets a cookie, and Set-Cookie headers are only visible server-side. The badge shows "Batcache Blocked by Cookies" with the cookie names and, for well-known names (PHPSESSID, WooCommerce, Polylang, WPML, Wordfence, Jetpack, Mailchimp, CookieYes, Complianz and others), the plugin that sets them.
* Batcache status badge: anything other than an active Batcache now opens an alert panel under the header with a title, what was observed, each cookie with the plugin that usually sets it and how to fix it, and numbered next steps. New edge cases: a homepage answering HTTP 4xx/5xx (maintenance, coming soon, a Defensive Mode challenge) shows "Status Unavailable" with the code instead of "Broken"; a homepage listed in Exclude Pages shows "Homepage Excluded"; a very short Cache-Control max-age is called out.
* Batcache status badge: results are stored for a day and a check runs at most once a minute unless the refresh button is clicked, with a lock against two tabs probing at once. The 90-second recovery poll and the five-fetch browser engine are removed. A check that cannot reach the site shows "Status Unavailable" with the error instead of "Batcache Broken".
* Release workflow: pinned Ruby 3.1.3 is no longer available on GitHub's ubuntu-26.04 runners, which made the PackageReleasePluginCI job fail before creating the tag and release; it now uses the latest 3.1.x.
* Settings page: the admin footer now shows the plugin version ("Pressable Cache Management v6.4.0") instead of the WordPress version, and carries "Send feedback" (prefilled GitHub issue on the plugin repo) and "Pressable Support" links; the Batcache alert panel links to both as its last step.
* Settings page: the page could be scrolled sideways because the wrapper had a negative right margin that made it 20px wider than the viewport. The margin is removed and the two-column card grid collapses to one column under 1100px.

= 6.3.5 =
* Flush Cache on Comment Delete now also flushes when a comment is trashed outside wp-admin (WP-CLI, the REST API and apps that use it, such as the WordPress mobile app). Its code was only loaded on wp-admin requests.

= 6.3.4 =
* Advanced Post Cache deprecation: confirmed this plugin never calls Advanced Post Cache ($advanced_post_cache_object->flush_cache()), so it does not cause the "advanced-post-cache: this plugin is deprecated" PHP warning. Sites that log the warning have separate custom code (such as the "Flush Object Cache on Particular Page" mu-plugin or a line in wp-config.php) that needs updating.
* Fixed Batcache invalidation in the post-list Flush Cache link, the toolbar "Flush Cache for This Page" button and Flush Cache on Post/Page Edit: on object caches that keep the Batcache group local, wp_cache_set() was called with shifted arguments, so the version key was written to the default group with the group name as its value. The three copies are replaced by one helper, pcm_flush_batcache_url() in admin/custom-functions/flush_batcache_url.php.
* Toolbar "Flush Cache for This Page" now flushes and purges the right URL on sites installed in a subdirectory (the page path was appended to home_url(), repeating the subdirectory) and on pages with non-ASCII or percent-encoded slugs (the path was URL-decoded, so it no longer matched the key Batcache stores). A missing nonce or path no longer raises PHP warnings.
* Flush Cache on Post/Page Edit now also flushes a published post's old URL when the post is unpublished, trashed or its slug changes. Previously the old cached copy stayed live until it expired.
* Flush Cache on Page Delete: the "Last flushed at" timestamp was overwritten on every post save even when nothing was flushed; it is now written only when the cache is flushed. Published posts and pages deleted permanently without going to the trash now flush the cache as well.
* Flush Object Cache button: the flush ran only when the admin bar rendered, so the success notice could appear without anything being flushed. It now flushes as soon as the request is verified.
* Post-list Flush Cache link: a missing post ID no longer raises PHP warnings, responses are sent with wp_send_json(), and column.js now declares its jQuery dependency and uses filemtime() versioning.
* Defensive Mode: a WP_Error from the Edge Cache backend caused the fatal error "Cannot use object of type WP_Error as array". The error message is now shown instead.
* Security: Enable/Disable Edge Cache now also requires manage_options, matching Purge Edge Cache and Defensive Mode.
* Fixed the Edge Cache settings sanitize callback name (egde_ to edge_), which did not match the name registered with register_setting().
* Exclude Pages mu-plugin: no longer raises warnings under WP-CLI and cron, where REQUEST_URI is not set, and now matches excluded pages with or without a trailing slash and their paginated URLs (/about/page/2/), which never matched before because saved paths end in "/".
* WooCommerce Batcache manager mu-plugin: timestamps now use gmdate() instead of temporarily changing the PHP default timezone.
* Flush Cache on Comment Delete: added the ABSPATH direct-access guard and gmdate() timestamps.
* Uninstall: the recursive delete helper is renamed pcm_rrmdir() so it cannot collide with another plugin's rrmdir(), the object cache is flushed once instead of once per file, and the Defensive Mode options and Edge Cache status transient are now removed.
* Removed unused files: the empty admin/public/js/toolbar_remove_branding.js (still enqueued on every page) and the unreferenced remove-old-mu-plugins.php and remove-mu-plugins-batcache-on-uninstall.php duplicates.

= 6.3.3 =
* Edge Cache tab: the Defensive Mode status check is now isolated so a failure in it can never affect the Enable/Disable Edge Cache controls or the Purge Edge Cache button, and a failure shows an error message instead of leaving the status on "Finding status".
* Security: removed the unused admin/custom-functions/cache-purge-admin-bar.php, whose pressable_cache_purge AJAX handler had neither a nonce nor a capability check.
* Fixed the Cache Settings link disappearing from the admin bar menu: its node id was the generic 'settings', so any other plugin registering an admin bar node with that id overwrote it. It now uses the namespaced id 'pcm-cache-settings', is shown to users with manage_options (the capability the settings page itself requires), is hidden only while viewing the plugin's own settings page, and is now also available in the front-end Flush Cache toolbar menu.

= 6.2.3 =
* Fixed fatal error "Cannot redeclare class Batcache_Manager" on sites that still had the legacy hyphen-named mu-plugins/pcm-batcache-manager.php written by March-July 2026 builds alongside the current pcm_batcache_manager.php.
* pcm_batcache_manager.php: wrapped the Batcache_Manager class and its instantiation in a class_exists() guard so a duplicate copy of the mu-plugin can never cause a fatal.
* flush_batcache_for_woo_individual_page.php: removes the legacy mu-plugins/pcm-batcache-manager.php on both enable and disable. Also fixed the fresh-enable check, which could never be true after a successful copy, so the cache flush and activation notice now fire on first enable as intended.
* remove_old_mu_plugins.php: legacy cleanup now also removes hyphen-era mu-plugin files (pcm-batcache-manager.php and the pcm-cache-wpp-cookies-pages, pcm-exclude-pages-from-batcache, pcm-exclude-query-string-gclid and pcm-extend-batcache variants in the pressable-cache-management folder) and the pre-2023 top-level pcm_exclude_pages_from_batcache.php.
* Fixed unreadable hover state in the admin bar Cache Management menu: hovered items showed white text on the mint background (1.8:1 contrast); hover text is now navy #040024 (11.3:1 contrast).
* Admin bar CSS is now scoped to the plugin's own menu items and prefixed with #wpadminbar, with !important on its colours, so styling from other plugins and admin colour schemes can no longer change how the menu looks, and the plugin no longer restyles other plugins' admin bar items (two global rules were removed).
* The toolbar stylesheet now declares core's admin-bar.css as a dependency so it always loads after it, is enqueued on admin_enqueue_scripts/wp_enqueue_scripts only when the admin bar is showing, and uses filemtime() versioning instead of time() so browsers can cache it.
* Security: added nonce verification (check_ajax_referer) to the admin bar Flush Object Cache, Purge Edge Cache and Flush Object & Edge Cache AJAX handlers, which previously checked only user capability and were open to CSRF.
* Security: added nonce verification to the Defensive Mode status AJAX endpoint.
* Security: removed the unused admin/custom-functions/cache-purge-admin-bar.php, whose pressable_cache_purge AJAX handler had neither a nonce nor a capability check.

= 6.1.3 =
* exclude_pages_from_batcache_mu_plugin.php: wrapped cancel_the_cache() and disable_edge_cache() in function_exists() guards, matching the pattern already used in the gclid mu-plugin template. Behavior is unchanged; the init hook still fires once.
* pressable_cache_management_mu_plugin_index.php: switched all require statements to require_once, so the loader safely skips any file another mechanism has already included.
* exclude_pages_from_batcache.php: the mu-plugins index and the exclude-pages mu-plugin are now overwritten whenever their content differs from the bundled source (md5 comparison, same approach as the WooCommerce Batcache manager sync), instead of only being written when absent. Previously, live sites kept their original copies indefinitely and never received fixes to these files.

= 6.1.2 =
* Refactored cache flushing logic to improve clarity and maintainability

= 6.1.1 =
* Added NULL-GUARD FIX for batcache manager plugin
* Bump wp-coding-standards/wpcs from 3.2.0 to 3.4.1 to handle vulnerability warning

= 6.1.0 =
* Added new features to enhance Edge Cache

= 6.0.0 =
* Added defensive mode feature for Edge Cache

= 5.9.9 =
* Updated language string to support admin menu
* Add features to track max age
* Updated transcient for status checker
* Updated settings
* Updated validation method for exclude cache

= 5.8.7 =
* Fix botton bug
* Update checkbox button color to red when disabled
* Bug fix
* Bypass Edge Cache header when Batcache checker checks for cache status
* Fix grid and margins

= 5.5.5 =
* Update Edge Cache page UI
* Update button color
* Updated tool tip for Batcache status checker

= 5.3.2 =
* Update the grid to also fill the full width of the centred container

= 5.3.1 =
* Updated plugin updater

= 5.3.0 =
**UI & Design**
* Redesigned settings page with card-based layout, toggle switches, and Inter font typography
* Added "Cache Management by" header above Pressable logo in branded header
* Made footer ♥ icon clickable in both branding states, linking to branding settings page
* Timestamps (Last flushed at:) are now always visible showing — when never flushed
* Timestamps display on a single straight line using flexbox layout (no line breaks)
* Bold text inside timestamps (e.g. — cache flushed due to Page edit: My Post) now renders correctly as bold rather than showing raw <b> tags
* Darkened timestamp and label text for improved readability
* Added interactive chip UI for cache exclusion URL input
* Added branded admin bar modals replacing browser alert() popups for Edge Cache enable/disable and flush actions

**Batcache Status Badge**
* Fixed Batcache status check to read the correct x-nananana header instead of scanning the response body for the word "batcache"
* Fixed probe request to send no session cookies and mimic a real browser request, preventing Batcache from skipping the response
* Removed Cache-Control: no-cache from the probe request which was causing the Pressable edge CDN to bypass cache entirely
* Added 90-second transient cache on the status result to avoid a live HTTP request on every admin page load
* Added ↺ refresh button on the badge that re-checks live status via AJAX without requiring a full page reload
* Added tooltip ? icon next to the badge explaining: when a page gets two visits within 2 minutes it is cached for 5 minutes, and that cookies may interfere with cache status updates
* Status transient is cleared immediately after any manual cache flush so the badge reflects the new state on next load

**Cache Flush Improvements**
* Flush Cache on Plugin/Theme Update now captures and displays the plugin or theme name in the timestamp (e.g. — Yoast SEO plugin was updated)
* Flush Cache on Post/Page Edit now captures and displays the post title and post type in the timestamp
* Page URL: field now correctly shows the human-readable URL when flushing from the page/post column link (previously showed the md5 hash Batcache uses internally)
* Page URL: field now correctly saves when flushing from the page preview toolbar
* Renamed Object Cache page button Flush Cache → Flush All Cache

**GitHub Auto-Updates**
* Integrated YahnisElsts/plugin-update-checker v5.6 library (bundled directly, no Composer required)
* Plugin now checks github.com/pressable/pressable-cache-management for new releases automatically
* WordPress sites show the standard update notice when a new GitHub Release is published
* Added readme.txt used to populate the "View Details" popup in WordPress admin when an update is available
* Added SETUP.md with full instructions for releasing future updates via GitHub tags
* Removed unused library files: DebugBar/ and OAuthSignature.php to reduce plugin size

**Translations**
* Added French (fr_FR) translation — 65 strings
* Added Dutch (nl_NL) translation — 65 strings
* Added Simplified Chinese (zh_CN) translation — 65 strings
* Added Hindi (hi_IN) translation — 65 strings
* Updated Spanish (es_ES) translation to 65 strings
* Fixed load_plugin_textdomain() to use the correct relative path so all locales load when site language is changed
* Wrapped all previously untranslated UI strings including card titles, toggle labels, descriptions, exclude pages hint text, and branding page strings
* All tooltip and badge strings included in all 5 languages

**Uninstall Cleanup**
* Rewrote uninstall.php to delete all option keys the plugin has ever written, organised by category: main settings, all timestamps, Edge Cache state, Batcache flags, exclusion settings, WooCommerce notices, legacy CDN options from older versions
* Added $wpdb->query() to sweep out PUC transients (_transient_puc_*pressable-cache-management*) on uninstall

= 5.2.3 =
* Redesigned UI with card layout and toggle switches
* Added GitHub auto-update support via plugin-update-checker
* Added translations: Spanish, French, Dutch, Chinese (Simplified), Hindi
* Fixed double "Cache settings updated" notice
* Improved timestamp visibility and formatting
* Branded admin bar modals replacing browser alert()
* Flush Cache on Post/Page Edit now captures and displays post title
* Plugin/Theme Update flush now captures and displays the updated item name

= 5.2.2 =
* Bug fixes and stability improvements

= 5.2.1 =
* Initial redesign
