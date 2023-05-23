<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

class zenario_common_features__organizer__custom_fields_hierarchy extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
 		$dataset = ze\dataset::details($refinerId);
 		if ($dataset) {
 			$panel['title'] = ze\admin::phrase('Fields for the "[[label]]" dataset', $dataset);
 		} else {
 			$panel['title'] = ze\admin::phrase('Dataset fields');
 		}
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		// Populate panel with dataset fields
		$sql = "
			SELECT f.id, f.tab_name, f.dataset_id, f.label, f.default_label, f.field_name, f.db_column, f.type, f.parent_id
			FROM " . DB_PREFIX . "custom_dataset_fields f
			WHERE TRUE
			AND f.type NOT IN ('other_system_field', 'repeat_start')
			AND f.db_column != ''";
		
		// Refiners...
		if ($refinerName == "dataset_id") {
			$sql .= "
				AND f.dataset_id = " . (int)$refinerId;
		} elseif ($refinerName == "custom_fields_only") {
			$sql .= "
				AND f.dataset_id = " . (int)$refinerId . "
				AND f.is_system_field = 0
				AND f.type IN ('text', 'date', 'radios', 'checkbox', 'textarea', 'consent', 'group', 'centralised_radios', 'url', 'select', 'dataset_select', 'centralised_select')";
		} elseif ($refinerName == "user_fields") {
			$sql .= "
				AND f.dataset_id = (
					SELECT cd.id
					FROM " . DB_PREFIX . "custom_datasets AS cd
					WHERE cd.table = 'users_custom_data'
				)";
		} elseif ($refinerName == "user_custom_fields_only") {
			$sql .= "
				AND f.dataset_id = (
					SELECT cd.id
					FROM " . DB_PREFIX . "custom_datasets AS cd
					WHERE cd.table = 'users_custom_data'
				)
				AND f.is_system_field = 0 
				AND f.type in ('text', 'date', 'radios', 'checkbox', 'textarea', 'consent', 'group', 'centralised_radios', 'url', 'select', 'dataset_select', 'centralised_select')";
		} elseif ($refinerName == "exclude_image_pickers") {
			$sql .= "
				AND f.dataset_id = " . (int)$refinerId . "
				AND f.type NOT IN ('file_picker')";
		}
		
		//This panel manually loads items from the database, rather than using Organizer's
		//standard tech. So the typeahead search won't work unless we try to manually
		//implement it as well.
		if ($mode == 'typeahead_search') {
			$sql .= "
				AND (f.label LIKE '%". ze\escape::like(ze::request('_search')). "%'
				  OR f.field_name LIKE '%". ze\escape::like(ze::request('_search')). "%')";
		}
		
		$sql .= "
			ORDER BY f.ord";
		
		$result = ze\sql::select($sql);
		
		$datasetTabs = [];
		while ($row = ze\sql::fetchAssoc($result)) {
			if ($row["tab_name"]) {
				
				if (!isset($datasetTabs[$row["dataset_id"]])) {
					$datasetTabs[$row["dataset_id"]] = [];
				}
				$datasetTabs[$row["dataset_id"]][] = $row["tab_name"];
				
				// Try and get a readable label for the field (important in pickers)
				if (!$row["label"]) {
					$row["label"] = $row["default_label"];
				}
				if (!$row["label"]) {
					$row["label"] = $row["field_name"];
				}
				if (!$row["label"]) {
					$row["label"] = $row["db_column"];
				}
				$row["label"] = trim($row["label"], " :");
				$row["is_field"] = true;
				$row["parent_id"] = $row["parent_id"] ? $row["parent_id"] : $this->getDatasetTabPanelId($row["dataset_id"], $row["tab_name"]);
				$row["css_class"] = "zenario_dataset_field_" . $row["type"];
				$panel["items"][$row["id"]] = $row;
			}
		}
		
		//Try to add the tab headings, unless this is the typeahead search which sholud just be a flat list
		if ($mode != 'typeahead_search') {
			$showDatasets = count($datasetTabs) > 1;
			foreach ($datasetTabs as $datasetId => $tabs) {
				// If the panel shows fields from more than one dataset, add another level to the hierarchy
				if ($showDatasets) {
					$dataset = ze\dataset::details($datasetId);
					$panel["items"][$this->getDatasetPanelId($datasetId)] = ["label" => $dataset["label"]];
				}
			
				// Add tabs to hierarchy
				$sql = "
					SELECT name, label, default_label
					FROM " . DB_PREFIX . "custom_dataset_tabs
					WHERE dataset_id = " . (int)$datasetId . "
					AND name IN (" . ze\escape::in($tabs) . ")";
				$result = ze\sql::select($sql);
				while ($row = ze\sql::fetchAssoc($result)) {
					$label = $row["label"] ? $row["label"] : $row["default_label"];
				
					$item = [];
					$item["label"] = $label;
					if ($showDatasets) {
						$item["parent_id"] = $this->getDatasetPanelId($datasetId);
					}
				
					$panel["items"][$this->getDatasetTabPanelId($datasetId, $row["name"])] = $item;
				}
			}
		}
		
	}
	
	
	public function getDatasetTabPanelId($datasetId, $tabId) {
		return $this->getDatasetPanelId($datasetId) . "_tab__" . $tabId;
	}
	
	public function getDatasetPanelId($datasetId) {
		return "dataset__" . $datasetId;
	}
	
}