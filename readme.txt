=== Plugin Name ===
Contributors: Calvin Freitas
Tags: Twitter, tweet, Elitwee, status, sidebar, MyTwitter
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 3.0.3

Elitwee MyTwitter for Wordpress allows users to display their recent tweets on their blog and update their status through the Options page.

== Description ==

Elitwee allows users to display their recent tweets on their Wordpress site and update their status through the Options page. Includes customization options including number of recent twitters to display, formatting options, and stylesheets.  It can be called as a function or used as a widget.

== Installation ==
Elitwee MyTwitter 3.0 requires PHP 5.2 or higher.  This is because it is written using Twitter's JSON API and older versions of PHP do not include JSON decoding by default.

Extract the contents of the archive. Upload the elitwee folder to your Wordpress plugins folder (e.g. http://example.com/wp-content/plugins/).  Set your preferences in the Settings panel for "Elitwee MyTwitter" (including username, password, cache location, and formatting options).

Establish the Cache Life to set the length of time for the Twitter feed to be cached before checking for updates.

Ensure the web server is able to write to the directory you selected for cache location in the Elitwee MyTwitter Options panel.

== Frequently Asked Questions ==

= How do I use this plugin? =

Configure the options for your Twitter profile using the Elitwee MyTwitter panel in the Settings pane of Wordpress.

= How do I display my Twitter updates? =

Enable Elitwee MyTwitter in the Wordpress Widgets administration screen.  Alternatively, you can include the mytwitter() function in your theme template anywhere you want to display your tweets.

= How do I style my tweets using CSS? =

Example CSS code is included in example.css.  To incorporate on your site, copy/edit the code to the stylesheet for your current wordpress theme.  For most themes, this can be done by going to Presentation -> Theme Editor and then select "Stylesheet" from the theme files list.

== Screenshots ==

1. The settings page for Elitwee MyTwitter.
2. An example of the widget configuration.
3. An example of what the widget looks like in the sidebar. This example uses the P2 theme.
