=== Simple Image Widget ===
Contributors: blazersix, bradyvercher
Tags: image widget, widget, media, media manager, sidebar, image, photo, picture
Requires at least: 3.3
Tested up to: 3.6.1
Stable tag: trunk
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A simple image widget that makes it a breeze to add images to your sidebars.

== Description ==

Simple Image Widget provides the absolute easiest method to quicky add an image to a sidebar or any other widget area. Despite its simplicty, it can be extended by developers via the various hooks to create additional image-based widgets.

Blazer Six took over development and maintenance of Simple Image Widget with version 3.0, rewriting it from the ground up to take advantage of the media improvements in WordPress 3.5. Read about the original thought behind creating this widget and ways it can be extended in [this blog post](http://www.blazersix.com/blog/wordpress-image-widget/).

= Additional Resources =

* [Write a review](http://wordpress.org/support/view/plugin-reviews/simple-image-widget#postform)
* [Have a question?](http://wordpress.org/support/plugin/simple-image-widget)
* [Contribute on GitHub](https://github.com/blazersix/simple-image-widget)
* [Follow @bradyvercher](https://twitter.com/bradyvercher)
* [Hire Blazer Six](http://www.blazersix.com/)

== Installation ==

Installation is just like installing most other plugins. [Check out the codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.

== Frequently Asked Questions ==

= How do I add alt text to images in the widget? =
When selecting an image in the media manager (not in the widget itself), the right section will be titled "Attachment Details" and contains a field for entering your alt text. After entering your alt text, click the "Update Image" button to use the selected image in your widget. You won't be able to see the alt text in most browsers without viewing the HTML source of the page.

== Screenshots ==

1. A new image widget.
2. The widget after selecting an image.

== Changelog ==

= 3.0.4 =
* Fixed a slash preventing custom translations from loading.
* Dropped the textdomain from custom translation filenames.
* Minor code formatting updates.

= 3.0.3 =
* Fixed PHP class name formatting.
* Added 'link_open' and 'link_close' args to the $instance when rendering the widget display.
* Added a 'simple-image' CSS class to the image wrapper.

= 3.0.2 =
* Implemented feature for opening links in a new tab/window.
* Fixed a bug preventing links in legacy widgets to not work.

= 3.0.1 =
* Removed the main plugin file for the previous version.

= 3.0 =
* Complete rewrite with new media manager support.