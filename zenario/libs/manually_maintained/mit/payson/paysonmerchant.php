<?php
namespace PaysonEmbedded {
    class Merchant {
        /** @var url $checkoutUri URI to the merchants checkout page.*/
        public $checkoutUri = NULL;
        /** @var url $confirmationUri URI to the merchants confirmation page. */
        public $confirmationUri;
        /** @var url $notificationUri Notification URI which receives CPR-status updates. */
        public $notificationUri;
        /** @var url $verificationUri Validation URI which is called to verify an order before it can be paid. */
        public $validationUri = NULL;
        /** @var url $termsUri URI leading to the sellers terms. */
        public $termsUri;
        /** @var string $reference Merchants own reference of the checkout.*/
        public $reference = NULL;
        /** @var string $partnerId Partners unique identifier */
        public $partnerId = NULL;
        /** @var string $integrationInfo Information about the integration. */
        public $integrationInfo = NULL;
        public function __construct($checkoutUri, $confirmationUri, $notificationUri, $termsUri, $partnerId = NULL, $integrationInfo = 'PaysonCheckout2.0|1.0|NONE') {
            $this->checkoutUri = $checkoutUri;
            $this->confirmationUri = $confirmationUri;
            $this->notificationUri = $notificationUri;
            $this->termsUri = $termsUri;
            $this->partnerId = $partnerId;
            $this->integrationInfo = $integrationInfo;
        }
        
        public static function create($data) {
            $merchant =  new Merchant($data->checkoutUri,$data->confirmationUri,$data->notificationUri,$data->termsUri, $data->partnerId, $data->integrationInfo);
            $merchant->reference=$data->reference;
            $merchant->validationUri=$data->validationUri;
            return $merchant;
        }
     
        public function toArray(){
            return get_object_vars($this);      
        }
    }
}