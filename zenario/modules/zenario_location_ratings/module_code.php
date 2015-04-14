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

class zenario_location_ratings extends zenario_location_manager {
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path=="zenario__locations/nav/accreditors/panel") {
			foreach ($panel['items'] as $id => &$item) {
				$accreditorDetails = $this->getAccreditorDetails($id);
				
				if ($accreditorDetails['score_type']!="boolean") {
					$item['link']['path'] = "zenario__locations/nav/accreditors/panel/hidden_nav/accreditor_scores/panel";
					$item['link']['branch'] = "Yes";
					$item['link']['refiner'] = "zenario_location_ratings__accreditor";
					$item['tooltip'] = adminPhrase("View this Accreditor's ratings");
				}
			}
		} elseif ($path=="zenario__locations/nav/accreditors/panel/hidden_nav/accreditor_scores/panel") {
			$accreditorDetails = $this->getAccreditorDetails($refinerId);
			
			$panel['title'] = "Ratings for the Accreditor \"" . $accreditorDetails['name'] . "\"";
		}
	}

	function setAdminFloatingBoxTitleAndValidatePrimaryKey($floatingBoxName, &$primaryKey, &$title) {
		switch ($floatingBoxName){
			case 'Create a new Accreditor':
				$title = "Creating an Accreditor";
				break;
			case 'Edit Accreditor':
				$sector = $this->getAccreditorDetails($primaryKey['id']);
				$title = 'Viewing/Editing the Accreditor "'. $sector['name'] . '"';
				break;
		}
	}

	function showAdminFloatingBoxTab($targetClassName, $floatingBoxName, $tabLabel, $primaryKey, $editMode, &$showEditButton) {
		switch ($floatingBoxName) {
			case 'Edit Location':
				$locationValues = $this->getLocationDetails($primaryKey['id']);
				
				if ($locationAccreditations = self::getLocationAccreditorScores($primaryKey['id'])) {
					$locationAccreditationScoreIds = array();
					
					foreach ($locationAccreditations as $locationAccreditation) {
						$locationAccreditationScoreIds[] = $locationAccreditation['accreditor_score_id'];
					}
				}
			case 'Create a new Location':
				if ($tabLabel=="Accreditation") {
					$output = "<tr>
									<td colspan=\"2\">Use this screen to set Accreditation Scores against this Location</td>
								</tr>\n";
					
					if ($editMode) {
						if ($accreditors = $this->getAccreditors()) {
							foreach ($accreditors as $accreditor) {
								$output .= "<tr>
												<td><b>" . $accreditor['name'] . ":</b></td>
												<td>
													<select id=\"accreditor_" . $accreditor['id'] . "\">";
													
								if ($accreditor['score_type']=="numeric") {					
									$output .= "			<option value=\"\"> -- Select a Star Rating -- </option>\n";
									$scoreSuffix = " Star(s)";
								} elseif ($accreditor['score_type']=="alpha") {
									$scoreSuffix = "";
								} elseif ($accreditor['score_type']=="boolean") {
									$scoreSuffix = "";
								}

								if ($accreditorScores = $this->getAccreditorScores($accreditor['id'])) {
									foreach ($accreditorScores as $accreditorScore) {
										if (is_array($locationAccreditationScoreIds) && in_array($accreditorScore['id'],$locationAccreditationScoreIds)) {
											$selected = " selected=\"selected\"";
										} else {
											$selected = "";
										}
									
										$output .= "<option value=\"" . $accreditorScore['id'] . "\"" . $selected . ">" . $accreditorScore['score'] . $scoreSuffix . "</option>\n";
									}
								}

								$output .= "		</select>
												</td>
											</tr>\n";
							}
						}
					} else {
						if ($locationAccreditations = self::getLocationAccreditorScores($primaryKey['id'])) {
							foreach ($locationAccreditations as $locationAccreditation) {
								$accreditorScore = self::getAccreditorAndScoreFromScoreId($locationAccreditation['accreditor_score_id']);
							
								$accreditor = $this->getAccreditorDetails($accreditorScore['accreditor_id']);
								
								$output .= "<tr>
												<td><b>" . $accreditor['name'] . ":</b></td>
												<td>" . $accreditorScore['score'] . "</td>
											</tr>\n";
							}
						}
					}
					
					echo $output;
				}
				
				break;
			case 'Edit Accreditor':
				$accreditorValues = $this->getAccreditorDetails($primaryKey['id']);
			case 'Create a new Accreditor':
				if ($tabLabel=="Details") {
					$accreditorFields= array(
												'name' => array('Label' => 'Name:','Field' => 'name','Type' => 'varchar(255)',
												'Null' => 'NO','Table' =>  ZENARIO_LOCATION_MANAGER_PREFIX . 'accreditors','Numeric' => false),
												'score_type' => array('Label' => 'Score Type:','Field' => 'score_type','Type' => 'select','Options' => array('numeric' => 'Numeric','alpha' => 'Alpha','boolean' => 'Boolean'),
												'Null' => 'NO','Table' =>  ZENARIO_LOCATION_MANAGER_PREFIX . 'accreditors','Numeric' => false)
										);
					
					echo dynamicThickboxFormInner($editMode,$accreditorFields,
													   array(),
													   $_POST,
													   $accreditorValues,array(),array());
				}
				break;
			case 'Edit Accreditor Score':
				$accreditorScoreValues = $this->getAccreditorDetails($primaryKey['id']);
			case 'Create a new Accreditor Score':
				if ($tabLabel=="Details") {
					$accreditorFields= array(
												'name' => array('Label' => 'Name:','Field' => 'name','Type' => 'varchar(255)',
												'Null' => 'NO','Table' =>  ZENARIO_LOCATION_MANAGER_PREFIX . 'accreditors','Numeric' => false)
										);
					
					echo dynamicThickboxFormInner($editMode,$accreditorFields,
													   array(),
													   $_POST,
													   $accreditorValues,array(),array());
				}
				break;
		}
	}

	public function validateAdminFloatingBoxTab($targetClassName, $floatingBoxName, $tabLabel, $primaryKey) {
		$errArray = array();
		
		switch ($floatingBoxName){
			case 'Create a new Accreditor':
			case 'Edit Accreditor':
				if ($tabLabel=="Details") {
					if (($rv=$this->addOrUpdateAccreditorCheck(
																$floatingBoxName,
																$tabLabel,
																$primaryKey['id'],
																post('name')
															))){
						$errArray[] = htmlspecialchars($rv);
					}
				}
				break;
		}
		
		echo json_encode(array('valid' => !count($errArray), 'errArray' => $errArray));
	}

	public function saveAdminFloatingBoxTab($targetClassName, $floatingBoxName, $tabLabel, $primaryKey) {
		$errArray = array();
		
		switch ($floatingBoxName){
			case 'Create a new Accreditor':
			case 'Edit Accreditor':
					$id = $this->addOrUpdateAccreditor(
													$floatingBoxName,
													$tabLabel,
													$primaryKey['id'],
													post('name'),
													post('score_type')
												);
					echo json_encode(array('id' => $id));
				break;
			case 'Create a new Location':
			case 'Edit Location':
				if ($tabLabel=="Accreditation") {
					if ($accreditors = $this->getAccreditors()) {
						$newAccreditationScores = array();
					
						foreach ($accreditors as $accreditor) {
							if (post('accreditor_' . $accreditor['id'])) {
								$newAccreditationScores[] = array("accreditor_id" => $accreditor['id'], "score" => post('accreditor_' . $accreditor['id']));
							}
						}
						
						if (sizeof($newAccreditationScores)>0) {
							$this->updateAccreditationScores($primaryKey['id'],$newAccreditationScores);
						}
					}
				}			
		}
	}

	
	function addOrUpdateAccreditor($floatingBoxName,$tab,$ID,$name,$scoreType){
		if (($rv=$this->addOrUpdateAccreditorCheck($floatingBoxName,$tab,$ID,$name))==''){
			if ($ID){
				$accreditor = $this->getAccreditorDetails($ID);
			
				$sql = 'UPDATE ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
				$sql .= 'SET ';
				
				if ($tab=="Details") {
					if ($name) {
						$sql .= "name = '" . sqlEscape($name) . "',";
					}
					
					if ($scoreType) {
						$sql .= "score_type = '" . sqlEscape($scoreType) . "'";
				
						if ($scoreType!=$accreditor['score_type']) {
							$updateScores = true;
						}
						
					}
				}
				
				if (substr($sql,-1)==",") {
					$sql = substr($sql,0,(strlen($sql)-1));
				}
				
				$sql .=' WHERE id=' . (int)$ID ;
				
				sqlQuery($sql);
				
				if ($updateScores) {
					$this->prepopulateAccreditorScores($ID);
				}
				
				return $ID;
			} else {
				$sql = 'INSERT INTO ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
				$sql .= '(`name`,`score_type`)';
				$sql .=' VALUES (\'' . sqlEscape($name) . '\',\'' . sqlEscape($scoreType) . '\')';

				sqlUpdate($sql);
				$accreditorId = sqlInsertId();
				
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
				FROM ". DB_NAME_PREFIX. ZENARIO_LOCATION_RATINGS_PREFIX. "accreditor_scores
				WHERE accreditor_id = ". (int) $accreditorId;
				
		$result = sqlQuery($sql);
	
		if ($accreditor['score_type']=="numeric") {
			$sql = 'INSERT INTO ' . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores (`accreditor_id`,`score`)
					VALUES (' . $accreditorId . ',1),(' . $accreditorId . ',2),(' . $accreditorId . ',3),(' . $accreditorId . ',4),(' . $accreditorId . ',5)';
					
			$result = sqlQuery($sql);
		} elseif ($accreditor['score_type']=="boolean") {
			$sql = 'INSERT INTO ' . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores (`accreditor_id`,`score`)
					VALUES (' . $accreditorId . ',\'Yes\'),(' . $accreditorId . ',\'No\')';
					
			$result = sqlQuery($sql);
		}
	}

	function checkAccreditorNameUnique ($name,$id) {
		$sql = "SELECT id
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors
				WHERE name = '" . sqlEscape($name) . "'";
		
		if ($id) {
			$sql .= " AND id <> " . (int) $id;
		}
		
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
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
				FROM ' . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores
				WHERE accreditor_id = ' . (int) $ID;
				
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchArray($result)) {
				$sql = 'DELETE 
						FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'location_accreditor_score_link 
						WHERE accreditor_score_id=' . (int) $row['id'];
						
				sqlQuery($sql);
			}
		}
		
		$sql = 'DELETE 
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores 
				WHERE accreditor_id=' . (int)$ID;
				
		sqlQuery($sql);
		
		$sql = 'DELETE 
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors 
				WHERE id=' . (int)$ID;
				
		sqlQuery($sql);
	}
	
	static function getAccreditors(){
		$rv = array();
		$sql = 'SELECT id,
					name,
					score_type
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
		if (sqlNumRows($result = sqlQuery($sql))>0){
			while ($row = sqlFetchArray($result)) {
				$rv[] = $row;
			}
		}
		return $rv;
	}

	static function getAccreditorDetails($ID){
		$rv = array();
		$sql = 'SELECT name,
					score_type
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditors ';
		$sql .= 'WHERE id = ' . (int) $ID;
		if (sqlNumRows($result = sqlQuery($sql))==1){
			$rv = sqlFetchAssoc($result);
		}
		return $rv;
	}

	static function getAccreditorScores($ID){
		$accreditor = self::getAccreditorDetails($ID);
	
		$scores = array();
		$sql = 'SELECT id,
					score
				FROM ' .DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . 'accreditor_scores ';
		$sql .= 'WHERE accreditor_id = ' . (int) $ID . '
				ORDER BY score';

		if ($accreditor['score_type']=="boolean") {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		$result = sqlQuery($sql);

		if (sqlNumRows($result)>0) {
			while ($row = sqlFetchArray($result)) {
				$scores[] = $row;
			}
		}

		return $scores;
	}

	static function getAccreditorScore($id){
		return getRow(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores",array("id","score"),array("id" => $id));
	}

	static function getLocationAccreditorScores ($locationId) {
		$sql = "SELECT id,
					location_id,
					accreditor_score_id
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
				WHERE location_id = " . (int) $locationId;
				
		$result = sqlQuery($sql);
				
		if (sqlNumRows($result)>0) {
			$locationAccreditorScores = array();
			
			while ($row = sqlFetchArray($result)) {
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
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores
				WHERE id = " . (int) $id;
				
		$result = sqlQuery($sql);
		
		if (sqlNumRows($result)>0) {
			$row = sqlFetchArray($result);
			
			return $row;
		} else {
			return false;
		}
	}

	function updateAccreditationScores ($locationId,$accreditations) {
		$sql = "DELETE
				FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
				WHERE location_id = " . (int) $locationId;
				
		$result = sqlQuery($sql);
		
		foreach ($accreditations as $accreditation) {
			$sql = "INSERT INTO " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
					SET location_id = " . (int) $locationId . ",
						accreditor_score_id = " . (int) $accreditation['score'];
						
			$result = sqlQuery($sql);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		switch ($path) {
			case 'zenario__locations/nav/accreditors/panel':
				switch (post('action')){
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
				switch (post('action')){
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
				FROM ". DB_NAME_PREFIX. ZENARIO_LOCATION_RATINGS_PREFIX. "accreditor_scores
				WHERE id = ". (int) $id;
				
		$result = sqlQuery($sql);
	}
	
	public function showSlot() {
		$locationId = $this->getLocationIdFromContentItem($this->cID,$this->cType);
		
		$mergeFields = array();
		$subSections = array();
		
		$mergeFields['Location_Rating_Title'] = $this->phrase("_LOCATION_RATING_TITLE");
		
		if ($locationAccreditorScoreIds = $this->getLocationAccreditorScores($locationId)) {
			foreach ($locationAccreditorScoreIds as $locationAccreditorScoreId) {
				if ($locationAccreditorScore = self::getAccreditorAndScoreFromScoreId($locationAccreditorScoreId['accreditor_score_id'])) {
					$accreditor = $this->getAccreditorDetails($locationAccreditorScore['accreditor_id']);
					
					if ($accreditor['score_type']=="numeric") {
						$subSections['Score_Type_Numeric'] = true;
						$mergeFields['Numeric_Score_Label'] = $this->phrase("_NUMERIC_LOCATION_SCORE",array("name" => $accreditor['name']));
						$mergeFields['Numeric_Score'] = $this->getStars($locationAccreditorScore['score']);
					} elseif ($accreditor['score_type']=="alpha") {
					
					} elseif ($accreditor['score_type']=="boolean") {
						if ($locationAccreditorScore['score']=="Yes") {
							$subSections['Score_Type_Boolean'] = true;
							$mergeFields['Boolean_Score_Label'] = $this->phrase("_BOOLEAN_LOCATION_SCORE", array("name" => $accreditor['name']));
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
				$locationAccreditationScoreIds = array();
				
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
						$scores = array();
					
						if ($accreditorScores = $this->getAccreditorScores($accreditor['id'])) {
							$scoreId = false;
							
							$scoreOrd = 1;
							
							foreach ($accreditorScores as $accreditorScore) {
								$scores[$accreditorScore['id']] = array(
																			"label" => $accreditorScore['score'],
																			"ord" => $scoreOrd
																		);
								
								$scoreOrd++;
								
								if (is_array($locationAccreditationScoreIds) && in_array($accreditorScore['id'],$locationAccreditationScoreIds)) {
									$scoreId = $accreditorScore['id'];
								}
							}
						}

						$box['tabs']['zenario_location_ratings__accreditation']['fields']['accreditor_' . $accreditor['id']] = array(
																																		"ord" => $i,
																																		"label" => $accreditor['name'] . ":",
																																		"type" => "select",
																																		"values" => $scores,
																																		"value" => $scoreId,
																																		"empty_value" => " -- Select a Rating -- "
																																	);
																																
						$i++;
					}
				}

				
				break;
			case "zenario_location_manager__locations_multiple_edit":
				if ($box['key']['id']) {      
					$locationAccreditationScoreIds = array();
					$locationAccreditationScoreIdsFinal = array();
					
					$locationIds = explode(",",$box['key']['id']);
					
					foreach ($locationIds as $locationId) {     
						if ($locationAccreditations = self::getLocationAccreditorScores($locationId)) {
							foreach ($locationAccreditations as $locationAccreditation) {
								if (!issetArrayKey($locationAccreditationScoreIds,$locationAccreditation['accreditor_score_id'])) {
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
							$scores = array();
						
							if ($accreditorScores = $this->getAccreditorScores($accreditor['id'])) {
								$scoreId = false;
							
								foreach ($accreditorScores as $accreditorScore) {
									$scores[$accreditorScore['id']] = $accreditorScore['score'];
									
									if (is_array($locationAccreditationScoreIdsFinal) && in_array($accreditorScore['id'],$locationAccreditationScoreIdsFinal)) {
										$scoreId = $accreditorScore['id'];
									}
								}
							}
	
							$box['tabs']['zenario_location_ratings__accreditation']['fields']['accreditor_' . $accreditor['id']] = array(
																																			"ord" => $i,
																																			"label" => $accreditor['name'] . ":",
																																			"type" => "select",
																																			"values" => $scores,
																																			"value" => $scoreId,
																																			"empty_value" => " -- Select a Rating -- ",
																																			"multiple_edit" => array("exists" => true)
																																		);
																																	
							$i++;
						}
					}
				}
				
				break;
			case "zenario_location_ratings__accreditor":
				if (issetArrayKey($box,'key','id')) {      
					$accreditor = self::getAccreditorDetails($box['key']['id']);
					
					$box['title'] = "Viewing/Editing the Accreditor \"" . $accreditor['name'] . "\"";
					
					$box['tabs']['accreditor']['fields']['name']['value'] = $accreditor['name'];
					$box['tabs']['accreditor']['fields']['score_type']['value'] = $accreditor['score_type'];
  				}
				
				break;
			case "zenario_location_ratings__accreditor_rating":
				$box['key']['accreditor_id'] = get("refiner__zenario_location_ratings__accreditor");
			
				if (issetArrayKey($box,'key','id')) {      
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
				if (!issetArrayKey($values,'accreditor/name')) {
					$box['tabs']['accreditor']['errors'][] = "Error. You must enter a Name";
				} else {
					$sql = "SELECT id
							FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors
							WHERE name = '" . sqlEscape($values['accreditor/name']) . "'";
							
					if (issetArrayKey($box,"key","id")) {
						$sql .= " AND id <> " . (int) $box['key']['id'];
					}
					
					$result = sqlQuery($sql);
					
					if (sqlNumRows($result)>0) {
						$box['tabs']['accreditor']['errors'][] = "Error. You must enter a unique Name";
					}
				}

				break;
			case "zenario_location_ratings__accreditor_rating":
				if (!issetArrayKey($values,'accreditor_rating/rating')) {
					$box['tabs']['accreditor_rating']['errors'][] = "Error. You must enter a Rating";
				} else {
					$sql = "SELECT id
							FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores
							WHERE score = '" . sqlEscape($values['accreditor_rating/rating']) . "'
								AND accreditor_id = " . (int) arrayKey($box,"key","accreditor_id");
							
					if (issetArrayKey($box,"key","id")) {
						$sql .= " AND id <> " . (int) $box['key']['id'];
					}
					
					$result = sqlQuery($sql);
					
					if (sqlNumRows($result)>0) {
						$box['tabs']['accreditor_rating']['errors'][] = "Error. You must enter a unique Rating";
					}
				}
			
				break;
		}
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path) {
			case "zenario_location_manager__location":
				if (engToBooleanArray($box, 'tabs', 'zenario_location_ratings__accreditation', 'edit_mode', 'on')) {
					if ($accreditors = $this->getAccreditors()) {
						$newAccreditationScores = array();
					
						foreach ($accreditors as $accreditor) {
							if (issetArrayKey($values,'zenario_location_ratings__accreditation/accreditor_' . $accreditor['id'])) {
								$newAccreditationScores[] = array("accreditor_id" => $accreditor['id'], "score" => arrayKey($values,'zenario_location_ratings__accreditation/accreditor_' . $accreditor['id']));
							}
						}
						
						if (sizeof($newAccreditationScores)>0) {
							$this->updateAccreditationScores($box['key']['id'],$newAccreditationScores);
						}
					}

				}
			
				break;
			case "zenario_location_manager__locations_multiple_edit":
				if (engToBooleanArray($box, 'tabs', 'zenario_location_ratings__accreditation', 'edit_mode', 'on')) {
					if ($accreditors = $this->getAccreditors()) {
						$newAccreditationScores = array();

						$locationIds = explode(",",$box['key']['id']);
			
						foreach ($accreditors as $accreditor) {
							if ($changes['zenario_location_ratings__accreditation/accreditor_' . $accreditor['id']]==1) {
								foreach ($locationIds as $locationId) {
									if (issetArrayKey($values,'zenario_location_ratings__accreditation/accreditor_' . $accreditor['id'])) {
										$sql = "REPLACE INTO " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link
												SET location_id = " . (int) $locationId . ",
													accreditor_score_id = " . (int) $values['zenario_location_ratings__accreditation/accreditor_' . $accreditor['id']];
										
										$result = sqlQuery($sql);
									} else {
										$sql = "DELETE lasl
												FROM " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "location_accreditor_score_link AS lasl
												INNER JOIN " . DB_NAME_PREFIX . ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores AS acs
													ON lasl.accreditor_score_id = acs.id
												WHERE lasl.location_id = " . (int) $locationId . "
													AND acs.accreditor_id = " . (int) $accreditor['id'];
										
										$result = sqlQuery($sql);
									}
								}
							}
						}
					}					
				}				
			
				break;
			case "zenario_location_ratings__accreditor":
				if (issetArrayKey($box,"key","id")) {
					$oldAccreditorDetails = $this->getAccreditorDetails($box['key']['id']);

					updateRow(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors",array("name" => arrayKey($values,'accreditor/name'),"score_type" => arrayKey($values,'accreditor/score_type')),array("id" => $box['key']['id']));	
					
					$newAccreditorDetails = $this->getAccreditorDetails($box['key']['id']);
					
					if ($newAccreditorDetails['score_type']!=$oldAccreditorDetails['score_type']) {
						$this->prepopulateAccreditorScores($box['key']['id']);
					}
				} else {
					$accreditorId = insertRow(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditors",array("name" => arrayKey($values,'accreditor/name'),"score_type" => arrayKey($values,'accreditor/score_type')));	
					$this->prepopulateAccreditorScores($accreditorId);
				}
				
				break;
			case "zenario_location_ratings__accreditor_rating":
				if (issetArrayKey($box,"key","id")) {
					updateRow(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores",array("score" => arrayKey($values,'accreditor_rating/rating'),"accreditor_id" => arrayKey($box,"key","accreditor_id")),array("id" => $box['key']['id'],"accreditor_id" => $box['key']['accreditor_id']));	
				} else {
					insertRow(ZENARIO_LOCATION_RATINGS_PREFIX . "accreditor_scores",array("score" => arrayKey($values,'accreditor_rating/rating'),"accreditor_id" => arrayKey($box,"key","accreditor_id")));	
				}
				
				break;
		}
	}
}

?>