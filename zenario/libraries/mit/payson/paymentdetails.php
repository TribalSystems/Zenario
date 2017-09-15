<?php

class PaymentDetails {

    protected $orderItems;
    protected $receivers;
    protected $token;
    protected $status;
    protected $invoiceStatus;
    protected $guaranteeStatus;
    protected $guaranteeDeadlineTimestamp;
    protected $type;
    protected $currencyCode;
    protected $custom;
    protected $trackingId;
    protected $correlationId;
    protected $purchaseId;
    protected $senderEmail;
    protected $receiverFee;
    protected $shippingAddressName;
    protected $shippingAddressStreetAddress;
    protected $shippingAddressPostalCode;
    protected $shippingAddressCity;
    protected $shippingAddressCountry;

    public function __construct($responseData) {
        $this->orderItems = OrderItem::parseOrderItems($responseData);
        $this->receivers = Receiver::parseReceivers($responseData);

        if (isset($responseData["token"])) {
            $this->token = $responseData["token"];
        }

        if (isset($responseData["status"])) {
            $this->status = $responseData["status"];
        }
        if (isset($responseData["invoiceStatus"])) {
            $this->invoiceStatus = $responseData["invoiceStatus"];
        }

        if (isset($responseData["guaranteeStatus"])) {
            $this->guaranteeStatus = $responseData["guaranteeStatus"];
        }

        if (isset($responseData["guaranteeDeadlineTimestamp"])) {
            $this->guaranteeDeadlineTimestamp = $responseData["guaranteeDeadlineTimestamp"];
        }

        if (isset($responseData["shippingAddress.name"])) {
            $this->shippingAddressName = $responseData["shippingAddress.name"];
        }
        if (isset($responseData["shippingAddress.streetAddress"])) {
            $this->shippingAddressStreetAddress = $responseData["shippingAddress.streetAddress"];
        }
        if (isset($responseData["shippingAddress.postalCode"])) {
            $this->shippingAddressPostalCode = $responseData["shippingAddress.postalCode"];
        }
        if (isset($responseData["shippingAddress.city"])) {
            $this->shippingAddressCity = $responseData["shippingAddress.city"];
        }
        if (isset($responseData["shippingAddress.country"])) {
            $this->shippingAddressCountry = $responseData["shippingAddress.country"];
        }

        if (isset($responseData["receiverFee"])) {
            $this->receiverFee = $responseData["receiverFee"];
        }

        if (isset($responseData["type"])) {
            $this->type = $responseData["type"];
        }

        if (isset($responseData["currencyCode"])) {
            $this->currencyCode = $responseData["currencyCode"];
        }

        if (isset($responseData["custom"])) {
            $this->custom = $responseData["custom"];
        }
        if (isset($responseData["trackingId"])) {
            $this->trackingId = $responseData["trackingId"];
        }
        if (isset($responseData["correlationId"])) {
            $this->correlationId = $responseData["correlationId"];
        }
        if (isset($responseData["purchaseId"])) {
            $this->purchaseId = $responseData["purchaseId"];
        }

        if (isset($responseData["senderEmail"])) {
            $this->senderEmail = $responseData["senderEmail"];
        }
    }

    /**
     * Get array of OrderItem objects
     *
     * @return array
     */
    public function getOrderItems() {
        return $this->orderItems;
    }

    /**
     * Get array of Receiver objects
     *
     * @return array
     */
    public function getReceivers() {
        return $this->receivers;
    }

    /**
     *
     * @return
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * Get status of the payment
     *
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Get type of the payment
     *
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Get currency code of the payment
     *
     * @return string
     */
    public function getCurrencyCode() {
        return $this->currencyCode;
    }

    /**
     * Get custom field of the payment
     *
     * @return string
     */
    public function getCustom() {
        return $this->custom;
    }

    /**
     * Get trackingId field of the payment
     *
     * @return string
     */
    public function getTrackingId() {
        return $this->trackingId;
    }

    /**
     * Get the correlation id for the payment
     *
     * @return
     */
    public function getCorrelationId() {
        return $this->correlationId;
    }

    /**
     * Get purchase id for the payment
     *
     * @return
     */
    public function getPurchaseId() {
        return $this->purchaseId;
    }

    /**
     * Get email address of the sender of the payment
     *
     * @return
     */
    public function getSenderEmail() {
        return $this->senderEmail;
    }

    /**
     * Get the detailed status of an invoice payment
     *
     * @return
     */
    public function getInvoiceStatus() {
        return $this->invoiceStatus;
    }

    /**
     * Get the detailed status of an guarantee payment
     *
     * @return
     */
    public function getGuaranteeStatus() {
        return $this->guaranteeStatus;
    }

    /**
     * Get the next deadline of a guarantee payment
     *
     * @return
     */
    public function getGuaranteeDeadlineTimestamp() {
        return $this->guaranteeDeadlineTimestamp;
    }

    /**
     * Get the name of an invoice payment
     *
     * @return
     */
    public function getShippingAddressName() {
        return $this->shippingAddressName;
    }

    /**
     * Get the street address of an invoice payment
     *
     * @return
     */
    public function getShippingAddressStreetAddress() {
        return $this->shippingAddressStreetAddress;
    }

    /**
     * Get the postal code of an invoice payment
     *
     * @return
     */
    public function getShippingAddressPostalCode() {
        return $this->shippingAddressPostalCode;
    }

    /**
     * Get the city of an invoice payment
     *
     * @return
     */
    public function getShippingAddressCity() {
        return $this->shippingAddressCity;
    }

    /**
     * Get the country of an invoice payment
     *
     * @return
     */
    public function getShippingAddressCountry() {
        return $this->shippingAddressCountry;
    }

    /**
     * Returns the fee that the receiver of the payment are charged
     * @return double
     */
    public function getReceiverFee() {
        return $this->receiverFee;
    }

    public function __toString() {
        $receiversString = "";
        foreach ($this->receivers as $receiver) {
            $receiversString = $receiversString . "\t" . $receiver . "\n";
        }

        $orderItemsString = "";

        foreach ($this->orderItems as $orderItem) {
            $orderItemsString = $orderItemsString . "\t" . $orderItem . "\n";
        }
        $returnData = "token:\t\t " . $this->token . "\n" .
                "type:\t\t " . $this->type . "\n" .
                "status:\t\t " . $this->status . "\n" .
                "currencyCode:\t " . $this->currencyCode . "\n" .
                "custom:\t\t " . $this->custom . "\n" .
                "correlationId:\t " . $this->correlationId . "\n" .
                "purchaseId:\t " . $this->purchaseId . "\n" .
                "senderEmail:\t " . $this->senderEmail . "\n" .
                "receivers:\t\t \n" . $receiversString .
                "orderItems:\t\t \n" . $orderItemsString .
                "receiverFee:\t " . $this->receiverFee . $this->currencyCode;

        if ($this->type == "INVOICE") {
            $invoiceData = "\n\nInvoice status:\t " . $this->invoiceStatus;
            $invoiceData .= "\nShipping address: \n";
            $invoiceData .= "\nName:\t\t " . $this->shippingAddressName;
            $invoiceData .= "\nStreet:\t\t " . $this->shippingAddressStreetAddress;
            $invoiceData .= "\nZip code:\t " . $this->shippingAddressPostalCode;
            $invoiceData .= "\nCity:\t\t " . $this->shippingAddressCity;
            $invoiceData .= "\nCountry:\t " . $this->shippingAddressCountry;

            $returnData .= $invoiceData;
        }

        return $returnData;
    }

}

?>