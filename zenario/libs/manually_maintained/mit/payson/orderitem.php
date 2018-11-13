<?php
namespace PaysonEmbedded{
    
    abstract class OrderItemType
    {
        const PHYSICAL = 'physical';
        const DISCOUNT = 'discount';
        const SERVICE  = 'service';
        const FEE      = 'fee';
    }
    
    class OrderItem {
        
        /** @var string $id */
        public $itemId;
        /** @var float $discountRate Discount rate of the article (Decimal number (0.00-1.00)). */
        public $discountRate;
        /** @var float $creditedAmount Credited amount (Decimal number (with two decimals)). */
        public $creditedAmount;
        /** @var url $imageUri An URI to an image of the article. */
        public $imageUri;
        /** @var string $name Name of the article. */
        public $name;
        /** @var float $unitPrice Unit price of the article (including tax). */
        public $unitPrice ;
        /** @var int $quantity  Quantity of the article. */
        public $quantity;
        /** @var float taxRate Tax rate of the article (0.00-1.00). */
        public $taxRate;
        /** @var string $reference Article reference, usually the article number. */
        public $reference;
        /** @var string $type Type of article ("Fee", "Physical" (default), "Service"). */
        public $type;
        /** @var url $uri URI to a the article page of the order item. */
        public $uri;
        /** @var ean $ean European Article Number. Discrete number (13 digits) */
        public $ean;
 
        /**
        * Constructs an OrderItem object
        * 
        * If any other value than description is provided all of them has to be entered
        * 
        * @param string $name Name of order item. Max 128 characters
        * @param float $unitPrice Unit price incl. VAT
        * @param int $quantity  Quantity of this item. Can have at most 2 decimal places
        * @param float $taxRate Tax value. Not actual percentage. For example, 25% has to be entered as 0.25
        * @param string $reference Sku of item
        */
        public function __construct($name, $unitPrice, $quantity, $taxRate, $reference, $type=OrderItemType::PHYSICAL, $discountRate=null, $ean = null, $uri=null, $imageUri=null) {
            // Mandatory 
            $this->name = $name;
            $this->unitPrice = $unitPrice;
            $this->quantity = $quantity;
            $this->taxRate = $taxRate;
            $this->type = $type;
            $this->reference = $reference;
            
			if(!$name || is_null($unitPrice) || !$quantity || is_null($taxRate) || !$type || !$reference) {
                throw new PaysonApiException("Not all of mandatory fields are set for creating of an OrderItem object");
            }
            
            // Optional
            $this->discountRate = $discountRate;
            $this->ean = $ean;
            $this->uri = $uri;
            $this->imageUri = $imageUri;
        }
        public static function create($data) {
            $item = new OrderItem($data->name, $data->unitPrice, $data->quantity, $data->taxRate, $data->reference, $data->type, isset($data->discountRate)?$data->discountRate:null, isset($data->ean)?$data->ean:null, isset($data->uri)?$data->uri:null, isset($data->imageUri)?$data->imageUri:null);
            $item->discountRate=$data->discountRate;
            $item->creditedAmount=$data->creditedAmount;
            if(isset($data->itemId)) {
                $item->itemId = $data->itemId;
            }
            return $item;
        }
        
        public function toArray() {
            return get_object_vars($this);   
        }
    }
}