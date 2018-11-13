<?php
namespace PaysonEmbedded {
    class Checkout {
        /** @var Merchant $merchant */
        public $merchant;
        
        /** @var PayData $order */
        public $payData;
        
        /** @var Gui $gui */
        public $gui;
        
        /** @var Customer $customer */
        public $customer;
       
       /** @var string $status */
        public $status;
        
        /** @var string $id */
        public $id;
        
        /** @var int $purchaseId */
        public $purchaseId;
        
        /** @var string $snippet */
        public $snippet;
        
        /** @var string $description */
        public $description;
       
        public function __construct(Merchant $merchant, PayData $payData,  Gui $gui = null,  Customer $customer = null, $description = '') {
            $this->merchant = $merchant;
            $this->payData = $payData;
            $this->gui = $gui?:new Gui();
            $this->customer = $customer?:new Customer();
            $this->purchaseId = null;
            $this->description = $description;
        }
        
        public static function create($data) {
            $checkout = new Checkout(Merchant::create($data->merchant), PayData::create($data->order), Gui::create($data->gui), Customer::create($data->customer));
            $checkout->status = $data->status;
            $checkout->id = $data->id;
            $checkout->snippet = $data->snippet;
            if(isset($data->purchaseId)) {
                $checkout->purchaseId = $data->purchaseId;
            }
            
            if(isset($data->description)) {
                $checkout->description = $data->description;
            }
            return $checkout;
        }
      
        public function toArray() {
            return array(
                'id' => $this->id,
                'description' => $this->description,
                'status' => $this->status,
                'merchant' => $this->merchant->toArray(),
                'order' => $this->payData->toArray(),
                'gui' => $this->gui->toArray(),
                'customer' => $this->customer->toArray()
            );
        }
    }
}
