<?php

class PaymentDetailsData {

    protected $token;

    public function __construct($token) {
        $this->token = $token;
    }

    public function getOutput() {
        $output = array();

        $output["token"] = $this->token;

        return $output;
    }

}

?>