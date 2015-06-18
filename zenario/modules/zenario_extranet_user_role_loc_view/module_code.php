<?php

class zenario_extranet_user_role_loc_view extends zenario_location_viewer {

	public function getLocationId() {
		$userId = false;
		$locationId = false;
	
		if (!$userId = $this->setting("user")) {
			$userId = self::getUserIdFromDescriptivePage($this->cID,$this->cType);
		}
		
		if ($userId && $this->setting("role")) {
			if ($locations = zenario_organization_manager::getUserRoleLocations($userId,$this->setting("role"))) {
				$locationId = $locations[0];
			}
		}
		return $locationId;
	}

	public static function getUserIdFromDescriptivePage($cID, $cType) {
		if ($cID && $cType) {
			if ($equivId = equivId($cID,$cType)) {
				return getRow("users","id",array("equiv_id" => $equivId, "content_type" => $cType));
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}

?>