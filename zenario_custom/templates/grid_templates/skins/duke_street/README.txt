Skins in Zenario
===================

The CSS files in this directory are combined to form a Skin.
(The CSS files in any subdirectories are also included.)



Order of inclusion
---------------------

The files are combined in the following order:

 * The `reset.css` file is always included first
 * The other CSS files in this directory are included in alphabetical order
 * Browser-specific files such as `style_ie.css` are always included last



Browser-specific files
-------------------------

The following browser-specific files are only included when a page is viewed by
the named browser or device:

 * `style_chrome.css`
 * `style_ff.css`
 * `style_ie.css`
 * `style_ie6.css`
 * `style_ie7.css`
 * `style_ie8.css`
 * `style_ie9.css`
 * `style_ios.css`
 * `style_ipad.css`
 * `style_iphone.css`
 * `style_opera.css`
 * `style_safari.css`
 * `style_webkit.css`



CSS File Wrappers
--------------------

By default, Zenario combines multiple CSS files together into one file to reduce the
number of downloads and make your website load faster.

Designers may want to turn this off for easier debugging. You can turn it off by going
to Configuration -> Site Settings -> Optimization in Organizer and changing the
"CSS File Wrappers" setting.



Printing
-----------

When a visitor prints a page, the only CSS file that will be included is the
`stylesheet_print.css` file.

The `stylesheet_print.css` file is not included when viewing the page normally.



Styles for editors in Floating Admin Boxes
---------------------------------------------

HTML editors in Floating Admin Boxes are unstyled by default. You can add styles by
adding them into the `tinymce.css` file.

The `tinymce.css` file is not included when viewing the page normally.



