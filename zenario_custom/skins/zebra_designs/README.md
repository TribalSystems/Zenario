Skins in Zenario
================

The appearance of a Zenario site is based on a "layout", and on a "skin".

Layouts are defined via the Zenario Gridmaker interface (accessible using a browser when in admin mode, on the "Edit layout" tab of the admin toolbar). They are stored in the database.

Skins are stored on the file system, with their CSS files being in the `zenario_custom/[skin-name]/editable_css/` directory. They can be edited via the Skin editor in Zenario, or directly on the file system if the designer has FTP access. For the Zenario skin editor to work, the editable_css directory must have read, write and execute permissions by the web server, and the files inside it must have read and write permissions by the web server.

The `description.yaml` file defines the skin’s display name, the skin’s type ("usable" for a regular skin, or "component" if the extension of another skin) and styles formats.

Skins are composed of several CSS files in the editable_css directory, which are prefixed with a number so as to utilise the files in numerical order. This is typically:

* `0.reset.css` - this file is numerically first and is read first; use it to add a list of rules that "reset" all of the default browser styles
* `1.fonts.css` — use this to define fonts used in the skin
* `1.forms.css` — use this to define how Zenario's user forms are displayed
* `1.layout.css` —  use this to define common styles for the different layouts sections like header, body and footer
* `2...` - there will be a number of such CSS files prefixed with a "2", which relate to the plugin that generates the respective HTML framework
* `3.misc.css` — use this to define various other styles not catered for by the above
* `4.responsive.css` — use this to define how a site should appear on different client devices
* `print.css` — use this file to add aditional rules for printing

Skins may rely on images, and so these should be stored in the `zenario_custom/[skin-name]/images/` directory. There is no web interface to upload these images.

For optimal speed of serving pages, Zenario combines CSS files in the `editable_css/` directory into "bundles", so as to efficiently serve a small number of CSS files, rather than a large number of the small files. This feature can be controlled in the Site settings > Cache area of Organizer, in the site settings.

There are other files and directories in the `zenario_custom/[skin-name]/` directory:

* `colorbox/` directory — defines the appearance of colorbox popups (modal windows)
* `installer/` director — includes a thumbnail image representing the skin; used when Zenario is installed and the skin is offered as the default for installation
* `jquery_ui/` directory — defines the appearance of jQuery UI widgets

This "blank" skin is included to help you create a custom skin. We recommend you duplicate this entire directory, give it an appropriate name, and then edit the files and images as required.

After creating a new skin, to start using it, you should use your browser to go to a Layout (in Organizer or on the Admin toolbar), edit the settings of the layout, and select the new skin. 

