<?php

require_once "responseenvelope.php";
require_once "accountdetails.php";

class AccountDetailsResponse {

    protected $responseEnvelope;
    protected $accountDetails;

    public function __construct($responseData) {
        $this->responseEnvelope = new ResponseEnvelope($responseData);
        $this->accountDetails = new AccountDetails($responseData);
    }

    /**
     * Returns the response envelope
     * 
     * @see ResponseEnvelope
     * 
     * @return ResponseEnvelope
     */
    public function getResponseEnvelope() {
        return $this->responseEnvelope;
    }

    /**
     * Returns the account details from the response
     * 
     * @return AccountDetails
     */
    public function getAccountDetails() {
        return $this->accountDetails;
    }

    /**
     * Returns a string representation of the account  details
     * 
     * @return string
     */
    public function __toString() {
        return $this->accountDetails->__toString();
    }

}

?>
