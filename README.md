# Pressable Cache Management

Plugin Name:  Pressable Cache Management
Description:  This plugin makes management of cache easy from WordPress admin.
Author:       Pressable Customer Success Team.

# Description

Pressable cache management plugin makes management of Batcache and Pressable CDN settings all from WordPress admin easier instead having to manage the settings from Pressable control panel. This plugin does not work in place of Batcache or Pressable control panel but it helps you manage it's settings easily all from WordPress dashboard. 


*   "Contributors" otarhe, wpjess, paulhtrott, georgestephanis 
*   "Tags" 
*   "Requires at least" WordPress 6.0
*   "Tested up to" WordPress 6.1
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

# Installation

1. Upload the plugin files to the `/wp-content/plugins/pressable-cache-management` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings->pressable-cache-management screen to configure the plugin


# Frequently Asked Questions

= A question that someone might have =

An answer to that question.

= How can I install the plugin =

The plugin can be downloaded via GitHub repo and installed via manually via WordPress plugin upload

# Screenshots

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

# Changelog

=== Pressable Cache Management Change log ===

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


=== Pressable Cache Management Change log ===


= Version 3.0.1 (April 17, 2022) =

* Exclude Google Tag Manager gtm.js from Pressable CDN to fix Google tracking issue bug
