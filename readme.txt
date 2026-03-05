=== Pressable Cache Management ===
Contributors: pressable
Tags: cache, batcache, object cache, edge cache, pressable
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 5.2.3
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
