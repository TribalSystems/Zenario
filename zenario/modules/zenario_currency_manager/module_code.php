<?php
/*
 * Copyright (c) 2015, Tribal Limited
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

class zenario_currency_manager extends module_base_class {


	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		switch ($path){
			case 'zenario__currency_manager':
				if ($box['key']['id']) {
					$currencyId = $box['key']['id'];
					$currencyDetails = self::getCurrency($currencyId); 
					if ($currencyDetails){
						if(isset($currencyDetails['english_name']) && $currencyDetails['english_name']){
							if(isset($values['details/english_name'])){
								$values['details/english_name'] = $currencyDetails['english_name'];
							}
						}
						

						
						if(isset($currencyDetails['code']) && $currencyDetails['code']){
							if (isset($values['details/code'])){
								$values['details/code'] = $currencyDetails['code'];
							}
						}
						if(isset($currencyDetails['symbol_left']) && $currencyDetails['symbol_left']){
							if(isset($values['details/symbol_left'])){
								$values['details/symbol_left'] = $currencyDetails['symbol_left'];
							}
						}
						
						
						if(isset($currencyDetails['symbol_right']) && $currencyDetails['symbol_right']){
							if(isset($values['details/symbol_right'])){
								$values['details/symbol_right'] = $currencyDetails['symbol_right'];
							}
						}
						if(isset($currencyDetails['decimal_places']) && $currencyDetails['decimal_places']!=""){
							if (isset($values['details/decimal_places'])){
								$values['details/decimal_places'] = $currencyDetails['decimal_places'];
							}
						}
						if(isset($currencyDetails['decimal_separator']) && $currencyDetails['decimal_separator']){
							if (isset($values['details/decimal_separator'])){
								$values['details/decimal_separator'] = $currencyDetails['decimal_separator'];
							}
						}
						if(isset($currencyDetails['thousands_separator']) && $currencyDetails['thousands_separator']){
							if(isset($values['details/thousands_separator'])){
								$values['details/thousands_separator'] = $currencyDetails['thousands_separator'];
							}
						}
						if(isset($currencyDetails['rate']) && $currencyDetails['rate']){
							if (isset($values['details/rate'])){
								$values['details/rate'] = $currencyDetails['rate'];
							}
						}
						if(isset($currencyDetails['base_currency']) && $currencyDetails['base_currency']){
							if (isset($values['details/base_currency'])){
								$values['details/base_currency'] = $currencyDetails['base_currency'];
							}
						}
						
						if(isset($currencyDetails['last_updated_timestamp']) && $currencyDetails['last_updated_timestamp']){
							if (isset($values['details/last_updated'])){
							$values['details/last_updated'] = date('jS F Y H:i', strtotime($currencyDetails['last_updated_timestamp']));
							}
						}
						
						if(isset($currencyDetails['date_rate_last_fetched']) && $currencyDetails['date_rate_last_fetched']){
							if (isset($values['details/date_rate_last_fetched'])){
							$values['details/date_rate_last_fetched'] = date('jS F Y H:i', strtotime($currencyDetails['date_rate_last_fetched']));
							}
						}
						
						
						
					}
					$box['title']=adminPhrase('Editing the currency "[[english_name]] ([[code]])"',$currencyDetails);
				}else{
					if (isset($fields['details/last_updated'])){
						$fields['details/last_updated']['hidden'] = true;
					}
					
					if (isset($fields['details/date_rate_last_fetched'])){
						$fields['details/date_rate_last_fetched']['hidden'] = true;
					}
					
				}
			break;
		}
	}
	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'zenario__currency_manager':
				if ($box['key']['id']) {
					$id=$box['key']['id'];
					$baseCurrencyId = self::getBaseCurrencyId();
					
					if ($values['details/base_currency']){
						$fields['details/rate']['hidden'] = true;
					}else{
						$fields['details/rate']['hidden'] = false;
					}
					
					
				}
			break;
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving){
		switch ($path){
			case 'zenario__currency_manager':
					if ($box['key']['id']){
						if (isset($values['details/code'])&& ($values['details/code'])){
							$code=$values['details/code'];
							$code=trim($code);
							$currencyId = $box['key']['id'];
							$currencyCodeExists=self::checkCurrencyCodeExistsInDbExceptId($currencyId,$code);
							if ($currencyCodeExists){
								$fields['details/code']['error'] = adminPhrase('The currency code "[[code]]" is already saved.',array('code'=>$code));
							}
						}

						if (isset($values['details/english_name'])&& ($values['details/english_name'])){
							$currencyName=$values['details/english_name'];
							$currencyName=trim($currencyName);
							$currencyId = $box['key']['id'];
							$currencyNameExists = self::checkCurrencyNameExistsInDbExceptId($currencyId,$currencyName);
							if ($currencyNameExists){
								$fields['details/english_name']['error'] = adminPhrase('The currency name "[[currencyName]]" is already saved.',array('currencyName'=>$currencyName));
							}
						}
					}
					
					if (!$box['key']['id']) {
						if (isset($values['details/code'])&& ($values['details/code'])){
							$code=$values['details/code'];
							$code=trim($code);
							$currencyCodeExists=self::checkCurrencyCodeExistsInDb($code);
							if ($currencyCodeExists){
								$fields['details/code']['error'] = adminPhrase('The currency code "[[code]]" is already saved.',array('code'=>$code));
							}
						}
						
						if (isset($values['details/english_name'])&& ($values['details/english_name'])){
							$currencyName=$values['details/english_name'];
							$currencyName=trim($currencyName);
							$currencyNameExists = self::checkCurrencyNameExistsInDb($currencyName);
							if ($currencyNameExists){
								$fields['details/english_name']['error'] = adminPhrase('The currency name "[[currencyName]]" is already saved.',array('currencyName'=>$currencyName));
							}
						}
					}
					
					
					if($fields['details/rate']['hidden']==false){
						if (isset($values['details/rate'])&&(!$values['details/rate'])){
							$fields['details/rate']['error'] = adminPhrase('Please enter a currency rate.');
						}
					}

					if($fields['details/rate']['hidden']==false){
						if (isset($values['details/rate'])&&($values['details/rate'])){
							if(!is_numeric($values['details/rate'])){
								$fields['details/rate']['error'] = adminPhrase('Please enter a number in currency rate.');
							}
						}
					}
					
					if (isset($values['details/code'])&& (!$values['details/code'])){
						$fields['details/code']['error'] = adminPhrase('Please enter a currency code.');
					}
					
					if (isset($values['details/code'])&& ($values['details/code'])){
						$currencyCode=$values['details/code'];
						$currencyCode=trim($currencyCode);
						if (is_numeric($currencyCode)){
							$fields['details/code']['error'] = adminPhrase('The currency code cannot be numeric.');
						}
					}

					if (isset($values['details/english_name'])&& (!$values['details/english_name'])){
						$fields['details/english_name']['error'] = adminPhrase('Please enter a currency name.');
					}
					
					if (isset($values['details/english_name'])&& ($values['details/english_name'])){
						$currencyName=$values['details/english_name'];
						$currencyName=trim($currencyName);
						if (is_numeric($currencyName)){
							$fields['details/english_name']['error'] = adminPhrase('The currency name cannot be numeric.');
						}
					}
					
					if (isset($values['details/symbol_left']) && isset($values['details/symbol_right'])){
						if((!$values['details/symbol_left']) && (!$values['details/symbol_right'])){
							$fields['details/symbol_left']['error'] = adminPhrase('Please enter a symbol.');
						}
					}
						
					if(isset($values['details/symbol_left']) && ($values['details/symbol_left'])){
						if (is_numeric($values['details/symbol_left'])){
							$fields['details/symbol_left']['error'] = adminPhrase('"Symbol left" cannot be numeric.');
						}
					}
						
					if(isset($values['details/symbol_right']) && ($values['details/symbol_right'])){
						if (is_numeric($values['details/symbol_right'])){
							$fields['details/symbol_right']['error'] = adminPhrase('"Symbol right" cannot be numeric.');
						}
					}

					
					if (isset($values['details/decimal_places'])&& ($values['details/decimal_places']=="")){
						$fields['details/decimal_places']['error'] = adminPhrase('Please enter a decimal places.');
					}
					
					
					if (isset($values['details/decimal_separator'])&& (!$values['details/decimal_separator'])){
						$fields['details/decimal_separator']['error'] = adminPhrase('Please enter a decimal separator.');
					}
					
					
					
					if (isset($values['details/decimal_separator'])&& ($values['details/decimal_separator'])){
						$separator =$values['details/decimal_separator'];
						$separator=trim($separator);
						if ($values['details/decimal_separator']!='.' && $values['details/decimal_separator']!=',' ){
							$fields['details/decimal_separator']['error'] = adminPhrase('Please enter "." or "," in decimal separator.');
						}
					}

					if (isset($values['details/thousands_separator'])&& (!$values['details/thousands_separator'])){
						$fields['details/thousands_separator']['error'] = adminPhrase('Please enter a thousands separator.');
					}
					
					
					if (isset($values['details/thousands_separator'])&& ($values['details/thousands_separator'])){
						$separator =$values['details/thousands_separator'];
						$separator=trim($separator);
						if ($values['details/thousands_separator']!='.' && $values['details/thousands_separator']!=',' ){
							$fields['details/thousands_separator']['error'] = adminPhrase('Please enter "." or "," in thousands separator.');
						}
					}
					
					if(isset($values['details/decimal_separator'])&& ($values['details/decimal_separator']) && isset($values['details/thousands_separator'])&& ($values['details/thousands_separator'])){
						if ($values['details/decimal_separator']==$values['details/thousands_separator']){
							$fields['details/decimal_separator']['error']  = adminPhrase('The decimal and thousands separator must be different.');
						}
					}
					
				break;
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes){
		switch ($path){
			case 'zenario__currency_manager':
					$id = $box['key']['id'];
					$englishName = trim($values['details/english_name']);
					$code = trim($values['details/code']);
					$symbolLeft = trim($values['details/symbol_left']);
					$symbolRight = trim($values['details/symbol_right']);
					$decimalSeparator = trim($values['details/decimal_separator']);
					$thousandsSeparator = trim($values['details/thousands_separator']);
					$decimalPlaces = trim($values['details/decimal_places']);
					$rate = trim($values['details/rate']);
					$baseCurrency = trim($values['details/base_currency']);
					$previousBaseCurrencyId = self::getBaseCurrencyId();
					$lastUpdated=date("Y-m-d H:i:s");
					if($code){
						$code = strtoupper($code);
					}
					
				if ($id) {
					self::updateCurrency($id,$englishName,$code,$symbolLeft,$symbolRight,$decimalSeparator,$thousandsSeparator,$decimalPlaces,$rate,$lastUpdated);
					if ($baseCurrency){
						if($previousBaseCurrencyId){
							if($id != $previousBaseCurrencyId){
								self::resetCurreciesRate();
								self::updateBaseCurrency($previousBaseCurrencyId,0);
								self::updateBaseCurrency($id,1);
							}
						}
					}
					$box['key']['id']=$id;
				}else{
					if ($baseCurrency){
						self::resetCurreciesRate();
						$newId=self::setCurrency($englishName,$code,$symbolLeft,$symbolRight,$decimalSeparator,$thousandsSeparator,$decimalPlaces,$rate,$lastUpdated);
						self::updateBaseCurrency($newId,1);
						self::updateBaseCurrency($previousBaseCurrencyId,0);
						$box['key']['id']=$newId;
					}else{
						$newId=self::setCurrency($englishName,$code,$symbolLeft,$symbolRight,$decimalSeparator,$thousandsSeparator,$decimalPlaces,$rate,$lastUpdated);
						self::updateBaseCurrency($newId,0);
						$box['key']['id']=$newId;
					}
				}
				break;
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__administration/panels/currencies':
				if(request('action') == 'delete_currency') {
					foreach (explode(',', $ids) as $id) {
						self::deleteCurrency($id);
					}
				}
				if(request('action') == 'mark_as_base_currency') {
					foreach (explode(',', $ids) as $id) {
						$previousBaseCurrencyId = self::getBaseCurrencyId();
						if($previousBaseCurrencyId){
							self::updateBaseCurrency($previousBaseCurrencyId,0);
						}
						self::updateBaseCurrency($id,1);
					}
					self::resetCurreciesRate();
					self::updateCurrenciesDateTime();
				}
			break;
		}
	}
	
	//this function is called by schedule task
	public static function updateCurrencyRate(){
		$scheduleTask = true;
		$currenciesUpdated=self::updateCurrencyRateInDb($scheduleTask);
	
		if ($currenciesUpdated > 1 || $currenciesUpdated == 0) {
			echo $currenciesUpdated . " currencies updated.";
		} else {
			echo $currenciesUpdated . " currency updated.";
		}
		
		if($currenciesUpdated) {
			return true;
		} else {
			return false;
		}
	}
	
	
	
	public static function updateCurrencyRateInDb($scheduleTask=false){
	
		$baseCurrencyId = self::getBaseCurrencyId();
		$baseCurrencyDetails = self::getCurrency($baseCurrencyId);
		$baseCurrencyCode= $baseCurrencyDetails['code'];
		$currenciesWithoutBaseCurrency = self::getCurrenciesExceptTheBaseCurrency();
		
		$i=0;
		if($currenciesWithoutBaseCurrency){
			foreach ($currenciesWithoutBaseCurrency as $currency){
				$currencyRate=self::getCurrencyRate($baseCurrencyCode,$currency['code']);
				
				if($currencyRate!=false){
					self::updateCurrencyRateColumn($currency['id'],$currencyRate);
					//self::updateCurrencyDateTime($currency['id']);
					if ($scheduleTask){
						self::updateDateRateLastFetched($currency['id']);
					}
				}else{
					echo "Connection error with the currency code: ".$currency['code'];
				}
				$i++;
			}
		}
		return $i;
	}
	
	public static function getCurrencyRate($currencyCode1,$currencyCode2){
		return self::getYahooCurrencyRateValue($currencyCode1,$currencyCode2);
	}
	
	//currency return base currency stored
	public static function getCurrencyRateFromDb($currencyCodeOrId){
		if(is_numeric($currencyCodeOrId)) {
			return self::getCurrencyRateFromId($id);
		} else {
			return self::getCurrencyRateFromCode($currencyCodeOrId);
		}
	}
	
	
	public static function getYahooCurrencyRateValue($currencyCode1,$currencyCode2){
		$currenciesCode=$currencyCode1.$currencyCode2;
		$query="select * from yahoo.finance.xchange where pair in ('".$currenciesCode."')";
		$currencyRate=self::getYahooCurrencyRateArray($query);
		$currecyRateResult=$currencyRate['query']['results']['rate']['Rate'];
		return $currecyRateResult;
	}
	
	
	public static function getYahooCurrencyRateArray($query,$attempt = 0){
		//$env=urlencode("store://datatables.org/Falltableswithkeys");
		$env="store%3A%2F%2Fdatatables.org%2Falltableswithkeys&callback=";
		$query=urlencode($query);
		$yahooUrl = "http://query.yahooapis.com/v1/public/yql?q=". $query."&format=json&env=".$env;
		if ($attempt < 1) {
			$curl_handle=curl_init();
			curl_setopt($curl_handle,CURLOPT_CONNECTTIMEOUT,8);
			curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,true);
			curl_setopt($curl_handle, CURLOPT_URL, $yahooUrl);
			$response = curl_exec($curl_handle);
			curl_close($curl_handle);
			if ($response) {
				return json_decode($response, true);
			} else {
				$attempt++;
				self::getYahooCurrencyRate($attempt);
			}
		} else {
			return false;
		}
	} 
	
	
	//add new a new currency
	public static function setCurrency($englishName,$code,$symbolLeft,$symbolRight,$decimalSeparator,$thousandsSeparator,$decimalPlaces,$rate,$lastUpdated){
		$rowId = setRow(
				ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
				array(
						'english_name' => $englishName, 
						'code' => $code,
						'symbol_left' => $symbolLeft,
						'symbol_right' => $symbolRight,
						'decimal_separator' => $decimalSeparator,
						'thousands_separator' => $thousandsSeparator,
						'decimal_places' => $decimalPlaces,
						'rate' => $rate,
						'last_updated_timestamp'=>$lastUpdated
					)
			);
		return $rowId;
	}
	
	//Update currency
	public static function updateCurrency($id,$englishName,$code,$symbolLeft,$symbolRight,$decimalSeparator,$thousandsSeparator,$decimalPlaces,$rate,$lastUpdated){
		$rowId = updateRow(
				ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
				array(
						'english_name' => $englishName, 
						'code' => $code,
						'symbol_left' => $symbolLeft,
						'symbol_right' => $symbolRight,
						'decimal_separator' => $decimalSeparator,
						'thousands_separator' => $thousandsSeparator,
						'decimal_places' => $decimalPlaces,
						'rate' => $rate,
						'last_updated_timestamp'=>$lastUpdated
						),
						array('id' => $id)
					);
		return $rowId;
	}
	
	//Update column 'base' if the currency is mask as base
	public static function updateBaseCurrency($id,$baseCurrency){
		$rowId=updateRow(
						ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
						array('base_currency' => $baseCurrency),
						array('id' => $id)
					);
		return $rowId;
	}
	

	
	//Get a specific curency
	public static function getCurrency($id){
		$currency = getRow(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
							array(	'id',
									'english_name', 
									'code',
									'symbol_left',
									'symbol_right',
									'decimal_separator',
									'thousands_separator',
									'decimal_places',
									'rate',
									'base_currency',
									'last_updated_timestamp',
									'date_rate_last_fetched'
									),
									array('id' => $id)
								);
		return $currency;
	}
	
	//Get all currencies from DB
	public static function getCurrencies(){
		$currencies = getRowsArray(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
							array(	'id',
									'english_name', 
									'code',
									'symbol_left',
									'symbol_right',
									'decimal_separator',
									'thousands_separator',
									'decimal_places',
									'rate',
									'base_currency',
									'last_updated_timestamp'
									));
		return $currencies;
	}
	
	public static function deleteCurrency($id){
		$currency = deleteRow(ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',array('id' => $id));
		return $currency;
	}
	
	public static function getBaseCurrencyId(){
		$currencyId = getRow(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies','id',array('base_currency' => 1)
						);
		return $currencyId;
	}
	
	public static function getBaseCurrencyCode(){
		$currencyCode = getRow(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies','code',array('base_currency' => 1)
						);
		return $currencyCode;
	}
	
	public static function getCurrenciesExceptTheBaseCurrency(){
		$sql = "
			SELECT id, english_name, code, symbol_left, symbol_right, decimal_separator, thousands_separator, decimal_places, rate, base_currency
			FROM ".DB_NAME_PREFIX. ZENARIO_CURRENCY_MANAGER_PREFIX. "currencies
			WHERE base_currency != 1";
			
			$currencies = array();
			$result = sqlQuery($sql);
			while($row = sqlFetchAssoc($result)) {
				$currencies[] = $row;
			}
			
		
		if (isset($currencies) && $currencies){
			return $currencies;
		}else{
			return false;
		}
	}
	
	public static function updateCurrencyRateColumn($id,$rate){
		$rowId=updateRow(
						ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
						array('rate' => $rate),
						array('id' => $id)
					);
		return $rowId;
	}
	
	public static function resetCurrencyRate($id){
		$rowId=self::updateCurrencyRateColumn($id,1);
		return $rowId;
	}
	
	public static function resetCurreciesRate(){
		$currencies = self::getCurrencies();
		if($currencies){
			foreach ($currencies as $currency){
				self::resetCurrencyRate($currency['id']);
			}
		}
	}
	
	public static function getCurrencyRateFromId($id){
			$currencyRate = getRow(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
							array('rate'),
							array('id' => $id)
							);
		return $currencyRate;
	}
	
	public static function getCurrencyRateFromCode($code){
			$currencyRate = getRow(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
							array('rate'),
							array('code' => $code)
							);
		return $currencyRate;
	}
	
	public static function getCurrencyDetailsFromCode($code){
	
			$currency = getRow(
							ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
							array(	'id',
									'english_name', 
									'code',
									'symbol_left',
									'symbol_right',
									'decimal_separator',
									'thousands_separator',
									'decimal_places',
									'rate',
									'base_currency',
									'last_updated_timestamp'
									),
									array('code' => $code)
								);
		if($currency){
			return $currency;
		}else{
			return false;
		}
	
	}
	
	public static function updateCurrencyDateTime($id){
		$dateTime=date("Y-m-d H:i:s");
			$rowId=updateRow(
						ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
						array('last_updated_timestamp' => $dateTime),
						array('id' => $id)
					);
		return $rowId;
	}
	
	
	
	public static function updateDateRateLastFetched($id){
		$dateTime=date("Y-m-d H:i:s");
			$rowId=updateRow(
						ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies',
						array('date_rate_last_fetched' => $dateTime),
						array('id' => $id)
					);
		return $rowId;
	}
	
	
	public function checkCurrencyCodeExistsInDb($code){
		return checkRowExists(ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies', array('code' => $code));
	}
	
	public function checkCurrencyNameExistsInDb($currencyName){
		return checkRowExists(ZENARIO_CURRENCY_MANAGER_PREFIX. 'currencies', array('english_name' => $currencyName));
	}
	
	public static function updateCurrenciesDateTime(){
		$currencies = self::getCurrencies();
		if($currencies){
			foreach ($currencies as $currency){
				self::updateCurrencyDateTime($currency['id']);
			}
		}
	}
	
	
	public static function checkCurrencyNameExistsInDbExceptId($id,$currencyName){
		$sql = "
			SELECT id
			FROM ".DB_NAME_PREFIX. ZENARIO_CURRENCY_MANAGER_PREFIX. "currencies
			WHERE english_name = '".$currencyName."'
			AND id != ".$id;
			
			$currencies = array();
			$result = sqlQuery($sql);
			while($row = sqlFetchAssoc($result)) {
				$currencies[] = $row;
			}
			
		
		if (isset($currencies) && $currencies){
			return true;
		}else{
			return false;
		}
	}
	
	//public static function 
	
	
	public static function checkCurrencyCodeExistsInDbExceptId($id,$code){
		$sql = "
			SELECT id
			FROM ".DB_NAME_PREFIX. ZENARIO_CURRENCY_MANAGER_PREFIX. "currencies
			WHERE code = '".$code."'
			AND id != ".$id;
			
			$currencies = array();
			$result = sqlQuery($sql);
			while($row = sqlFetchAssoc($result)) {
				$currencies[] = $row;
			}

		if (isset($currencies) && $currencies){
			return true;
		}else{
			return false;
		}
	}



}