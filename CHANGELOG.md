# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [5.2.2] - 2025-10-15
### Fixed
- Fix bug for admin bar option not updating quicky after Edge cache is enabled/disabled

## [5.2.1] - 2025-10-07
### Added
- Added option to purge Edge Cache for single URL
- Added option to control Edge Cache without using an API connection
- Added dropdown button to flush both Object and Edge Cache
- Added option to automatically purge Edge Cache when WooCommerce product are edited via the WooCommerce API
### Removed
- Removed API connection features for edge cache
- Removed all support for the CDN

## [5.1.0] - 2025-07-30
### Updated
- Updated permission Only users with the following: administrator, editor and manage_woocommerce Can: See the "Flush Cache" option.

## [5.0.0] - 2025-05-30
### Removed
- Removed CDN tab as CDN feature has been phased out from Pressable

## [4.9.9] - 2025-03-22
### Added
- Added functionality to exclude specific pages from Batcache caching, ensuring they are not cached by the Edge Cache
### Updated
- Updated description for the option exclude page from Batcache to include Edge Cache

## [4.8.9] - 2025-02-22
### Fixed
- Fix bug to resolve issue with cache terminating when a single page is excluded from cache

## [4.7.9] - 2025-01-28
### Fixed
- Fix bug that breaks site when excluding single file from CDN

## [4.6.9] - 2024-11-15
### Added
- Added features to exclude home page from caching

## [4.5.9] - 2024-10-31
### Added
- Added a check if the "Name" key exists in $plugin_info before trying to access it to prevent "Undefined array key 'Name'"

## [4.4.9] - 2024-10-03
### Fixed
- Fix: ensure HTML/CSS is not output on every page load including AJAX requests

## [4.3.9] - 2024-07-24
### Updated
- Updated Pressable branding

## [4.2.9] - 2024-07-22
### Fixed
- Fixed bugs

## [4.2.8] - 2024-07-22
### Changed
- Sync the plugin CDN and Edge Cache state with MyPressable Control Panel when the plugin is authenticated
- Disable CDN state by defualt on plugin install
- Hide CDN tab if /cdn api endpoint is not found

## [4.2.7] - 2024-06-27
### Fixed
- Fixed bug with <p> tag added to robots.txt code snippet when extend cache option is enabled for some sites
### Updated
- Updated from v4.2.6 - v4.2.7

## [4.2.5] - 2024-03-18
### Fixed
- Fixed the warning error bug with DOMDocument – PHP Warning: DOMDocument::loadHTML():

## [4.2.2] - 2023-12-01
### Fixed
- Fixed footer conflict with Yoursite plugin which causes critical error

## [4.2.1] - 2023-09-14
### Added
- Add notice to disable CDN if Edge Cache is enabled
### Fixed
- Fixed bug with edge Cache turn on/off not syncing properly with MyPressable Control Panel

## [4.2.0] - 2023-06-22
### Changed
- Changed the location for mu-plugins used to flush cache when Woo product is updated via API

## [4.1.0] - 2023-06-22
### Fixed
- Fixed issue double function deceleration

## [4.0.0] - 2023-06-21
### Removed
- Removed edge cache purge time stamp from database

## [3.10.10] - 2023-06-20
### Added
- Features to flush WooCommerce product page automatically when product are updated via Woo API
- Ability for Shop managers to flush cache via Admin top bar
- Ability to extend cache for URL's inside data-srcset attributes
- Features to manage Edge Cache option
- Ability to auto turn on Edge Cache if it is disabled from MPCP when Edge cache is purged
### Fixed
- Resolve conflict that causes Imagify plugin generated .webp images from displaying

## [3.9.10] - 2023-06-08
### Fixed
- Fixed bug of purge CDN button which is displaying when CDN is disabled
- Fixed issue with tag not appending to RSS feed page
- Fixed bug for broken images inside
### Changed
- Hide visible hyperlink on footer page to hide branding

## [3.8.10] - 2023-05-12
### Added
- Added features and notification to check if Cloudflare is interfering with Batcache

## [3.7.10] - 2023-05-02
### Changed
- Code cleanup and changelog update

## [3.6.10] - 2023-04-20
### Removed
- Excluded gclid option from the UI
- Excluded wpp_ cookies option from the UI

## [3.5.10] - 2023-03-23
### Updated
- Updated new option

## [3.4.10] - 2023-03-23
### Changed
- Hide option from UI which does not function correctly yet

## [3.3.10] - 2023-03-20
### Updated
- Updated batcache code

## [3.3.9] - 2023-02-26
### Updated
- Updated language functionality

## [3.3.8] - 2023-02-23
### Added
- Added support to move mu-plugin from PCM to a private folder

## [3.3.7] - 2023-02-20
### Fixed
- Bug fixes
### Added
- New features added

## [3.3.6] - 2023-02-07
### Updated
- Updated footer icon

## [3.3.5] - 2023-01-07
### Added
- Added option to flush cache when comment is deleted
- Added notification to know if plugin or theme was updated
- Add option to flush website cache automatically when page or post is deleted after publishing this works for post-types as well
- Added option to exclude pages from Batcache
### Updated
- Updated extending cache option to execute via mu-plugin
### Fixed
- Fixed function conflict on Flush cache on theme plugin update
### Changed
- Disable plugin automatically when used on another platform

## [3.3.4] - 2022-08-12
### Fixed
- Fixed Zend OPcache API is restricted by "restrict_api" configuration directive warning on extended batcache
- Fixed admin notice not displaying when extend batcache is enabled and disabled

## [3.3.4] - 2022-08-04
### Fixed
- Fixed bug for site id not entering on multi-network site

## [3.3.4] - 2022-07-10
### Fixed
- Fixed TTFB performance issue that slows down WordPress admin
- Fixed bug that shows CDN option when plugin is not yet connected to the API
### Added
- Added new admin notice warning to alert user if CDN is flushed twice within a minute
- Added admin notice to alert user to connect to the api if API credentials is not saved
- Added settings option from the plugin area
- Added button option to connect and disconnect API
- Added option to clear API credentials when API is disconnected
- Added nonce to button for added security
- Added error message to alert user if the plugin is installed on site hosted on another platform
### Changed
- Renamed authentication button to save option
- Rename authentication button tab button to save option
- Hide save button if connection is successful for added security
- Cached the API key using transient for fast performance
- Updated toggle option to enable/disable CDN to button

## [3.2.3] - 2022-07-03
### Fixed
- Fixed CSS conflict issue with some third-party plugin

## [3.1.4] - 2022-05-21
### Added
- Added warning notice to CDN cache extender when enabled to notify user to disable the option if it causes any issue(The CDN extender features is buggy and know

## [3.0.3] - 2022-05-21
### Fixed
- Fixed issue with api_connection which adds extra time to site TTFB

## [3.0.2] - 2022-04-28
### Fixed
- Fixed ?extend_cdn appended to the word style.css which is added to blog post/page

## [3.0.2] - 2022-04-22
### Fixed
- Applied patch to prevent HTML injection from all CDN exemption features cdn_exclude_css, cdn_exclude_js_json, cdn_exclude_jpg_png_webp, cdn_exclude_specificfile.

## [3.0.1] - 2022-04-17
### Fixed
- Exclude Google Tag Manager gtm.js from Pressable CDN to fix Google tracking issue bug
