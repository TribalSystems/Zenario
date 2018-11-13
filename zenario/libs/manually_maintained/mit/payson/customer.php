<?php
namespace PaysonEmbedded{
     class Customer{
        /** @var string $city */
        public $city;
        /** @var string $countryCode */
        public $countryCode;
        /** @var int $identityNumber Date of birth YYMMDD (digits). */
        public $identityNumber;
        /** @var string $email */
        public $email;
        /** @var string $firstName */
        public $firstName;
        /** @var string $lastName */
        public $lastName;
        /** @var string $phone Phone number. */
        public $phone;
        /** @var string $postalCode Postal code. */
        public $postalCode;
        /** @var string $street Street address.*/
        public $street;
        /** @var string $type Type of customer ("business", "person" (default)).*/
        public $type;
        public function __construct($firstName = Null, $lastName = Null,  $email = Null,  $phone = Null, $identityNumber = Null, $city = Null, $countryCode = Null, $postalCode = Null, $street = Null, $type = 'person'){
            $this->firstName = $firstName;
            $this->lastName = $lastName;
            $this->email = $email;
            $this->phone = $phone;
            $this->identityNumber = $identityNumber;
            $this->city = $city; 
            $this->countryCode = $countryCode;
            $this->postalCode = $postalCode;
            $this->street = $street;
            $this->type = $type;   
        }
        
        public static function create($data) {
             return new Customer($data->firstName,$data->lastName,$data->email,$data->phone,$data->identityNumber,$data->city,$data->countryCode,$data->postalCode,$data->street,$data->type);
        }
        
        public function toArray(){
            return get_object_vars($this);   
        }
    }
}