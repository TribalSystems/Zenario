<?php

/**
 * Represents an order item
 * 
 * Note:  Order Items are required for INVOICE payments and optional for other payment types. Also, please note that 
 * the total sum of all order items amount (inc. VAT) must match the total sum of all receivers amount.
 */
class OrderItem {

    protected $description;
    protected $unitPrice;
    protected $quantity;
    protected $taxPercentage;
    protected $sku;

    const FORMAT_STRING = "orderItemList.orderItem(%d).%s";

    /**
     * Constructs an OrderItem object
     * 
     * If any other value than description is provided all of them has to be entered
     * 
     * @param string $description Description of order item. Max 128 characters
     * @param type $unitPrice Unit price not incl. VAT
     * @param type $quantity  Quantity of this item. Can have at most 2 decimal places
     * @param type $taxPercentage Tax value. Not actual percentage. For example, 25% has to be entered as 0.25
     * @param string $sku Sku of item
     */
    public function __construct($description, $unitPrice = null, $quantity = null, $taxPercentage = null, $sku = null) {
        $this->description = $description;
        $this->unitPrice = $unitPrice;
        $this->quantity = $quantity;
        $this->taxPercentage = $taxPercentage;
        $this->sku = $sku;
    }

    /**
     * Returns the description of the order item
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Price of item. <strong>Note: </strong>Not including vat
     * 
     * @return type
     */
    public function getUnitPrice() {
        return $this->unitPrice;
    }

    /**
     * Quantity of this item
     * 
     * @return type
     */
    public function getQuantity() {
        return $this->quantity;
    }

    /**
     * Returns the tax as a decimal value. Multiply this with 100
     * to get actual tax value.
     * 
     * @return type Tax value
     */
    public function getTaxPercentage() {
        return $this->taxPercentage;
    }

    /**
     * Returns the Sku of the order item
     * 
     * @return string
     */
    public function getSku() {
        return $this->sku;
    }

    /**
     * Parses an object into an array containing OrderItem objects
     * 
     * @param type $data
     * @return array|\OrderItem
     */
    public static function parseOrderItems($data) {
        $items = array();
        $i = 0;

        if (!is_array($data)) {
            return $items;
        }

        while (isset($data[sprintf(self::FORMAT_STRING, $i, "description")])) {
            $items[$i] = new OrderItem(
                    $data[sprintf(self::FORMAT_STRING, $i, "description")], $data[sprintf(self::FORMAT_STRING, $i, "unitPrice")], $data[sprintf(self::FORMAT_STRING, $i, "quantity")], $data[sprintf(self::FORMAT_STRING, $i, "taxPercentage")], $data[sprintf(self::FORMAT_STRING, $i, "sku")]
            );

            $i++;
        }

        return $items;
    }

    public static function addOrderItemsToOutput($items, &$output) {
        $i = 0;

        if (is_array($items))
            foreach ($items as $item) {
                $output[sprintf(self::FORMAT_STRING, $i, "description")] = $item->getDescription();
                if ($item->getUnitPrice() != null) {
                    $output[sprintf(self::FORMAT_STRING, $i, "unitPrice")] = number_format($item->getUnitPrice(), 4, ".", ",");
                    $output[sprintf(self::FORMAT_STRING, $i, "quantity")] = number_format($item->getQuantity(), 2, ".", "");
                    $output[sprintf(self::FORMAT_STRING, $i, "taxPercentage")] = number_format($item->getTaxPercentage(), 6, ".", "");
                    $output[sprintf(self::FORMAT_STRING, $i, "sku")] = $item->getSku();
                }
                $i++;
            }
    }

    /**
     * 
     * @return string A string representation of an OrderItem object
     */
    public function __toString() {
        return "description: " . $this->description .
                " unitPrice: " . $this->unitPrice .
                " quantity: " . $this->quantity .
                " taxPercentage: " . $this->taxPercentage .
                " sku: " . $this->sku;
    }

}

?>