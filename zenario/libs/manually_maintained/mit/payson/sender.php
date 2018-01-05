<?php

class Sender {

    protected $email;
    protected $firstName;
    protected $lastName;

    public function __construct($email, $firstName, $lastName) {
        $this->email = $email;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function getLastName() {
        return $this->lastName;
    }

    public function addSenderToOutput(&$output) {
        $output["senderEmail"] = $this->getEmail();
        $output["senderFirstName"] = $this->getFirstName();
        $output["senderLastName"] = $this->getLastName();
    }

}

?>