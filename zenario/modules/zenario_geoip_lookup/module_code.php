<?php
/*
 * Copyright (c) 2026, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

use GeoIp2\Database\Reader;

class zenario_geoip_lookup extends ze\moduleBaseClass {

	public static function getCountryISOCodeForIp($ip, $ignoreDebugCountry = false) {
		$code = '';
		if (ze::setting('force_visitor_country') && !$ignoreDebugCountry) {
			$code = ze::setting('force_visitor_country');
		} else {
			$lookUpCode = false;
			$dir = CMS_ROOT . "zenario_custom/GeoIP/";
			if (file_exists($dir) && is_dir($dir) && is_readable($dir) && is_writeable($dir) && is_executable($dir)) {
				$file = $dir . '/GeoLite2-Country.mmdb';
				if (is_file($file) && is_readable($file) && is_writeable($file) && !is_executable($file)) {
					$lookUpCode = true;
				}
			}
			
			if ($lookUpCode) {
				try {
					$reader = new Reader(CMS_ROOT . 'zenario_custom/GeoIP/GeoLite2-Country.mmdb');
					$record = $reader->country($ip);
					$code = $record->country->isoCode;
				} catch (Exception $e) {
					//If the IP address was invalid, or could not be looked up, return the code as an empty string for now...
					return $code;
				}
			}
			
		}
		return $code;
	}
	
	// Enhanced method to get detailed location information (city, region, country)
	public static function getLocationInfoForIp($ip, $ignoreDebugCountry = false) {
		$info = [
			'country_code' => '',
			'country_name' => '',
			'city_name' => '',
			'region_name' => '',
			'region_code' => '',
			'postal_code' => '',
			'latitude' => null,
			'longitude' => null,
			'timezone' => ''
		];

		// Check for forced country setting first
		if (ze::setting('force_visitor_country') && !$ignoreDebugCountry) {
			$info['country_code'] = ze::setting('force_visitor_country');
			return $info;
		}

		$dir = CMS_ROOT . "zenario_custom/GeoIP/";
		$lookUpCode = false;

		// Check directory permissions
		if (file_exists($dir) && is_dir($dir) && is_readable($dir) && is_writeable($dir) && is_executable($dir)) {
			
			// Try city database first (more detailed information)
			$cityFile = $dir . '/GeoLite2-City.mmdb';
			if (is_file($cityFile) && is_readable($cityFile) && is_writeable($cityFile) && !is_executable($cityFile)) {
				try {
					$reader = new Reader($cityFile);
					$record = $reader->city($ip);
					
					$info['country_code'] = $record->country->isoCode ?: '';
					$info['country_name'] = $record->country->name ?: '';
					$info['city_name'] = $record->city->name ?: '';
					$info['region_name'] = $record->mostSpecificSubdivision->name ?: '';
					$info['region_code'] = $record->mostSpecificSubdivision->isoCode ?: '';
					$info['postal_code'] = $record->postal->code ?: '';
					$info['latitude'] = $record->location->latitude;
					$info['longitude'] = $record->location->longitude;
					$info['timezone'] = $record->location->timeZone ?: '';
					
					return $info;
				} catch (Exception $e) {
					// Fall back to country database
				}
			}

			// Fall back to country database if city database failed or doesn't exist
			$countryFile = $dir . '/GeoLite2-Country.mmdb';
			if (is_file($countryFile) && is_readable($countryFile) && is_writeable($countryFile) && !is_executable($countryFile)) {
				try {
					$reader = new Reader($countryFile);
					$record = $reader->country($ip);
					
					$info['country_code'] = $record->country->isoCode ?: '';
					$info['country_name'] = $record->country->name ?: '';
					
				} catch (Exception $e) {
					// Return empty info
				}
			}
		}

		return $info;
	}

	// Method that form fields can use to preload a users country in a centralised select list
	public static function getCountryCodeForVisitorByIP($ignoreDebugCountry = false) {
		return static::getCountryISOCodeForIp(ze\user::ip(), $ignoreDebugCountry);
	}

	// Method to format location information for display
	public static function formatLocationString($ip, $includeCity = true, $includeRegion = true, $includeCountry = true, $separator = ', ') {
		$info = static::getLocationInfoForIp($ip);
		$parts = [];

		if ($includeCity && !empty($info['city_name'])) {
			$parts[] = $info['city_name'];
		}

		if ($includeRegion && !empty($info['region_name']) && $info['region_name'] !== $info['city_name']) {
			$parts[] = $info['region_name'];
		}

		if ($includeCountry) {
			if (!empty($info['country_name'])) {
				$parts[] = $info['country_name'];
			} elseif (!empty($info['country_code'])) {
				$parts[] = $info['country_code'];
			}
		}

		return implode($separator, $parts);
	}

	// Enhanced method for visitor location (replaces getCountryCodeForVisitorByIP for city info)
	public static function getLocationInfoForVisitorByIP($ignoreDebugCountry = false) {
		return static::getLocationInfoForIp(ze\user::ip(), $ignoreDebugCountry);
	}
	
	public static function checkVisitorInCountries($isoCodeCSVList,$testType){
		$rv = false;
		if (strtolower($testType)=='exclude'){
			$testType = true;
		} else {
			$testType = false;
		}
		if ($code = self::getCountryISOCodeForIp(ze\user::ip())){
			foreach (explode(",", $isoCodeCSVList) as $arrayElement){
				if (trim(strtoupper($code)) == trim(strtoupper($arrayElement))) {
					$rv = true;
					break;
				}
			}
			$rv = $testType ^ $rv;
		}
		return $rv;
	}

	// Scheduled task to update the GeoIP2 database used by this module.
	// Now defaults to City database for enhanced location information
	public static function jobUpdateGeoIPDatabase() {
		try {
			$result = static::updateGeoIP2Database(true); // Force city database
		} catch(Exception $e) {
			exit($e->getMessage());
		}
		if ($result) {
			echo "GeoIP2 City database has been updated to the latest version.\n";
			return true;
		} else {
			echo "The GeoIP2 City Database has not been changed since the previous update.\n";
		}
	}
	
	public static $errors = [
		"license_key_error" => "No License Key found.",
		"directory_error" =>
			"The directory \"zenario_custom/GeoIP\" that is needed to hold the GeoIP database does not exist or does not have the correct permissions.
			Please make the directory if it does not exist, and make it readable and writeable by the web server (cd zenario_custom; mkdir GeoIP; chmod 777 GeoIP; chmod 666 GeoIP/*.mmdb).",
		"db_file_error" =>
			"The GeoIP database file exists, but it does not have the correct permissions.
			Please make it readable and writeable by the web server, but not executable (cd zenario_custom/GeoIP; chmod 666 *.mmdb).",
		"curl_error" => "Could not fetch data, curl is not working.",
		"api_error" => "Could not fetch data. Curl is working, but the data could not be retrieved. Please try again in a few minutes, or check that your key is valid.",
		"header_error" => "Unable to find Last-Modified date in request header.",
		"download_error" => "Unable to download GeoIP2 database."
	];
	
	public static function updateGeoIP2Database($forceCity = null) {
		// We use the direct download method rather than the pre-written update program.
		// https://dev.maxmind.com/geoip/geoip-direct-downloads/
		
		$licenceKey = ze::setting("maxmind_geoip2_license_key");
		if (!$licenceKey) {
			throw new Exception(static::$errors["license_key_error"]);
		}

		$dir = CMS_ROOT . "zenario_custom/GeoIP/";
		$archivePath = $dir . "archive.tar.gz";
		if (!file_exists($dir) || !is_dir($dir) || !is_readable($dir) || !is_writeable($dir) || !is_executable($dir)) {
			throw new Exception(static::$errors["directory_error"]);
		}

		// Determine which database to download
		if ($forceCity !== null) {
			// Parameter override (used by scheduled task)
			$useCity = $forceCity;
		} else {
			// Check user preference, default to City for better data
			$useCity = ze::setting('geoip_use_city_database') !== false; // Default to true
		}
		
		$edition = $useCity ? 'GeoLite2-City' : 'GeoLite2-Country';
		
		echo "Downloading $edition database...\n";
		
		$url = "https://download.maxmind.com/app/geoip_download?edition_id=" . $edition . "&license_key=" . urlencode($licenceKey) . "&suffix=tar.gz";

		// Check first of all whether there has been any changes to the file since the last update (this request does not count towards the daily download limit)
		// Get the headers ONLY of the target file
		$response = ze\curl::fetch($url, $post=false, $options=[CURLOPT_NOBODY => true, CURLOPT_HEADER => true]);
		if (!$response) {
			if (!ze\curl::checkEnabled()) {
				throw new Exception(static::$errors["curl_error"]);
			} else {
				throw new Exception(static::$errors["api_error"]);
			}
		}

		// Check the Last-Modified header
		$headers = ze\curl::getHeadersFromResponse($response);
		$lastModifiedDateTime = '';
		
		if (isset($headers["Last-Modified"])) {
			$lastModifiedDateTime = $headers["Last-Modified"];
		} elseif (isset($headers["last-modified"])) {
			$lastModifiedDateTime = $headers["last-modified"];
		}
		
		if ($lastModifiedDateTime) {
			$dateTime = new DateTime($lastModifiedDateTime);
			$timestamp = $dateTime->getTimestamp();

			// Compare to the latest update timestamp and only update the archive if it is newer
			$lastModifiedTimestamp = ze\row::max(ZENARIO_GEOIP_LOOKUP_PREFIX . "geoip_update_log", "last_modified_timestamp", []);
			if ($timestamp <= $lastModifiedTimestamp) {

				// Just to make sure, check the database file does exist in case it was deleted manually for some reason and needs to be re-downloaded
				$dbFileName = $useCity ? "GeoLite2-City.mmdb" : "GeoLite2-Country.mmdb";
				if (file_exists($dir . $dbFileName)) {
					return false;
				}
			}
		} else {
			throw new Exception(static::$errors["header_error"]);
		}
		

		// Fetch database archive from maxmind
		$result = ze\curl::fetch($url, $post=false, $options=[], $archivePath);
		// Handle curl error
		if (!$result) {
			throw new Exception(static::$errors["download_error"]);
		}

		// Extract files from archive. existing files are overwritten.
		$phar = new PharData($archivePath);
		$phar->extractTo($dir);
		$dirname = $phar->getFileName() . "/";

		// Handle both City and Country database files
		// Use the $useCity variable already determined earlier in the function
		$dbFileName = $useCity ? "GeoLite2-City.mmdb" : "GeoLite2-Country.mmdb";

		// There should only be these 3 files in the archive downloaded.
		$files = ["COPYRIGHT.txt", "LICENSE.txt", $dbFileName];
		foreach ($files as $filename) {
			if (file_exists($dir . $dirname . $filename)) {
				rename($dir . $dirname . $filename, $dir . $filename);
			}
			
			@chmod($dir . $filename, 0666);
		}
		
		// Cleanup files
		unlink($archivePath);
		$success = ze\cache::deleteDir($dir. $dirname);
		if (!$success) {
			throw new Exception("Unable to delete directory \"" . $dir . $dirname . "\", there may be an unexpected file there.\n");
		}
		
		// Update log
		ze\row::insert(ZENARIO_GEOIP_LOOKUP_PREFIX . "geoip_update_log", ["logged" => date("Y-m-d H:i:s"), "last_modified_timestamp" => $timestamp]);
		return true;
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		switch ($path) {
			case "zenario__administration/panels/site_settings":
				$panel["items"]["api_keys"]["keywords"] .= " Maxmind IP location GeoIP";
				break;
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (ze::$isTwig) return;
		switch($path) {
			case "site_settings":
				if ($settingGroup == "ip_services") {
					// Check directory used for GeoIP2 databases exists.
					$everythingOk = true;
					$dir = CMS_ROOT . "zenario_custom/GeoIP/";
					if (!file_exists($dir) || !is_dir($dir) || !is_readable($dir) || !is_writeable($dir) || !is_executable($dir)) {
						$box["tabs"]["orders"]["notices"]["missing_dir"]["show"] = true;
						$box["tabs"]["orders"]["notices"]["missing_dir"]["message"] = static::$errors["directory_error"];
						$everythingOk = false;
					} else if (!file_exists($dir . "GeoLite2-City.mmdb") && !file_exists($dir . "GeoLite2-Country.mmdb")) {
						$box["tabs"]["orders"]["notices"]["fetch_now"]["show"] = true;
						$box["tabs"]["orders"]["fields"]["fetch_now_button"]["hidden"] = false;
						// Perform a manual fetch of the database.
						if (!empty($fields["orders/fetch_now_button"]["pressed"])) {
							try {
								$result = static::updateGeoIP2Database(true); // Download city database
								if ($result) {
									// Fetch success!
									$box["tabs"]["orders"]["notices"]["fetch_success"]["show"] = true;
									$box["tabs"]["orders"]["notices"]["fetch_now"]["show"] = false;
									$box["tabs"]["orders"]["fields"]["fetch_now_button"]["hidden"] = true;
								} else {
									// Button SHOULD be hidden in this case, but show a message just in case...
									echo "It looks like the database has already been downloaded.";
								}
							} catch (Exception $e) {
								echo $e->getMessage();
								$everythingOk = false;
							}
							
						}
					} else {
						// Check whichever database file exists (prioritize City database)
						$cityFile = $dir . '/GeoLite2-City.mmdb';
						$countryFile = $dir . '/GeoLite2-Country.mmdb';
						$activeFile = file_exists($cityFile) ? $cityFile : $countryFile;
						
						if (!is_file($activeFile) || !is_readable($activeFile) || !is_writeable($activeFile) || is_executable($activeFile)) {
							$box["tabs"]["orders"]["notices"]["file_has_wrong_perms"]["show"] = true;
							$box["tabs"]["orders"]["notices"]["file_has_wrong_perms"]["message"] = static::$errors["db_file_error"];
							$everythingOk = false;
						} else {
							$lastUpdate = ze\row::max(ZENARIO_GEOIP_LOOKUP_PREFIX . "geoip_update_log", "logged");
							if ($lastUpdate) {
								$lastUpdate = ze\date::format($lastUpdate);
							
								// Determine which database type is active
								$databaseType = file_exists($cityFile) ? 'City' : 'Country';
								$databaseSize = file_exists($cityFile) ? '~50MB, includes city/region data' : '~10MB, country data only';
								
								$box["tabs"]["orders"]["notices"]["data_loaded"]["show"] = true;
								$box["tabs"]["orders"]["notices"]["data_loaded"]["message"] = ze\admin::phrase("GeoIP [[type]] database is loaded ([[size]]), last update [[date]]", [
									"type" => $databaseType,
									"size" => $databaseSize,
									"date" => $lastUpdate
								]);
							}
						}
					}
					
					$link = ze\link::absolute() . '/organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tgeoip~k{"id"%3A"api_keys"}';
					$fields["orders/description"]["snippet"]["html"] = ze\admin::phrase('GeoIP lookup is based on GeoLite2 City Database by MaxMind (includes city, region, and country data). A MaxMind account is needed (<a href="https://www.maxmind.com/en/geolite2/signup" target="_blank">signup here</a>) to generate a License Key that must be entered in the <a href="[[link]]" target="_blank">API keys area.</a>', ["link" => htmlspecialchars($link)]);

					// Show which database files are present
					$cityFile = $dir . '/GeoLite2-City.mmdb';
					$countryFile = $dir . '/GeoLite2-Country.mmdb';
					$dbFiles = [];
					if (file_exists($cityFile)) {
						$dbFiles[] = 'GeoLite2-City.mmdb';
					}
					if (file_exists($countryFile)) {
						$dbFiles[] = 'GeoLite2-Country.mmdb';
					}
					if (!empty($dbFiles)) {
						$fields["orders/database_files"]["snippet"]["html"] = implode(', ', $dbFiles);
					} else {
						$fields["orders/database_files"]["snippet"]["html"] = ze\admin::phrase('No database files found');
					}

					if ($ip = ze\user::ip()) {
						$fields["orders/current_ip"]["snippet"]["html"] = $ip;

						// Get detailed location information
						$locationInfo = static::getLocationInfoForIp($ip, $ignoreDebugCountry = true);
						$locationParts = [];

						if (!empty($locationInfo['city_name'])) {
							$locationParts[] = $locationInfo['city_name'];
						}
						if (!empty($locationInfo['region_name']) && $locationInfo['region_name'] !== $locationInfo['city_name']) {
							$locationParts[] = $locationInfo['region_name'];
						}

						// Get country name from country manager if available
						$country = false;
						if ($locationInfo['country_code'] && ze\module::inc('zenario_country_manager')) {
							$country = zenario_country_manager::getEnglishCountryName($locationInfo['country_code']);

							//Fall back gracefully if a country had been deleted
							if (!$country || substr($country, 0, 14) == '_COUNTRY_NAME_') {
								$country = $locationInfo['country_name'] ?: ze\admin::phrase('could not find country name');
							}
						} elseif (!empty($locationInfo['country_name'])) {
							$country = $locationInfo['country_name'];
						}

						if ($country) {
							$locationParts[] = $country . ' (' . $locationInfo['country_code'] . ')';
						} elseif ($locationInfo['country_code']) {
							$locationParts[] = $locationInfo['country_code'];
						}

						if (!empty($locationParts)) {
							$fields["orders/current_location"]["snippet"]["html"] = implode(', ', $locationParts);
						} else {
							$fields["orders/current_location"]["snippet"]["html"] = ze\admin::phrase('Could not look up location');
						}
					}
				
					if (!empty($box['tabs']['orders']['fields']['check_ip']['pressed']) &&  ($ip=($values['orders/ip_to_check'] ?? false))) {
						$code = static::getCountryISOCodeForIp($ip, $ignoreDebugCountry = true);
						if (ze\module::inc('zenario_country_manager')) {
							$country = zenario_country_manager::getEnglishCountryName($code);
						}
						if ($country) {
							$box['tabs']['orders']['fields']['country_checked']['value'] = $country; 
						} elseif ($code) {
							$box['tabs']['orders']['fields']['country_checked']['value'] = $code; 
						}				
					}
				}
				break;
		}
	}
	
}
