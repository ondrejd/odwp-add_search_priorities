=== Customize search results order ===
Contributors: ondrejd
Donate link: https://www.paypal.me/ondrejd
Tags: search,meta box
Requires at least: 4.8
Tested up to: 4.9.5
Requires PHP: 5.2.4
Stable tag: 1.2.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 
Plugin which customizes order of search results by additional priority value. It supports plain WordPress search functionality as well as plugin Relevanssi.
 
== Description ==
 
Plugin which customizes order of search results by additional priority value. It supports plain __WordPress__ search functionality as well as plugin [Relevanssi](https://wordpress.org/plugins/relevanssi/).

The main part of the plugin is new administration page (__Admin__ -> __Tools__ -> __Search priorities__) where you can set priority to single pages or posts. Items with higher priority will be shown on top of search results listing.

Screencast with usage of the plugin can be found in [this post](https://ondrejd.com/en/wordpress-changing-of-priority-of-search-results/) on my blog.
 
== Installation ==
 
This section describes how to install the plugin and get it working.
 
1. Upload `odwp-add_search_priorities.zip` to the `/wp-content/plugins/` directory
2. Unpack ZIP archive (so new `odwp-add_search_priorities` directory is created)
3. Activate the plugin through the 'Plugins' menu in WordPress
 
== Screenshots ==

1. Plugin's admin screen
2. Another screen of plugin's admin screen
 
== Changelog ==

= 1.2.1 =
* Some code fixes because of publishing on __WordPress Plugins Directory__

= 1.2 =
* Added `readme.txt`
* Some code fixes (`_e` replaced by `esc_html_e` etc.)

= 1.1 =
* Ajax added to plugin's admin page.
* Source codes translated to English.
* Added `cs_CZ` localization.
 
= 1.0 =
* The very first functional version.
* Added `odwp-priority` meta value to posts/pages.
* Added admin page for test searchs and updating priorities.
