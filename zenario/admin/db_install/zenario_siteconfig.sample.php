<?php
// This file should be called zenario_siteconfig.php and located in your CMS directory.

  /////////////////////////////
 // MySQL database settings //
/////////////////////////////

//Database host (often localhost):
define('DBHOST', '[[DBHOST]]');
//Database name:
define('DBNAME', '[[DBNAME]]');
//MySQL database username:
define('DBUSER', '[[DBUSER]]');
//Database password:
define('DBPASS', '[[DBPASS]]');
//Prefix for all table names (to keep them distinct from other apps):
define('DB_NAME_PREFIX', '[[DB_NAME_PREFIX]]');
//Use MySQL in "strict" mode:
define('DEBUG_USE_STRICT_MODE', true);


  //////////////////////////////////////////////////////////
 // MySQL database settings for multi-site adminstrators //
//////////////////////////////////////////////////////////

// These optional parameters allow you to specify a different installation
// of the CMS and log in here with those administrator accounts:
//define('DBHOST_GLOBAL', '[[DBHOST_GLOBAL]]');
//define('DBNAME_GLOBAL', '[[DBNAME_GLOBAL]]');
//define('DBUSER_GLOBAL', '[[DBUSER_GLOBAL]]');
//define('DBPASS_GLOBAL', '[[DBPASS_GLOBAL]]');
//define('DB_NAME_PREFIX_GLOBAL', '[[DB_NAME_PREFIX_GLOBAL]]');


  ///////////////////////////
 // Support email address //
///////////////////////////

// Send system errors to the following email address:
define('EMAIL_ADDRESS_GLOBAL_SUPPORT', '[[EMAIL_ADDRESS_GLOBAL_SUPPORT]]');

// Send an email to the address above if there is a database error:
define('DEBUG_SEND_EMAIL', true);

// Note that other email-related settings can be found under
// "Configuration -> Site settings -> Email" in Organizer


  /////////////////////////////
 // Directory and file name //
/////////////////////////////

// If you are running the CMS in a subdirectory please enter this here.
// If specifed, it MUST start with a / and must end in a /
// If you are not running in a subdirectory please just enter a /
define('SUBDIRECTORY', '[[SUBDIRECTORY]]');

// The filename of the index file in the root directory.
// In most cases you don't need to change this.
define('DIRECTORY_INDEX_FILENAME', 'index.php');


  /////////////////////
 // Error reporting //
/////////////////////

//define('ERROR_REPORTING_LEVEL', (E_ALL | E_NOTICE | E_STRICT));
// This shows every type of error. we recommend that developers use this level of
// reporting on their development servers.

//define('ERROR_REPORTING_LEVEL', (E_ALL & ~E_NOTICE | E_STRICT));
// This shows all errors except notices. We recommend using this level of reporting on
// staging sites that are not live.

define('ERROR_REPORTING_LEVEL', (E_ALL & ~E_NOTICE & ~E_STRICT));
// This shows all errors except notices and strict errors. We recommend using this
// level of reporting on live/production sites.


  ////////////////////////////
 // Forwarded IP addresses //
////////////////////////////

// Normally, a visitor's IP Address is read from the $_SERVER['REMOTE_ADDR'] variable.
// If you are using a load balancer and/or a proxy, you must set this to true so that
// the CMS will read from the $_SERVER['HTTP_X_FORWARDED_FOR'] variable instead.
define('USE_FORWARDED_IP', false);
