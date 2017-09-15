<?php

class AccountDetails {

    protected $accountEmail;
    protected $enabledForInvoice;
    protected $enabledForPaymentPlan;
    protected $merchantId;

    public function __construct($responseData) {

        if (isset($responseData["accountEmail"])) {
            $this->accountEmail = $responseData["accountEmail"];
        }

        if (isset($responseData["enabledForInvoice"])) {
            $this->enabledForInvoice = $responseData["enabledForInvoice"];
        }
        if (isset($responseData["enabledForPaymentPlan"])) {
            $this->enabledForPaymentPlan = $responseData["enabledForPaymentPlan"];
        }

        if (isset($responseData["merchantId"])) {
            $this->merchantId = $responseData["merchantId"];
        }

    }

    /**
     * Get payson account email
     *
     * @return string
     */
    public function getAccountEmail() {
        return $this->accountEmail;
    }

    /**
     * Get enable for invoice boolean
     *
     * @return bool
     */
    public function getEnabledForInvoice() {
        return $this->enabledForInvoice == "TRUE" ? true : false;
    }

    /**
     * Get enable for paymentplan boolean
     *
     * @return bool
     */
    public function getEnabledForPaymentPlan() {
        return $this->enabledForPaymentPlan == "TRUE" ? true : false;
    }
    
    /**
     * Get agent id
     *
     * @return string
     */
    public function getAgentId() {
        return $this->merchantId;
    }


    public function __toString() {
       
        $returnData = "agentid:\t\t " . $this->merchantId . "\n" .
                "accountEmail:\t\t " . $this->accountEmail . "\n" .
                "enabledForInvoice:\t\t " . $this->enabledForInvoice . "\n" .
                "enabledForPaymentPlan:\t " . $this->enabledForPaymentPlan;
        return $returnData;
    }

}

?>