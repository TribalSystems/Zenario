<?php 
/* This file should be called zenario_siteconfig.php and located in your CMS directory.
 */


/* MySQL Database settings
 */

/* Database host (often localhost) */
define('DBHOST', '[[DBHOST]]');
/* Database name */
define('DBNAME', '[[DBNAME]]');
/* MySQL Database username */
define('DBUSER', '[[DBUSER]]');
/* Database password */
define('DBPASS', '[[DBPASS]]');
/* Prefix for all table names to keep them distinct from other apps */
define('DB_NAME_PREFIX', '[[DB_NAME_PREFIX]]');


/* MySQL Database settings for Super Adminstrators
 * These optional parameters allow you to specify a different installation of the CMS
 * and log in here with those Administrator accounts
 */

//define('DBHOST_GLOBAL', '[[DBHOST_GLOBAL]]');
//define('DBNAME_GLOBAL', '[[DBNAME_GLOBAL]]');
//define('DBUSER_GLOBAL', '[[DBUSER_GLOBAL]]');
//define('DBPASS_GLOBAL', '[[DBPASS_GLOBAL]]');
//define('DB_NAME_PREFIX_GLOBAL', '[[DB_NAME_PREFIX_GLOBAL]]');


/* Email address for Support for this site.
 * If database activity fails, this address may be used for system error messages.
 * See Settings in the database (via Settings admin screen) for other emails
 */
define('EMAIL_ADDRESS_GLOBAL_SUPPORT', '[[EMAIL_ADDRESS_GLOBAL_SUPPORT]]');

/* Send an email to Support as specified above if there is a database error */
define('DEBUG_SEND_EMAIL', true);

/* Use MySQL in Strict mode */
define('DEBUG_USE_STRICT_MODE', true);


/* SUBDIRECTORY
 * This tells the CMS where it lives within your web folder.
 * Must be just / if no subdirectory used.
 * If specifed, it MUST start with / and must end in /
 */
define('SUBDIRECTORY', '[[SUBDIRECTORY]]');

/* DIRECTORY_INDEX_FILENAME
 * This should be set to one of two values:
 *	 * You should set it to "index.php" if you wish to show "index.php" in the URLs for
 *	   your site
 *	 * You should set it to "" (an empty string) if you do not wish to show "index.php"
 *	   in the URLs for your site, and have a DirectoryIndex set in Apache.
 */
define('DIRECTORY_INDEX_FILENAME', 'index.php');


/* ERROR_REPORTING_LEVEL
 * This determines what level of errors to report.
 *
 * (E_ALL | E_NOTICE | E_STRICT) shows every type of error.
 * We recommend that Module Developers use this level of reporting on their development
 * servers.
 *
 * (E_ALL & ~E_NOTICE | E_STRICT) shows all errors except notices.
 * We recommend using this level of reporting on staging sites that are not live.
 *
 * (E_ALL & ~E_NOTICE & ~E_STRICT) shows all errors except notices and strict errors.
 * We recommend using this level of reporting on live/production sites.
 */

//define('ERROR_REPORTING_LEVEL', (E_ALL | E_NOTICE | E_STRICT));
//define('ERROR_REPORTING_LEVEL', (E_ALL & ~E_NOTICE | E_STRICT));
define('ERROR_REPORTING_LEVEL', (E_ALL & ~E_NOTICE & ~E_STRICT));


/* USE_FORWARDED_IP
 * This controls how a visitor's IP Address is read.
 *
 * Normally, a visitor's IP Address can be found accessed in the
 * $_SERVER['REMOTE_ADDR'] variable.
 * If you are using a load balancer and/or a proxy, you must set this to true so
 * that the CMS will use the $_SERVER['HTTP_X_FORWARDED_FOR'] variable instead.
 */
define('USE_FORWARDED_IP', false);
