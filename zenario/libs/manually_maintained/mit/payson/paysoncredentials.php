<?php

/*
 * Container class for credentials used to log in via Payson API.
 */

class PaysonCredentials {

    protected $userId;
    protected $password;
    protected $applicationId;
    protected $moduleInfo;

    /**
     * Sets up a PaysonCredential object
     *
     * @param  string $userId API user id 
     * @param  string $password API password
     * @param null $applicationId
     * @param string $moduleInfo version of library
     */
    public function __construct($userId, $password, $applicationId = null, $moduleInfo = 'PaysonIntegrationPHP|1.1|NONE') {
        $this->userId = $userId;
        $this->password = $password;
        $this->applicationId = $applicationId;
        $this->moduleInfo = $moduleInfo;
    }

    public function UserId() {
        return $this->userId;
    }

    public function Password() {
        return $this->password;
    }

    public function ApplicationId() {
        return $this->applicationId;
    }

    public function ModuleInfo() {
        return $this->moduleInfo;
    }

    public function toHeader() {
        return array(
            'PAYSON-SECURITY-USERID:   ' . $this->UserId(),
            'PAYSON-SECURITY-PASSWORD: ' . $this->Password(),
            'PAYSON-APPLICATION-ID:    ' . $this->ApplicationId(),
            'PAYSON-MODULE-INFO:       ' . $this->ModuleInfo()
        );
    }

}

?>
