# Simple Image Widget

Contributors: cedaro, bradyvercher  
Tags: image widget, widget, media, media manager, sidebar, image, photo, picture  
Requires at least: 4.6  
Tested up to: 4.9  
Stable tag: 4.4.2  
License: GPL-2.0+  
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A simple widget that makes it a breeze to add images to your sidebars.

## Description

Simple Image Widget is what the name implies -- the easiest way to add images to your sidebars. Display advertisements, calls-to-action, or even build a slider based on image widgets.

Despite its simplicity, Simple Image Widget is built with extensibility in mind, making it super easy to spin off new image-based widgets, or customize the widget ouput using the available template hierarchy.

### Additional Resources

* [Write a review](https://wordpress.org/support/view/plugin-reviews/simple-image-widget#postform)
* [Contribute on GitHub](https://github.com/cedaro/simple-image-widget)
* [Follow @cedaroco](https://twitter.com/cedaroco)
* [Visit Cedaro](https://www.cedaro.com/?utm_source=wordpress.org&utm_medium=link&utm_content=simple-image-widget-readme&utm_campaign=plugins)


## Installation

Install just like most other plugins. [Check out the codex](http://codex.wordpress.org/Managing_Plugins#Installing_Plugins) if you have any questions.


## Frequently Asked Questions

#### Is there a way to filter the widget output?

Absolutely. Changing the output can be done a few different ways, but the most common alternatives involve using the "`simple_image_widget_output`" filter or overriding the template in your theme.

To use the template method, copy "`widget.php`" from the "`/templates`" directory in the plugin to a "`/simple-image-widget`" directory in your theme. Then update as you wish. It's also possible to create a custom template specific to each sidebar in your theme using the following default template hierarchy:

* `{theme}/simple-image-widget/{sidebar_id}_widget.php`
* `{theme}/simple-image-widget/widget.php`
* `{plugin}/templates/widget.php`

_Always use a [child theme](https://codex.wordpress.org/Child_Themes) to make changes if you acquired your theme from a third-party and you expect it to be updated. Otherwise, you run the risk of losing your customizations._

#### How do I add alt text to images in the widget?

When selecting an image in the media modal (the popup to select images), the right sidebar will be titled "Attachment Details" and contains a field for entering alt text. After entering your alt text, click the "Update Image" button to use the selected image in your widget. Most browsers don't show the alt text, so you'll need to view the HTML source to make sure it exists.

#### How do I center the widget?

The widget can be centered using CSS. Custom CSS should be added a child theme or using a plugin like [Simple Custom CSS](https://wordpress.org/plugins/simple-custom-css/) or [Jetpack](https://wordpress.org/plugins/jetpack/). The following snippet will center the contents of the widget:

`.widget_simpleimage {
     text-align: center;
}`

#### Can I remove the width and height attributes?

The widget uses the core function `wp_get_attachment_image()` to display the image and it would be more trouble than it's worth to remove those attributes. Some basic CSS will typically allow you to make the image responsive if necessary:

`.widget_simpleimage img {
	height: auto;
	max-width: 100%;
}`


## Screenshots

1. A new image widget.
2. The widget after selecting an image.


## Changelog

### 4.4.2 - October 12, 2017
* Changed the widget name to "Image (Simple)" to differentiate it from the core image widget introduced in WordPress 4.8.

### 4.4.1 - September 6, 2016
* Added missing text domains.

### 4.4.0 - April 22, 2016
* Enabled selective refresh in the Customizer.
* Removed PO and MO files in favor of WordPress.org Language Packs.
* Prevented errors if the main plugin file is accessed directly.

### 4.3.0
* Transferred to [Cedaro](http://www.cedaro.com/).

### 4.2.2
* Show media extensions in the post finder modal.
* Added Dutch translation.

### 4.2.1
* Fixed a PHP 5.2 incompatibility that prevented the correct image from showing on the front-end.
* Fixed a debug notice when searching for attachments in the new find posts modal.

### 4.2.0
* Added functionality to search for posts to link images to.
* Added Japanese translation.
* Changed the method for generating cache keys. Should provide better support for the_widget() and similar methods.
* Deprecated the method for flushing a single widget instance from the cache.

### 4.1.2
* Added Serbo-Croation translation.

### 4.1.1
* Added Finnish translation.
* Prevent a notice about non-existent title when adding a widget in the Customizer in debug mode.

### 4.1.0
* Added the ability to hide widget fields.
* Added a field to insert HTML classes on the text link. Hidden by default.
* Removed "the_content" filter from widget text to prevent other plugins from appending content.
* Renamed /scripts to /js and /styles to /css.
* Improved handling of fields that have been removed in child widgets.

### 4.0.2
* Fixed the reference to the widget's parent class to prevent an error.

### 4.0.1
* Allow more HTML tags in the text field.
* Updated customizer support and prevent cache poisoning.
* Added French translation.

### 4.0.0
* New template system to make it easier to override the output.
* Restructured to make it more intuitive for developers to extend the widget.
* Moved legacy support into a separate class that hooks into the widget.
* Works with the Widget Customizer added in WordPress 3.9.
* Improved compatibility with plugins like Page Builder by SiteOrigin.

### 3.0.4
* Fixed a slash preventing custom translations from loading.
* Dropped the text domain from custom translation filenames.
* Loading the text domain earlier so the widget title and description can be filtered.
* Minor code formatting updates.

### 3.0.3
* Fixed PHP class name formatting.
* Added 'link_open' and 'link_close' args to the $instance when rendering the widget display.
* Added a 'simple-image' CSS class to the image wrapper.

### 3.0.2
* Implemented feature for opening links in a new tab/window.
* Fixed a bug preventing links in legacy widgets to not work.

### 3.0.1
* Removed the main plugin file for the previous version.

### 3.0
* Complete rewrite with new media manager support.
