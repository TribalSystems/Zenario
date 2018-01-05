<?php

class PaymentUpdateData {

    protected $token;
    protected $method;

    public function __construct($token, $method) {
        $this->token = $token;
        $this->method = $method;
    }

    public function getOutput() {
        $output = array();

        $output["token"] = $this->token;
        $output["action"] = PaymentUpdateMethod::ConstantToString($this->method);

        return $output;
    }

}

?>