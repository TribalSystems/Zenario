<?php

class My360Lead
{
    public $Address;
    public $Auth;
    public $Clients;
    public $Notes;
    public $Opportunity;
}

class My360Address
{
    public $AddressLine1;
    public $AddressLine2;
    public $County;
    public $MailingName;
    public $Postcode;
    public $Salutation;
    public $Town;
}

class My360Auth
{
    public $Key;
}

class My360Client
{
    public $Contact;
    public $DateOfBirth;
    public $Dependants;
    public $Forename;
    public $Gender;
    public $Income;
    public $EmploymentStatus;
    public $Occupation;
    public $Smoker;
    public $Surname;
    public $Title;
    
}

class My360Contact
{
    public $Email;
    public $Home;
    public $Mobile;
    public $Work;
}

class My360Opportunity
{
    public $Advisor;
    public $Appointment;
    public $Introducer;
    public $My360LeadSource;
    public $My360LeadType;
}

class My360Appointment
{
    public $End;
    public $Location;
    public $LocationType;
    public $Start;
}

class My360Ping
{
}

class My360PingResponse
{
    public $PingResult;
}

class My360SaveLead
{
    public $value;
}

class My360SaveLeadResponse
{
    public $SaveLeadResult;
}
