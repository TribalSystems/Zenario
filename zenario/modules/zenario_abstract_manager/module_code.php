<?php
/*
 * Copyright (c) 2018, Tribal Limited
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



class zenario_abstract_manager extends ze\moduleBaseClass {
	
	protected static $dsInfo = [
		'label' => '',
		'tablePrefix' => '',
		'tableName' => '',
		'adminBoxPath' => '',
		'organizerPanelPath' => '',
		'viewPriv' => '',
		'managePriv' => ''
	];
	
	public static function returnDsInfo() {
		return static::$dsInfo = static::$dsInfo;
	}
	
	public static function loadDsInfoFrom($moduleClass) {
		if (class_exists($moduleClass)
		 && method_exists($moduleClass, 'returnDsInfo')) {
			static::$dsInfo = $moduleClass::returnDsInfo();
		}
	}
	
	
	protected static function table() {
		return static::$dsInfo['tablePrefix']. static::$dsInfo['tableName'];
	}
	
	public static function createDatasetTableAndRegisterDataset() {
		
		$tableCreated =
			ze\row::cacheTableDef(DB_NAME_PREFIX. static::table(), $checkExists = true);
		
		if (!$tableCreated) {
			ze\sql::update("
				CREATE TABLE `". ze\escape::sql(DB_NAME_PREFIX. static::table()). "` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`record_name` varchar(255) CHARACTER SET utf8mb4 NOT NULL default '',
					PRIMARY KEY (`id`),
					KEY (`record_name`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8
			");
		}
		
		$datasetId = ze\datasetAdm::register(
			static::$dsInfo['label'],
			static::table(), $system_table = '',
			static::$dsInfo['adminBoxPath'], static::$dsInfo['organizerPanelPath'],
			static::$dsInfo['viewPriv'], static::$dsInfo['managePriv']);
		
		if (!$tableCreated) {
			ze\row::set(
				'custom_dataset_tabs',
				[
					'ord' => 1,
					'label' => 'Details'],
				[
					'dataset_id' => $datasetId,
					'name' => '__custom_tab_1']
			);
			
			$fieldId =
				ze\row::set(
					'custom_dataset_fields',
					[
						'tab_name' => '__custom_tab_1',
						'ord' => 1,
						'label' => 'Name:',
						'type' => 'text',
						'field_name' => 'record_name',
						'protected' => 1,
						'create_index' => 1,
						'searchable' => 1,
						'sortable' => 1,
						'organizer_visibility' => 'always_show',
						'include_in_export' => 1,
						'required' => 1,
						'required_message' => 'Please enter a name'],
					[
						'dataset_id' => $datasetId,
						'db_column' => 'record_name']
				);
			
			ze\row::update('custom_datasets', ['label_field_id' => $fieldId], $datasetId);
		}
	}
	
	public static function getDatasetId() {
		return ze\row::get('custom_datasets', 'id', ['table' => static::table()]);
	}
	
	public static function getDatasetDetails() {
		return ze\row::get('custom_datasets', true, ['table' => static::table()]);
	}
	
	
	protected static function loadDatasetFieldsDetails() {
		return ze\row::getArray(
				'custom_dataset_fields',
				['id', 'type', 'db_column', 'dataset_foreign_key_id', 'values_source'],
				['dataset_id' => static::getDatasetId(), 'is_system_field' => 0]);
	}
	
	
	protected static function formatRecord($id, &$values, &$ids, $dateFormat = false, $cFields = false) {
		
		if (!$cFields) {
			$cFields = static::loadDatasetFieldsDetails();
		}
		
		foreach ($cFields as $cfield) {
			
			$col = $cfield['db_column'];
			
			if (!isset($values[$col])) {
				$values[$col] = '';
			}

			switch ($cfield['type']) {
				case 'editor':
					break;

				case 'group':
				case 'checkbox':
					break;

				case 'date':
					if ($values[$col]) {
						$ids[$col] = $values[$col];
						$values[$col] = ze\date::format($values[$col], $dateFormat);
					}
					break;

				case 'checkboxes':
					//For checkboxes, there could be multiple values, so pass an array of ids => values
					$ids[$col] = ze\row::getArray(
						'custom_dataset_values_link',
						'value_id',
						['dataset_id' => static::getDatasetId(), 'linking_id' => $id]);
				
					if (empty($ids[$col])) {
						$values[$col] = [];
					} else {
						$values[$col] = ze\row::getArray(
							'custom_dataset_field_values',
							'label',
							['field_id' => $cfield['id'], 'id' => $ids[$col]],
							'ord');
					}
					break;

				case 'radios':
				case 'select':
					if ($values[$col]) {
						$ids[$col] = $values[$col];
						$values[$col] = ze\row::get(
							'custom_dataset_field_values',
							'label',
							['field_id' => $cfield['id'], 'id' => $ids[$col]]);
					}
					break;

				case 'centralised_radios':
				case 'centralised_select':
					if ($values[$col] && !empty($cfield['values_source'])) {
						$ids[$col] = $values[$col];
						$values[$col] = ze\dataset::centralisedListValue($cfield['values_source'], $ids[$col]);
						
						if (is_array($values[$col])
						 && isset($values[$col]['label'])) {
							$values[$col] = $values[$col]['label'];
						}
					}
					break;

				//Handle links to other datasets
				case 'dataset_select':
				case 'dataset_picker':
					if ($values[$col]) {
						$ids[$col] = $values[$col];
						if ($labelDetails = ze\dataset::labelFieldDetails($cfield['dataset_foreign_key_id'])) {
							$values[$col] = ze\row::get($labelDetails['table'], $labelDetails['db_column'], $ids[$col]);
						}
					}
					break;
			}
		}
	
	}
	
	
	
	
	  /////////////////////////////////////
	 //  Methods called by Admin Boxes  //
	/////////////////////////////////////
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		
		//...your PHP code...//
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path != static::$dsInfo['adminBoxPath']) return;
		
		//If the hide_tab_bar property is not set in TUIX,
		//try to inteligently work out what it should be
		if (!isset($box['hide_tab_bar'])) {
			$box['hide_tab_bar'] = true;
			
			$numberOfTabs = 0;
			if (!empty($box['tabs'])
			 && is_array($box['tabs'])) {
				foreach ($box['tabs'] as &$tab) {
					if (!empty($tab)
					 && is_array($tab)) {
						if (++$numberOfTabs > 1) {
							$box['hide_tab_bar'] = false;
							break;
						}
					}
				}
			}
		}
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		
		//...your PHP code...//
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if ($path != static::$dsInfo['adminBoxPath']) return;
		
		if (!$box['key']['id']) {
			$box['key']['id'] = ze\db::getNextAutoIncrementId(static::table());
		}
	}
	
	public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		//...your PHP code...//
	}
	
	
	
	
	  ///////////////////////////////////
	 //  Methods called by Organizer  //
	///////////////////////////////////
	
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != static::$dsInfo['organizerPanelPath']) return;
		
		if (empty($panel['db_items'])) {
			$panel['db_items'] = [];
		}
		if (empty($panel['db_items']['table'])) {
			$panel['db_items']['table'] = '`'. ze\escape::sql(DB_NAME_PREFIX. static::table()). '` AS cd';
		}
		if (empty($panel['db_items']['id_column'])) {
			$panel['db_items']['id_column'] = 'cd.id';
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != static::$dsInfo['organizerPanelPath']) return;
		

		//If the import/export buttons are in the system, make sure they point to the correct dataset
		if ($datasetId = static::getDatasetId()) {
			if (!empty($panel['collection_buttons'])
			 && is_array($panel['collection_buttons'])) {
				foreach ($panel['collection_buttons'] as &$button) {
					if (isset($button['admin_box']['key']['dataset'])
					 && empty($button['admin_box']['key']['dataset'])) {
						$button['admin_box']['key']['dataset'] = $datasetId;
					}
				}
			}
		}
	}

	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if ($path != static::$dsInfo['organizerPanelPath']) return;
		
		if (($_POST['delete'] ?? false) && (!static::$dsInfo['managePriv'] || ze\priv::check(static::$dsInfo['managePriv']))) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $id) {
				ze\row::delete(static::table(), $id);
			}
		}
	}

}