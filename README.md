# **Pressable Cache Management**

**Plugin Name:** Pressable Cache Management
**Description:** This plugin makes management of cache easy from the WordPress admin.
**Author:** Pressable Customer Success Team

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

### **Pressable Cache Management Changelog**

#### **Version 5.8.8 (Mar 07, 2026)**

* Updated language string to support admin menu
* Add features to track max age
* Updated transcient for status checker

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

#### **Version 5.0.0 (May 30, 2025)**

* Removed CDN tab as the CDN feature was phased out from Pressable
