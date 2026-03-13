# **Pressable Cache Management**

**Plugin Name:** Pressable Cache Management
**Description:** This plugin makes management of cache easy from the WordPress admin.
**Author:** Pressable Customer Support Team

---

## **Description**

Pressable Cache Management makes managing Batcache and Pressable Edge Cache settings from the WordPress admin easier instead of having to manage them from the Pressable Control Panel.

This plugin does not replace Batcache or the Pressable Control Panel but helps you manage their settings conveniently from the WordPress dashboard.

* **Contributors:** otarhe, wpjess, paulhtrott, georgestephanis
* **Tags:**
* **Requires at least:** WordPress 6.0
* **Tested up to:** WordPress 6.1

> The **Stable Tag** should indicate the Subversion tag of the latest stable version, or “trunk” if you use `/trunk/` for the stable release.

> Note that the `readme.txt` of the stable tag is considered the defining version for the plugin. If `/trunk/readme.txt` states that the stable tag is `4.3`, then `/tags/4.3/readme.txt` is what WordPress will use to display plugin information.

> Only the stable tag pointer is read from the trunk `readme.txt`. This allows developers to update the trunk documentation for in-development versions without exposing those details publicly until a new stable tag is released.

> If no stable tag is provided, WordPress assumes trunk is stable. However, specifying “trunk” explicitly is recommended to avoid confusion.

---

## **Installation**

1. Upload the plugin files to the `/wp-content/plugins/pressable-cache-management` directory, or install the plugin through the WordPress Plugins screen directly.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Use the **Settings → Pressable Cache Management** screen to configure the plugin.

---

## **Frequently Asked Questions**

**A question that someone might have**
An answer to that question.

**How can I install the plugin**
The plugin can be downloaded from the GitHub repository and installed manually via the WordPress plugin upload feature.

---

## **Screenshots**

1. This screenshot description corresponds to `screenshot-1.(png|jpg|jpeg|gif)`. The screenshot is taken from the `/assets` directory or the directory containing the stable readme file (`tags` or `trunk`). Screenshots in the `/assets` directory take precedence. For example, `/assets/screenshot-1.png` overrides `/tags/4.3/screenshot-1.png`.
2. This is the second screenshot.

---

## **Changelog**

#### **Version 6.0.0 (Mar 12, 2026)**
* Added defensive mode feature for Edge Cache

#### **Version 5.9.9 (Mar 08, 2026)**

* Updated language string to support admin menu
* Add features to track max age
* Updated transcient for status checker
* Updated settings
* Updated validation method for exclude cache

#### **Version 5.8.7 (Mar 07, 2026)**

* Fix botton bug
* Update checkbox button color to red when disabled
* Bug fix
* Bypass Edge Cache header when Batcache checker checks for cache status
* Fix grid and margins

#### **Version 5.5.5 (Mar 06, 2026)**

* Update Edge Cache page UI
* Update button color
* Updated tooltip for Batcache status checker

#### **Version 5.3.2 (Mar 05, 2026)**

* Updated the grid to fill the full width of the centered container

#### **Version 5.3.1 (Mar 05, 2026)**

* Updated plugin updater

#### **Version 5.3.0 (Mar 05, 2026)**

**UI & Design**

* Redesigned settings page with card-based layout, toggle switches, and Inter font typography
* Added “Cache Management by” header above Pressable logo in branded header
* Made footer ♥ icon clickable in both branding states, linking to the branding settings page
* Timestamps (Last flushed at:) are now always visible showing — when never flushed
* Timestamps display on a single straight line using flexbox layout
* Bold text inside timestamps now renders correctly rather than displaying raw `<b>` tags
* Darkened timestamp and label text for improved readability
* Added interactive chip UI for cache exclusion URL input
* Added branded admin bar modals replacing browser `alert()` popups

**Batcache Status Badge**

* Fixed Batcache status check to read the correct `x-nananana` header instead of scanning the response body
* Fixed probe request to send no session cookies and mimic a real browser request
* Removed `Cache-Control: no-cache` from the probe request which caused CDN bypass
* Added a 90-second transient cache on the status result
* Added ↺ refresh button to re-check status via AJAX
* Added tooltip explaining Batcache behavior
* Status transient clears after a manual cache flush

**Cache Flush Improvements**

