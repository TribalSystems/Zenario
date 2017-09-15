<?php

require_once "paymentdetails.php";

class ValidateResponse {

    protected $response;
    protected $paymentDetails;

    public function __construct($paymentDetails, $responseData) {
        $this->paymentDetails = new PaymentDetails($paymentDetails);

        $this->response = $responseData;
    }

    /**
     * Returns true if the request was verified by Payson
     *
     * @return bool
     */
    public function isVerified() {
        return $this->response == "VERIFIED";
    }

    /**
     * Returns the details about the payments.
     *
     * @return PaymentDetails
     */
    public function getPaymentDetails() {
        return $this->paymentDetails;
    }

}

?>