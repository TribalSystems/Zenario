<?php
/*
 * Copyright (c) 2020, Tribal Limited
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

class zenario_location_ratings extends zenario_location_manager {
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path == "zenario__locations/nav/accreditors/panel") {
			foreach ($panel['items'] as $id => &$item) {
				$accreditorDetails = $this->getAccreditorDetails($id);
				
				if ($accreditorDetails['score_type'] == "boolean") {
					$item['link'] = false;
				} else {
					$item['tooltip'] = ze\admin::phrase("View this Accreditor's ratings");
				}
			}
		} elseif ($path=="zenario__locations/nav/accreditors/panel/hidden_nav/accreditor_scores/panel") {
			$accreditorDetails = $this->getAccreditorDetails($refinerId);
			
			$panel['title'] = "Ratings for the Accreditor \"" . $accreditorDetails['name'] . "\"";
		}
	}

	
	function addOrUpdateAccreditor($floatingBoxName,$tab,$ID,$name,$scoreType){
		if (($rv=$this->addOrUpdateAccreditorCheck($floatingBoxName,$tab,$ID,$name))==''){
			if ($ID){
				$accreditor = $this->getAccreditorDetails($ID);
			
				$sql = 'UPDATE ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
				$sql .= 'SET ';
				
				if ($tab=="Details") {
					if ($name) {
						$sql .= "name = '" . ze\escape::sql($name) . "',";
					}
					
					if ($scoreType) {
						$sql .= "score_type = '" . ze\escape::sql($scoreType) . "'";
				
						if ($scoreType!=$accreditor['score_type']) {
							$updateScores = true;
						}
						
					}
				}
				
				if (substr($sql,-1)==",") {
					$sql = substr($sql,0,(strlen($sql)-1));
				}
				
				$sql .=' WHERE id=' . (int)$ID ;
				
				ze\sql::update($sql);
				
				if ($updateScores) {
					$this->prepopulateAccreditorScores($ID);
				}
				
				return $ID;
			} else {
				$sql = 'INSERT INTO ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
				$sql .= '(`name`,`score_type`)';
				$sql .=' VALUES (\'' . ze\escape::sql($name) . '\',\'' . ze\escape::sql($scoreType) . '\')';

				ze\sql::update($sql);
				$accreditorId = ze\sql::insertId();
				
				$this->prepopulateAccreditorScores($accreditorId);
				
				return $accreditorId;
			}
		} else {
			return $rv;
		}
	}

	function prepopulateAccreditorScores ($accreditorId) {
		$accreditor = $this->getAccreditorDetails($accreditorId);
	
		$sql = "
				DELETE
				FROM ". DB_PREFIX. ZENARIO_LOCATION_RATINGS_PREFIX. "accreditor_scores
				WHERE accreditor_id = ". (int) $accreditorId;
				
		$result = ze\sql::update($sql);
	
		if ($accreditor['score_type']=="numeric") {
			$sql = 'INSERT INTO ' . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores (`accreditor_id`,`score`)
					VALUES (' . $accreditorId . ',1),(' . $accreditorId . ',2),(' . $accreditorId . ',3),(' . $accreditorId . ',4),(' . $accreditorId . ',5)';
					
			$result = ze\sql::update($sql);
		} elseif ($accreditor['score_type']=="boolean") {
			$sql = 'INSERT INTO ' . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores (`accreditor_id`,`score`)
					VALUES (' . $accreditorId . ',\'Yes\'),(' . $accreditorId . ',\'No\')';
					
			$result = ze\sql::update($sql);
		}
	}

	function checkAccreditorNameUnique ($name,$id) {
		$sql = "SELECT id
				FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors
				WHERE name = '" . ze\escape::sql($name) . "'";
		
		if ($id) {
			$sql .= " AND id <> " . (int) $id;
		}
		
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			return false;
		} else {
			return true;
		}
	}

	function addOrUpdateAccreditorCheck($floatingBoxName,$tab,$ID,$name){
		if ($tab=="Details" && !$name) {
			return "You must enter a Name.";
		}
		
		if ($tab=="Details" && !$this->checkAccreditorNameUnique($name,$ID)) {
			return "You must enter a unique Name.";
		}
	}

	function deleteAccreditor($ID){
		$sql = 'SELECT id
				FROM ' . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores
				WHERE accreditor_id = ' . (int) $ID;
				
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchAssoc($result)) {
				$sql = 'DELETE 
						FROM ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'location_accreditor_score_link 
						WHERE accreditor_score_id=' . (int) $row['id'];
						
				ze\sql::update($sql);
			}
		}
		
		$sql = 'DELETE 
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores 
				WHERE accreditor_id=' . (int)$ID;
				
		ze\sql::update($sql);
		
		$sql = 'DELETE 
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors 
				WHERE id=' . (int)$ID;
				
		ze\sql::update($sql);
	}
	
	static function getAccreditors(){
		$rv = [];
		$sql = 'SELECT id,
					name,
					score_type
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
		if (ze\sql::numRows($result = ze\sql::select($sql))>0){
			while ($row = ze\sql::fetchAssoc($result)) {
				$rv[] = $row;
			}
		}
		return $rv;
	}

	static function getAccreditorDetails($ID){
		$rv = [];
		$sql = 'SELECT name,
					score_type
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
		$sql .= 'WHERE id = ' . (int) $ID;
		if (ze\sql::numRows($result = ze\sql::select($sql))==1){
			$rv = ze\sql::fetchAssoc($result);
		}
		return $rv;
	}

	static function getAccreditorScores($ID){
		$accreditor = self::getAccreditorDetails($ID);
	
		$scores = [];
		$sql = 'SELECT id,
					score
				FROM ' .DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores ';
		$sql .= 'WHERE accreditor_id = ' . (int) $ID . '
				ORDER BY score';

		if ($accreditor['score_type']=="boolean") {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		$result = ze\sql::select($sql);

		if (ze\sql::numRows($result)>0) {
			while ($row = ze\sql::fetchAssoc($result)) {
				$scores[] = $row;
			}
		}

		return $scores;
	}

	static function getAccreditorScore($id){
		return ze\row::get(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores",["id","score"],["id" => $id]);
	}

	static function getLocationAccreditorScores ($locationId) {
		$sql = "SELECT id,
					location_id,
					accreditor_score_id
				FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
				WHERE location_id = " . (int) $locationId;
				
		$result = ze\sql::select($sql);
				
		if (ze\sql::numRows($result)>0) {
			$locationAccreditorScores = [];
			
			while ($row = ze\sql::fetchAssoc($result)) {
				$locationAccreditorScores[] = $row;
			}
			
			return $locationAccreditorScores;
		} else {
			return false;
		}
	}

	public static function getAccreditorAndScoreFromScoreId ($id) {
		$sql = "SELECT accreditor_id,
					score
				FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores
				WHERE id = " . (int) $id;
				
		$result = ze\sql::select($sql);
		
		if (ze\sql::numRows($result)>0) {
			$row = ze\sql::fetchAssoc($result);
			
			return $row;
		} else {
			return false;
		}
	}

	function updateAccreditationScores ($locationId,$accreditations) {
		$sql = "DELETE
				FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
				WHERE location_id = " . (int) $locationId;
				
		$result = ze\sql::update($sql);
		
		foreach ($accreditations as $accreditation) {
			$sql = "INSERT INTO " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
					SET location_id = " . (int) $locationId . ",
						accreditor_score_id = " . (int) $accreditation['score'];
						
			$result = ze\sql::update($sql);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__locations/nav/accreditors/panel':
				switch ($_POST['action'] ?? false){
					case 'delete_accreditor':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
							if (($rv = $this->deleteAccreditor($ID))!=''){
								echo htmlspecialchars($rv);
							}
						}
						break;
				}
			case 'zenario__locations/nav/accreditors/panel/hidden_nav/accreditor_scores/panel':
				switch ($_POST['action'] ?? false){
					case 'delete_accreditor_score':
						$IDs = explode(',',$ids);
						foreach ($IDs as $ID){
							$this->deleteAccreditorScore($ID);
						}
						break;
				}
		}
	}

	function deleteAccreditorScore($id) {
		$sql = "
				DELETE
				FROM ". DB_PREFIX. ZENARIO_LOCATION_RATINGS_PREFIX. "accreditor_scores
				WHERE id = ". (int) $id;
				
		$result = ze\sql::update($sql);
	}
	
	public function showSlot() {
		$locationId = parent::getLocationIdFromContentItem($this->cID,$this->cType);
		
		$mergeFields = [];
		$subSections = [];
		
		$mergeFields['Location_Rating_Title'] = $this->phrase("Location Ratings");
		
		if ($locationAccreditorScoreIds = $this->getLocationAccreditorScores($locationId)) {
			foreach ($locationAccreditorScoreIds as $locationAccreditorScoreId) {
				if ($locationAccreditorScore = self::getAccreditorAndScoreFromScoreId($locationAccreditorScoreId['accreditor_score_id'])) {
					$accreditor = $this->getAccreditorDetails($locationAccreditorScore['accreditor_id']);
					
					if ($accreditor['score_type']=="numeric") {
						$subSections['Score_Type_Numeric'] = true;
						$mergeFields['Numeric_Score_Label'] = $this->phrase("[[name]]",["name" => $accreditor['name']]);
						$mergeFields['Numeric_Score'] = $this->getStars($locationAccreditorScore['score']);
					} elseif ($accreditor['score_type']=="alpha") {
					
					} elseif ($accreditor['score_type']=="boolean") {
						if ($locationAccreditorScore['score']=="Yes") {
							$subSections['Score_Type_Boolean'] = true;
							$mergeFields['Boolean_Score_Label'] = $this->phrase("[[name]] Award", ["name" => $accreditor['name']]);
						}
					}
				}
			}			
		}
		
		$this->framework('Outer', $mergeFields, $subSections);
	}
	
	function getStars($numberOfStars) {
		if ($numberOfStars) {
			$output = "";
		
			for ($i=0;$i<$numberOfStars;$i++) {
				$output .= "<div class=\"star_rating_image\"></div>";
			}
		}
		
		return $output;
	}
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		switch ($path) {
			case "zenario_location_manager__location":
				$locationAccreditationScoreIds = [];
				
				if ($box['key']['id']) {      
					if ($locationAccreditations = self::getLocationAccreditorScores($box['key']['id'])) {
						
						foreach ($locationAccreditations as $locationAccreditation) {
							$locationAccreditationScoreIds[] = $locationAccreditation['accreditor_score_id'];
						}
					}
					
                }

				if ($accreditors = $this->getAccreditors()) {
					$i = 2;

					foreach ($accreditors as $accreditor) {
						$scores = [];
					
						if ($accreditorScores = $this->getAccreditorScores($accreditor['id'])) {
							$scoreId = false;
							
							$scoreOrd = 1;
							
							foreach ($accreditorScores as $accreditorScore) {
								$scores[$accreditorScore['id']] = [
																			"label" => $accreditorScore['score'],
																			"ord" => $scoreOrd
																		];
								
								$scoreOrd++;
								
								if (is_array($locationAccreditationScoreIds) && in_array($accreditorScore['id'],$locationAccreditationScoreIds)) {
									$scoreId = $accreditorScore['id'];
								}
							}
						}

						$box['tabs']['zenario_location_ratings__accreditation']['fields']['accreditor_' . $accreditor['id']] = [
																																		"ord" => $i,
																																		"label" => $accreditor['name'] . ":",
																																		"type" => "select",
																																		"values" => $scores,
																																		"value" => $scoreId,
																																		"empty_value" => " -- Select a Rating -- "
																																	];
																																
						$i++;
					}
				}

				
				break;
			case "zenario_location_manager__locations_multiple_edit":
				if ($box['key']['id']) {      
					$locationAccreditationScoreIds = [];
					$locationAccreditationScoreIdsFinal = [];
					
					$locationIds = explode(",",$box['key']['id']);
					
					foreach ($locationIds as $locationId) {     
						if ($locationAccreditations = self::getLocationAccreditorScores($locationId)) {
							foreach ($locationAccreditations as $locationAccreditation) {
								if (!ze\ray::issetArrayKey($locationAccreditationScoreIds,$locationAccreditation['accreditor_score_id'])) {
									$locationAccreditationScoreIds[$locationAccreditation['accreditor_score_id']] = 1;
								} else {
									$locationAccreditationScoreIds[$locationAccreditation['accreditor_score_id']]++;
								}
							}
						}
					}
					
					foreach ($locationAccreditationScoreIds as $key => $value) {
						if ($value==sizeof($locationIds)) {
							$locationAccreditationScoreIdsFinal[] = $key;
						}
					}

					if ($accreditors = $this->getAccreditors()) {
						$i = 2;
	
						foreach ($accreditors as $accreditor) {
							$scores = [];
						
							if ($accreditorScores = $this->getAccreditorScores($accreditor['id'])) {
								$scoreId = false;
							
								foreach ($accreditorScores as $accreditorScore) {
									$scores[$accreditorScore['id']] = $accreditorScore['score'];
									
									if (is_array($locationAccreditationScoreIdsFinal) && in_array($accreditorScore['id'],$locationAccreditationScoreIdsFinal)) {
										$scoreId = $accreditorScore['id'];
									}
								}
							}
	
							$box['tabs']['zenario_location_ratings__accreditation']['fields']['accreditor_' . $accreditor['id']] = [
																																			"ord" => $i,
																																			"label" => $accreditor['name'] . ":",
																																			"type" => "select",
																																			"values" => $scores,
																																			"value" => $scoreId,
																																			"empty_value" => " -- Select a Rating -- ",
																																			"multiple_edit" => ["exists" => true]
																																		];
																																	
							$i++;
						}
					}
				}
				
				break;
			case "zenario_location_ratings__accreditor":
				if (ze\ray::issetArrayKey($box,'key','id')) {      
					$accreditor = self::getAccreditorDetails($box['key']['id']);
					
					$box['title'] = "Viewing/Editing the Accreditor \"" . $accreditor['name'] . "\"";
					
					$box['tabs']['accreditor']['fields']['name']['value'] = $accreditor['name'];
					$box['tabs']['accreditor']['fields']['score_type']['value'] = $accreditor['score_type'];
  				}
				
				break;
			case "zenario_location_ratings__accreditor_rating":
				$box['key']['accreditor_id'] = $_GET["refiner__zenario_location_ratings__accreditor"] ?? false;
			
				if (ze\ray::issetArrayKey($box,'key','id')) {      
					$accreditorScore = self::getAccreditorScore($box['key']['id']);
					
					$box['title'] = "Viewing/Editing the Accreditor Rating \"" . $accreditorScore['score'] . "\"";
					
					$box['tabs']['accreditor_rating']['fields']['rating']['value'] = $accreditorScore['score'];
 				}
				
				break;
		}
	}

	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		switch ($path) {
			case "zenario_location_ratings__accreditor":
				if (!ze\ray::issetArrayKey($values,'accreditor/name')) {
					$box['tabs']['accreditor']['errors'][] = "Error. You must enter a Name";
				} else {
					$sql = "SELECT id
							FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors
							WHERE name = '" . ze\escape::sql($values['accreditor/name']) . "'";
							
					if (ze\ray::issetArrayKey($box,"key","id")) {
						$sql .= " AND id <> " . (int) $box['key']['id'];
					}
					
					$result = ze\sql::select($sql);
					
					if (ze\sql::numRows($result)>0) {
						$box['tabs']['accreditor']['errors'][] = "Error. You must enter a unique Name";
					}
				}

				break;
			case "zenario_location_ratings__accreditor_rating":
				if (!ze\ray::issetArrayKey($values,'accreditor_rating/rating')) {
					$box['tabs']['accreditor_rating']['errors'][] = "Error. You must enter a Rating";
				} else {
					$sql = "SELECT id
							FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores
							WHERE score = '" . ze\escape::sql($values['accreditor_rating/rating']) . "'
								AND accreditor_id = " . (int) ($box["key"]["accreditor_id"] ?? false);
							
					if (ze\ray::issetArrayKey($box,"key","id")) {
						$sql .= " AND id <> " . (int) $box['key']['id'];
					}
					
					$result = ze\sql::select($sql);
					
					if (ze\sql::numRows($result)>0) {
						$box['tabs']['accreditor_rating']['errors'][] = "Error. You must enter a unique Rating";
					}
				}
			
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__location":
				if (ze\ring::engToBoolean($box['tabs']['zenario_location_ratings__accreditation']['edit_mode']['on'] ?? false)) {
					if ($accreditors = $this->getAccreditors()) {
						$newAccreditationScores = [];
					
						foreach ($accreditors as $accreditor) {
							if (ze\ray::issetArrayKey($values,'zenario_location_ratings__accreditation/accreditor_' . $accreditor['id'])) {
								$newAccreditationScores[] = ["accreditor_id" => $accreditor['id'], "score" => ze\ray::value($values,'zenario_location_ratings__accreditation/accreditor_' . $accreditor['id'])];
							}
						}
						
						if (sizeof($newAccreditationScores)>0) {
							$this->updateAccreditationScores($box['key']['id'],$newAccreditationScores);
						}
					}

				}
			
				break;
			case "zenario_location_manager__locations_multiple_edit":
				if (ze\ring::engToBoolean($box['tabs']['zenario_location_ratings__accreditation']['edit_mode']['on'] ?? false)) {
					if ($accreditors = $this->getAccreditors()) {
						$newAccreditationScores = [];

						$locationIds = explode(",",$box['key']['id']);
			
						foreach ($accreditors as $accreditor) {
							if ($changes['zenario_location_ratings__accreditation/accreditor_' . $accreditor['id']]==1) {
								foreach ($locationIds as $locationId) {
									if (ze\ray::issetArrayKey($values,'zenario_location_ratings__accreditation/accreditor_' . $accreditor['id'])) {
										$sql = "REPLACE INTO " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
												SET location_id = " . (int) $locationId . ",
													accreditor_score_id = " . (int) $values['zenario_location_ratings__accreditation/accreditor_' . $accreditor['id']];
										
										$result = ze\sql::update($sql);
									} else {
										$sql = "DELETE lasl
												FROM " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link AS lasl
												INNER JOIN " . DB_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores AS acs
													ON lasl.accreditor_score_id = acs.id
												WHERE lasl.location_id = " . (int) $locationId . "
													AND acs.accreditor_id = " . (int) $accreditor['id'];
										
										$result = ze\sql::update($sql);
									}
								}
							}
						}
					}					
				}				
			
				break;
			case "zenario_location_ratings__accreditor":
				if (ze\ray::issetArrayKey($box,"key","id")) {
					$oldAccreditorDetails = $this->getAccreditorDetails($box['key']['id']);

					ze\row::update(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors",["name" => ($values['accreditor/name'] ?? false),"score_type" => ($values['accreditor/score_type'] ?? false)],["id" => $box['key']['id']]);	
					
					$newAccreditorDetails = $this->getAccreditorDetails($box['key']['id']);
					
					if ($newAccreditorDetails['score_type']!=$oldAccreditorDetails['score_type']) {
						$this->prepopulateAccreditorScores($box['key']['id']);
					}
				} else {
					$accreditorId = ze\row::insert(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors",["name" => ($values['accreditor/name'] ?? false),"score_type" => ($values['accreditor/score_type'] ?? false)]);	
					$this->prepopulateAccreditorScores($accreditorId);
				}
				
				break;
			case "zenario_location_ratings__accreditor_rating":
				if (ze\ray::issetArrayKey($box,"key","id")) {
					ze\row::update(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores",["score" => ($values['accreditor_rating/rating'] ?? false),"accreditor_id" => ($box["key"]["accreditor_id"] ?? false)],["id" => $box['key']['id'],"accreditor_id" => $box['key']['accreditor_id']]);	
				} else {
					ze\row::insert(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores",["score" => ($values['accreditor_rating/rating'] ?? false),"accreditor_id" => ($box["key"]["accreditor_id"] ?? false)]);	
				}
				
				break;
		}
	}
}

?>