* Flush on plugin/theme update now records the plugin or theme name
* Flush on post/page edit records the post title and post type
* Page URL field now shows the human-readable URL instead of the Batcache MD5 hash
* Page URL now saves correctly when flushing from preview toolbar
* Renamed Object Cache page button **Flush Cache → Flush All Cache**

**GitHub Auto-Updates**

* Integrated `YahnisElsts/plugin-update-checker v5.6`
* Plugin checks the GitHub repository for new releases automatically
* WordPress shows update notices when a GitHub release is published
* Added `readme.txt` for the update details popup
* Added `SETUP.md` with instructions for future GitHub releases
* Removed unused library files to reduce plugin size

**Translations**

* Added French (fr_FR) translation
* Added Dutch (nl_NL) translation
* Added Simplified Chinese (zh_CN) translation
* Added Hindi (hi_IN) translation
* Updated Spanish (es_ES) translation
* Fixed `load_plugin_textdomain()` path so all locales load correctly
* Wrapped all UI strings for translation
* All tooltip and badge strings included in all languages

**Uninstall Cleanup**

* Rewrote `uninstall.php` to remove all option keys created by the plugin
* Added `$wpdb->query()` cleanup for plugin-update-checker transients

---

#### **Version 5.2.2 (Oct 15, 2025)**

* Fixed admin bar option not updating quickly after Edge Cache is enabled or disabled

#### **Version 5.2.1 (Oct 7, 2025)**

* Added option to purge Edge Cache for a single URL
* Removed API connection features for Edge Cache
* Added ability to control Edge Cache without API connection
* Added dropdown to flush Object and Edge Cache together
* Added auto-purge for WooCommerce products updated via API
* Removed all support for the CDN

#### **Version 5.1.0 (Jul 30, 2025)**

* Updated permissions: Administrators, Editors, and users with `manage_woocommerce` can see the **Flush Cache** option

= Version 5.0.0 (May 30, 2025) =
* Removed CDN tab as CDN feature has been phased out from Pressable

= Version 4.9.9 (Mar 22, 2025) =
* Added functionality to exclude specific pages from Batcache caching, ensuring they are not cached by the Edge Cache
* Updated description for the option exlude page from Batcache to include Edge Cache 

= Version 4.8.9 (Feb 22, 2025) =
* Fix bug to resolve issue with cache terminating when a single page is excluded from cache. 

= Version 4.7.9 (Jan 28, 2025) =

* Fix bug that breaks site when excluding single file from CDN

= Version 4.6.9 (Nov 15, 2024) =

* Added features to exclude home page from caching

= Version 4.5.9 (Oct 31, 2024) =

* Added a check if the "Name" key exists in $plugin_info before trying to access it to prevent  "Undefined array key 'Name'"

= Version 4.4.9 (Oct 3, 2024) =

* Fix: ensure HTML/CSS is not output on every page load including AJAX requests 

= Version 4.3.9 (July 24, 2024) =

* Updated Pressable branding

= Version 4.2.9 (July 22, 2024) =

* Fixed bugs

= Version 4.2.8 (July 22, 2024) =

* Sync the plugin CDN and Edge Cache state with MyPressable Control Panel when the plugin is authenticated
* Disable CDN state by defualt on plugin install
* Hide CDN tab if /cdn api endpoint is not found

= Version 4.2.7 (June 27, 2024) =
* Fixed bug with <p> tag added to robots.txt code snippet when extend cache option is enabled for some sites
* Updated from v4.2.6 - v4.2.7 

= Version 4.2.5 (March 18, 2024) =
* Fixed the warning error bug with DOMDocument – PHP Warning: DOMDocument::loadHTML():

= Version 4.2.2 (December 1, 2023) =

* Fixed footer conflict with Yoursite plugin which causes critical error

= Version 4.2.1 (September 14, 2023) =

* Add notice to disable CDN if Edge Cache is enabled
* Fixed bug with edge Cache turn on/off not syncing properly with MyPressable Control Panel

= Version 4.2.0 (June 22, 2023) =

 * Changed the location for mu-plugins used to flush cache when Woo product is updated via API

= Version 4.1.0 (June 22, 2023) =

 * Fixed issue double function deceleration 

= Version 4.0.0 (June 21, 2023) =

 * Removed edge cache purge time stamp from database

