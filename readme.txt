=== Randomize ===
Contributors: se-schwarz
Tags: widget, plugin, sidebar, random, text, quotes
Requires at least: 2.8
Tested up to: 4.2
Stable tag: trunk

Store and display randomized/rotated text by category in sidebar widget or templates.

== Description ==

Randomize simply displays randomized text. You're able to deposit text passages and quotes in the administration back-end by categories. You can use the widget, a shortcode or template tag to show up randomized text on your site.

= Notice! =

I am not the author of this plugin. But the original author has discontinued the development, so that I decided to continue the free distribution under the new name “Randomize” (“Random Text” so far).
Because I don’t have the know-how to continue the development for this plugin I may need your help! So if you have skills, take a look on the To-Do list and write a mail to me.

= To-Do list =
* Make it multi language to auto-detect the right language. (Deposit language strings in MO&PO files. The strings are fixed in the php files so far.)
* Make it usable with images to display random pictures.

== Installation ==

1. Upload the zip to your WordPress installation and activate it.
2. Switch to "Appearance" -> "Widgets" for using the widget OR embed a shortcode/template tag.
3. Manage your text entries in "Settings" -> "Randomize".

Note: During installation, Randomize creates a new table to your WP database to store the entries by category. After setup you should see two sample entries.

== Screenshots ==

1. widget options
2. management page
3. output on the site

== Frequently Asked Questions ==

= Can I use shortcodes? =

Yes, you can use [randomize] or [randomize category="funny"] or even [randomize category="funny" random="1"].

= What about template tags? = 

You can use something like this, where 'CATEGORY' is the group you wish to select items from.
< ?php randomize('CATEGORY'); ?>

== Changelog ==

= v1.0 2014-06-03 =

* Initial release
