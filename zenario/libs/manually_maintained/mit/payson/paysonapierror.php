<?php
namespace PaysonEmbedded{
    class PaysonApiError {
        public $message = NULL;
        public $parameter = NULL;
        public function __construct($message, $parameter = null) {
            $this->message = $message;
            $this->parameter = $parameter;
        }
        public function __toString() {
            return "Message: " . $this->message . "\t Parameter: " . $this->parameter;
        }
    }
}