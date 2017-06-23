Installing Zenario CMS
======================

System Requirements
-------------------

To run Zenario you will need a web server/hosted account with the following:

*   Apache Server version 2
*   PHP version 7.0 or later
*   MySQL version 5.5.3 or later (or an equivalent MariaDB version)
*   An empty MySQL (or MariaDB) database to install to
*   The GD, libCurl, libJPEG and libPNG libraries, and multibyte support in PHP
*   Apache mod_rewrite support for .htaccess files (optional but highly recommended)

Zenario sites will work with all modern, standards-compliant web browsers, however this
is dependent on how a designer writes CSS and frameworks for the site. If compatibility
with yet older browsers is required, this should be possible with careful design.

In administration mode, Zenario will run on:

*   Windows with Chrome (stable channel)
*   Windows with Firefox (release channel)
*   Windows with Microsoft Edge (latest version)
*   Windows with Microsoft Internet Explorer 11
*   Mac OSX with Chrome (stable channel)
*   Mac OSX with Firefox (release update channel)
*   Mac OSX with Safari (latest version)

We test on all of the above platforms. Zenario may run on other operating systems and
browsers, but this is not tested.


Place the files on your server
------------------------------

You should download the .zip file, unzip it on your local machine, and then use a FTP
program to upload the files to your server.

Alternatively, if you have ssh access it's faster to download the .tar.gz file, upload
it to your server and then unpack it by running:

    tar xfz community-7.0.2a.tar.gz

If you want to run Zenario in the root of a domain (e.g. http://example.com/), you
should place the files into your server's web directory (sometimes called the public
HTML directory or the document root) .

If you want to run Zenario from a subdirectory (e.g. http://example.com/cms/), you
should create a subdirectory with the correct name inside your server's web directory
and place the files in there.


Create directories and set permissions
--------------------------------------

You will need to create two directories:

*   A backup/ directory
*   A docstore/ directory

These should not be publicly accessible, so you should create them outside of your web
directory. Zenario will need to write files and folders to these directories, so you
need to make them writable, e.g. on a UNIX/Linux server:

    chmod 777 backup/
    chmod 777 docstore/

There are four directories in the CMS that you need to make writable:

*   The cache/ directory
*   The private/ directory
*   The public/ directory
*   The zenario_custom/templates/grid_templates/ directory

E.g. on a UNIX/Linux server:

    chmod 777 cache/
    chmod 777 private/
    chmod 777 public/
    chmod 777 zenario_custom/templates/grid_templates/
    chmod 666 zenario_custom/templates/grid_templates/*.css
    chmod 666 zenario_custom/templates/grid_templates/*.tpl.php

You can optionally make the zenario_siteconfig.php file writable for a smoother install
process.


Run the installer
-----------------

To run the installer you need to visit your site using a browser - e.g. by going to
http://example.com, or http://example.com/cms/ if you are running from a subdirectory.

The installer will then take you through the installation process, during which you will
need to enter:

*   A name, username and password to connect to a database
*   A name and an email address to create your first administrator account.
*   An initial language for your site (you can add more languages later).


Enable your site
----------------

No-one apart from you (and any other administrators you create) can see your site until
you enable it. To enable your site:

*   Publish your homepage and any other "special pages" in the system. ("Special pages" are
listed in Organizer under "Content items -> Special pages".)
*   Go to "Configuration -> Site settings" in Organizer and enable your site.