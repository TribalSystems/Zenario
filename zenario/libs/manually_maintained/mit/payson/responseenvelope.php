<?php

class ResponseEnvelope {

    protected $ack;
    protected $timestamp;
    protected $errors;

    public function __construct($responseData) {
        $this->ack = $responseData["responseEnvelope.ack"];
        $this->timestamp = $responseData["responseEnvelope.timestamp"];
        $this->errors = $this->parseErrors($responseData);
    }

    /**
     * Tells if the call is successful or not
     * <b>Note: </b> This only applies to the actual call, in the case
     * of an PaymentDetails call you must also check the Status of the Payment
     * @see PaymentDetails::getStatus()
     * @return boolean Indicates if the call was successful or not
     */
    public function wasSuccessful() {
        return $this->ack === "SUCCESS";
    }

    /**
     * Returns an array with errors if any
     * 
     * @return PaysonApiError[]
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Returns a string representation of the ResponseEnvelope
     * 
     * @return string
     */
    public function __toString() {
        return "ack: " . $this->ack . "\n" .
                "timestamp: " . $this->timestamp . "\n";
    }

    private function parseErrors($output) {
        $errors = array();

        $i = 0;
        while (isset($output[sprintf("errorList.error(%d).message", $i)])) {
            $errors[$i] = new PaysonApiError(
                    $output[sprintf("errorList.error(%d).errorId", $i)], $output[sprintf("errorList.error(%d).message", $i)], isset($output[sprintf("errorList.error(%d).parameter", $i)]) ?
                            $output[sprintf("errorList.error(%d).parameter", $i)] : null
            );
            $i++;
        }

        return $errors;
    }

}

?>