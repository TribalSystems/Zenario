<?php
namespace PaysonEmbedded{
    require_once "orderitem.php";
    abstract class CurrencyCode {
        const SEK = "SEK";
        const EUR = "EUR";
    }
    
    class PayData {
        /** @var string $currency Currency of the order ("sek", "eur"). */
        public $currency = NULL;
        /** @var array $items An array with objects of the order items*/
        public $items = array();
        
        /** @var float $totalPriceExcludingTax - Read only */
        public $totalPriceExcludingTax;
        /** @var float $totalPriceIncludingTax - Read only */
        public $totalPriceIncludingTax;
        /** @var float $totalTaxAmount - Read only */
        public $totalTaxAmount;
        /** @var float $totalCreditedAmount - Read only */
        public $totalCreditedAmount;
        public function __construct($currencyCode) {
            $this->currency = $currencyCode;
            $this->items = array();
        }
        
        public static function create($data) {
            $payData = new PayData($data->currency);
            $payData->totalPriceExcludingTax = $data->totalPriceExcludingTax;
            $payData->totalPriceIncludingTax = $data->totalPriceIncludingTax;
            $payData->totalTaxAmount = $data->totalTaxAmount;
            $payData->totalCreditedAmount =$data->totalCreditedAmount;
            
            foreach($data->items as $item) {
                $payData->items[] = OrderItem::create($item);
            }
            
            return $payData;
        }
        
        public function AddOrderItem(OrderItem $item) {
            $this->items[] = $item;
        }
     
        public function setOrderItems($items) { 
            if(!($items instanceof OrderItem))
                throw new PaysonApiException("Parameter must be an object of class Item");
            $this->items = $items;
        }
        public function toArray(){
            $items = array();
            foreach($this->items as $item) { $items[] = $item->toArray();  }
            return array( 'currency'=>$this->currency, 'items'=>$items );
        }
        
        public function toJson(){
            return json_encode($this->toArray());
        }
    }
}