<?php
// This file should be called zenario_siteconfig.php and located in Zenario's home directory.

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
//Database port number:
define('DBPORT', '[[DBPORT]]');
//Prefix for all table names (to keep them distinct from other apps):
define('DB_PREFIX', '[[DB_PREFIX]]');
//Use MySQL in "strict" mode:
define('DEBUG_USE_STRICT_MODE', true);


  /////////////////////////////////////////
 // Restore policy for database backups //
/////////////////////////////////////////

define('RESTORE_POLICY', 'always');
// Always allow database backups to be restored using Organizer.

//define('RESTORE_POLICY', 'never');
// Disable the ability to restore database backups using Organizer.

//define('RESTORE_POLICY', '2012-12-21');
// Allow database backups to be restored for one day. (Set this to the current date to use this option.)


  //////////////////////////////////////////////////////////
 // MySQL database settings for multi-site adminstrators //
//////////////////////////////////////////////////////////

// These optional parameters allow you to specify a different installation
// of Zenario and log in to this installation with those administrator accounts:
//define('DBHOST_GLOBAL', '[[DBHOST_GLOBAL]]');
//define('DBNAME_GLOBAL', '[[DBNAME_GLOBAL]]');
//define('DBUSER_GLOBAL', '[[DBUSER_GLOBAL]]');
//define('DBPASS_GLOBAL', '[[DBPASS_GLOBAL]]');
//define('DBPORT_GLOBAL', '[[DBPORT_GLOBAL]]');
//define('DB_PREFIX_GLOBAL', '[[DB_PREFIX_GLOBAL]]');


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

// If you are running Zenario in a subdirectory please enter this here.
// If specifed, it MUST start with a / and must end in a /
// If you are not running in a subdirectory please just enter a /
define('SUBDIRECTORY', '[[SUBDIRECTORY]]');

// The filename of the index file in the root directory.
// In most cases you don't need to change this.
define('DIRECTORY_INDEX_FILENAME', 'index.php');


  //////////////////////////
 // Cookies and Sessions //
//////////////////////////

// If you are using multiple sub-domains for your site, and this value is defined,
// then cookies set in the browser, an administrator's login, and an extranet user's
// login will persist across the domain and any sub-domains.
//
// For example, a setting of 'example.com' will persist cookies across all sites at
// *.example.com where '*' means any sub-domain, as well as example.com itself.
//
// You should use this if you use a sub-domains to denote languages, such as
// example.com, en.example.com, de.example.com and fr.example.com.
// In that case, enter 'example.com' below.
//
// Leave this blank if you're not using sub-domains.
define('COOKIE_DOMAIN', '');

// The length of time (in seconds) that the cookie consent, "remember me" and
// "Log me in automatically" options last.
// The default value if not set is 100 days.
define('COOKIE_TIMEOUT', 8640000);


// The length of time (in seconds) to persist the login of administrators and extranet users.
//
// If not active on the site for this amount of time, they will be logged out.
// If session.cookie_lifetime and/or session.gc_maxlifetime settings in php.ini file are
// shorter, they will take precedence.
//
// If set to 0 then they will be logged out when quitting/restarting, but will otherwise
// remain logged in until session.cookie_lifetime and/or session.gc_maxlifetime.
// 
// We recommend setting this to 1800 (30 minutes).
define('SESSION_TIMEOUT', 1800);


  /////////////////////
 // Error reporting //
/////////////////////

//define('ERROR_REPORTING_LEVEL', (E_ALL | E_NOTICE | E_STRICT));
// This shows every type of error.
// We recommend using this level of reporting on development or staging sites that are not live.

define('ERROR_REPORTING_LEVEL', (E_ALL & ~E_NOTICE & ~E_STRICT));
// This shows all errors except notices and strict errors.
// We recommend using this level of reporting on live/production sites.
