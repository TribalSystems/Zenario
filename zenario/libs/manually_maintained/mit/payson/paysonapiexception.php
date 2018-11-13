<?php
namespace PaysonEmbedded{
    class PaysonApiException extends \Exception {
        private $errors = array();
        
        public function getErrorList() {
            return $this->errors;
        }
        
        public function getErrors() {
            $r = '';
            if(count($this->errors)) {
                foreach ($this->errors as $error){
                    $r.='<pre>'.$error->message . ($error->parameter?'  --  Parameter: ' .$error->parameter:'').'</pre>';    
                }
            } else {
                $r .= $this->getMessage();
            }
            return $r;
        }
    
        public function __construct($message,array $errors = array()) {
            $this->errors = $errors;
            if(count($errors)) {
                $message .="\n\n"; foreach($errors as $error) { $message .= "\n".$error."\n"; } $message .="\n\n";
            }
            parent::__construct($message);
        }
    }
}
