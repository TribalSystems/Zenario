<?php

class Receiver {

    protected $email;
    protected $amount;
    protected $firstName;
    protected $lastName;
    protected $isPrimary;

    const FORMAT_STRING = "receiverList.receiver(%d).%s";

    public function __construct($email, $amount, $firstName = null, $lastName = null, $isPrimary = true) {
        $this->email = $email;
        $this->amount = $amount;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->isPrimary = $isPrimary;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getAmount() {
        return $this->amount;
    }
	
	public function isPrimary() {
        return $this->isPrimary;
    }
	
	public function getFirstName() {
        return $this->firstName;
    }
	
	public function getLastName() {
        return $this->lastName;
    }
	
	public function setPrimaryReceiver($isPrimary) {
		$this->isPrimary = $isPrimary;
	}
	
    public static function parseReceivers($data) {
        $receivers = array();

        $i = 0;
        while (isset($data[sprintf(self::FORMAT_STRING, $i, "email")])) {
            $receivers[$i] = new Receiver(
                    $data[sprintf(self::FORMAT_STRING, $i, "email")],
					$data[sprintf(self::FORMAT_STRING, $i, "amount")]
					);
			if (isset($data[sprintf(self::FORMAT_STRING, $i, "primary")])){
				$receivers[$i]->setPrimaryReceiver(strtoupper($data[sprintf(self::FORMAT_STRING, $i, "primary")]) == "TRUE" );
			}
            $i++;
        }

        return $receivers;
    }

    public static function addReceiversToOutput($items, &$output) {
        $i = 0;
        foreach ($items as $item) {
            $output[sprintf(self::FORMAT_STRING, $i, "email")] = $item->getEmail();
            $output[sprintf(self::FORMAT_STRING, $i, "amount")] = number_format($item->getAmount(), 2, ".", ",");
			if ($item->isPrimary() != null){
				$output[sprintf(self::FORMAT_STRING, $i, "primary")] = $item->isPrimary() ? "true" : "false";
			}
			if ($item->getFirstName() != null){
				$output[sprintf(self::FORMAT_STRING, $i, "firstName")] = $item->getFirstName();
			}
			if ($item->getLastName() != null){
				$output[sprintf(self::FORMAT_STRING, $i, "lastName")] = $item->getLastName();
			}
            $i++;
        }
    }

    public function __toString() {
        return "email: " . $this->email . " amount: " . $this->amount;
    }

}

?>
