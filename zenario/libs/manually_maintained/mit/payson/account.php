<?php
namespace PaysonEmbedded{
     class Account{
        /** @var string $accountEmail */
        public $accountEmail;
        /** @var string $status */
        public $status;
        /** @var int $merchantId */
        public $merchantId;
        /** @var string $enabledForInvoice */
        public $enabledForInvoice;
        /** @var string $enabledForPaymentPlan */
        public $enabledForPaymentPlan;
        
        public function __construct($accountEmail, $status,  $merchantId,  $enabledForInvoice, $enabledForpaymentPlan){
            $this->accountEmail = $accountEmail;
            $this->status = $status;
            $this->merchantId = $merchantId;
            $this->enabledForInvoice = $enabledForInvoice;
            $this->enabledForpaymentPlan = $enabledForpaymentPlan;
        }
        
        public static function create($data) {
             return new Account($data->accountEmail,$data->status,$data->merchantId,$data->enabledForInvoice,$data->enabledForpaymentPlan);
        }
        
        public function toArray(){
            return get_object_vars($this);   
        }
    }
}