= Version 3.10.10 (June 20, 2023) =

 * Features to flush WooCommerce product page automatically when product are updated via Woo API
 * Ability for Shop managers to flush cache via Admin top bar
 * Ability to extend cache for URL's inside data-srcset attributes
 * Features to manage Edge Cache option
 * Resolve conflict that causes Imagify plugin generated .webp images from displaying
 * Ability to auto turn on Edge Cache if it is disabled from MPCP when Edge cache is purged

= Version 3.9.10  (June 8, 2023) =

* Fixed bug of purge CDN button which is displaying when CDN is disabled
* Hide visible hyperlink on footer page to hide branding
* Fixed  issue with </link> tag not appending to RSS feed page
* Fixed bug for broken images inside <picture> tag that uses scr and srcset attribute when Imagify .webp is configures on a site

= Version 3.8.10  (May 12, 2023) =

* Added features and notification to check if Cloudflare is interfering with Batcache

= Version 3.7.10  (May 2, 2023) =

* Code cleanup and changelog update

= Version 3.6.10  (Apr 20, 2023) =

* Excluded gclid option from the UI
* Excluded wpp_ cookies option from the UI

= Version 3.5.10  (Mar 23, 2023) =

* Updated new option

= Version 3.4.10  (Mar 23, 2023) =

*  Hide option from UI which does not function correctly yet

= Version 3.3.10  (Mar 20, 2023) =

* Updated batcache code 

= Version 3.3.9 (Feb 26, 2023) =

* Updated language functionality 

= Version 3.3.8 (Feb 23, 2023) =

* Added support to move mu-plugin from PCM to a private folder 

= Version 3.3.7 (Feb 20, 2023) =

* Bug fixes and new features added 

= Version 3.3.6 (Feb 07, 2023) =

* Updated footer icon

= Version 3.3.5 (Jan 07, 2023) =

* Updated extending cache option to execute via mu-plugin
* Added option to flush cache when comment is deleted
* Added notification to know if plugin or theme was updated 
* Disable plugin automatically when used on another platform
* Add option to flush website cache automatically when page or post is deleted after publishing this works for post types as well
* Added option to exclude pages from Batcache

= Version 3.3.4 (Aug 12, 2022) =

* Fixed function conflict on Flush cache on theme plugin update

= Version 3.3.4 (Aug 12, 2022) =

*  Fixed Zend OPcache API is restricted by "restrict_api" configuration directive warning on extended batcache 
*  Fixed admin notice not displaying when extend batcache is enabled and disabled

= Version 3.3.4 (Aug 4, 2022) =

Fixed bug for site id not entering on multi-network site

= Version 3.3.4 (Jul 10, 2022) =

* Fixed TTFB performance issue that slows down WordPress admin
* Renamed authentication button to save option
* Rename authentication button tab button to save option
* Hide save button if connection is successful for added security
* Cached the API key using transient for fast performance 
* Added new admin notice warning to alert user if CDN is flushed twice within a minute
* Added admin notice to alert user to connect to the api if API credentials is not saved
* Updated toggle option to enable/disable CDN to button
* Added settings option from the plugin area
* Added button option to connect and disconnect API
* Added option to clear API credentials when API is disconnected
* Added nonce to button for added security
* Fixed bug that shows CDN option when plugin is not yet connected to the API
* Added error message to alert user if the plugin is installed on site hosted on another platform

= Version 3.2.3 (Jul 03, 2022) =

Fixed CSS conflict issue with some third-party plugin

= Version 3.1.4 (May 21, 2022) =

* Added warning notice to CDN cache extender when enabled to notify user to disable the option if it causes any issue(The CDN extender features is buggy and know 

= Version 3.0.3 (May 21, 2022) =

* Fixed issue with api_connection which adds extra time to site TTFB

= Version 3.0.2 (April 28, 2022) =

* Fixed ?extend_cdn appended to the word style.css which is added to blog post/page

= Version 3.0.2 (April 22, 2022) =

* Applied patch to prevent HTML injection from all CDN exemption features
cdn_exclude_css, cdn_exclude_js_json, cdn_exclude_jpg_png_webp, cdn_exclude_specificfile.

= Version 3.0.1 (April 17, 2022) =

* Exclude Google Tag Manager gtm.js from Pressable CDN to fix Google tracking issue bug


