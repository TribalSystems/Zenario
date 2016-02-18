<?php
/*
 * Copyright (c) 2016, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');

revision(12
, <<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_countries (
		`id` varchar(5) NOT NULL ,
		`english_name` varchar(255),
		`active` tinyint(1),
		PRIMARY KEY (`id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
, 
<<<_sql
	REPLACE INTO [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_countries (`id`,`english_name`,`active`)
	VALUES 
		("AD","Andorra",1),
		("AE","United Arab Emirates",1),
		("AF","Afghanistan",1),
		("AG","Antigua and Barbuda",1),
		("AI","Anguilla",1),
		("AL","Albania",1),
		("AM","Armenia",1),
		("AN","Netherlands Antilles",1),
		("AO","Angola",1),
		("AQ","Antarctica",1),
		("AR","Argentina",1),
		("AS","American Samoa",1),
		("AT","Austria",1),
		("AU","Australia",1),
		("AW","Aruba",1),
		("AX","Åland Islands",1),
		("AZ","Azerbaijan",1),
		("BA","Bosnia and Herzegovina",1),
		("BB","Barbados",1),
		("BD","Bangladesh",1),
		("BE","Belgium",1),
		("BF","Burkina Faso",1),
		("BG","Bulgaria",1),
		("BH","Bahrain",1),
		("BI","Burundi",1),
		("BJ","Benin",1),
		("BL","Saint Barthélemy",1),
		("BM","Bermuda",1),
		("BN","Brunei Darussalam",1),
		("BO","Bolivia, Plurinational State of",1),
		("BR","Brazil",1),
		("BS","Bahamas",1),
		("BT","Bhutan",1),
		("BV","Bouvet Island",1),
		("BW","Botswana",1),
		("BY","Belarus",1),
		("BZ","Belize",1),
		("CA","Canada",1),
		("CC","Cocos (Keeling) Islands",1),
		("CD","Congo, the Democratic Republic of the",1),
		("CF","Central African Republic",1),
		("CG","Congo",1),
		("CH","Switzerland",1),
		("CI","Côte d'Ivoire",1),
		("CK","Cook Islands",1),
		("CL","Chile",1),
		("CM","Cameroon",1),
		("CN","China",1),
		("CO","Colombia",1),
		("CR","Costa Rica",1),
		("CU","Cuba",1),
		("CV","Cape Verde",1),
		("CX","Christmas Island",1),
		("CY","Cyprus",1),
		("CZ","Czech Republic",1),
		("DE","Germany",1),
		("DJ","Djibouti",1),
		("DK","Denmark",1),
		("DM","Dominica",1),
		("DO","Dominican Republic",1),
		("DZ","Algeria",1),
		("EC","Ecuador",1),
		("EE","Estonia",1),
		("EG","Egypt",1),
		("EH","Western Sahara",1),
		("ER","Eritrea",1),
		("ES","Spain",1),
		("ET","Ethiopia",1),
		("FI","Finland",1),
		("FJ","Fiji",1),
		("FK","Falkland Islands (Malvinas)",1),
		("FM","Micronesia, Federated States of",1),
		("FO","Faroe Islands",1),
		("FR","France",1),
		("GA","Gabon",1),
		("GB","United Kingdom",1),
		("GD","Grenada",1),
		("GE","Georgia",1),
		("GF","French Guiana",1),
		("GG","Guernsey",1),
		("GH","Ghana",1),
		("GI","Gibraltar",1),
		("GL","Greenland",1),
		("GM","Gambia",1),
		("GN","Guinea",1),
		("GP","Guadeloupe",1),
		("GQ","Equatorial Guinea",1),
		("GR","Greece",1),
		("GS","South Georgia and the South Sandwich Islands",1),
		("GT","Guatemala",1),
		("GU","Guam",1),
		("GW","Guinea-Bissau",1),
		("GY","Guyana",1),
		("HK","Hong Kong",1),
		("HM","Heard Island and McDonald Islands",1),
		("HN","Honduras",1),
		("HR","Croatia",1),
		("HT","Haiti",1),
		("HU","Hungary",1),
		("ID","Indonesia",1),
		("IE","Ireland",1),
		("IL","Israel",1),
		("IM","Isle of Man",1),
		("IN","India",1),
		("IO","British Indian Ocean Territory",1),
		("IQ","Iraq",1),
		("IR","Iran, Islamic Republic of",1),
		("IS","Iceland",1),
		("IT","Italy",1),
		("JE","Jersey",1),
		("JM","Jamaica",1),
		("JO","Jordan",1),
		("JP","Japan",1),
		("KE","Kenya",1),
		("KG","Kyrgyzstan",1),
		("KH","Cambodia",1),
		("KI","Kiribati",1),
		("KM","Comoros",1),
		("KN","Saint Kitts and Nevis",1),
		("KP","Korea, Democratic People's Republic of",1),
		("KR","Korea, Republic of",1),
		("KW","Kuwait",1),
		("KY","Cayman Islands",1),
		("KZ","Kazakhstan",1),
		("LA","Lao People's Democratic Republic",1),
		("LB","Lebanon",1),
		("LC","Saint Lucia",1),
		("LI","Liechtenstein",1),
		("LK","Sri Lanka",1),
		("LR","Liberia",1),
		("LS","Lesotho",1),
		("LT","Lithuania",1),
		("LU","Luxembourg",1),
		("LV","Latvia",1),
		("LY","Libyan Arab Jamahiriya",1),
		("MA","Morocco",1),
		("MC","Monaco",1),
		("MD","Moldova, Republic of",1),
		("ME","Montenegro",1),
		("MF","Saint Martin (French part)",1),
		("MG","Madagascar",1),
		("MH","Marshall Islands",1),
		("MK","Macedonia, the former Yugoslav Republic of",1),
		("ML","Mali",1),
		("MM","Myanmar",1),
		("MN","Mongolia",1),
		("MO","Macao",1),
		("MP","Northern Mariana Islands",1),
		("MQ","Martinique",1),
		("MR","Mauritania",1),
		("MS","Montserrat",1),
		("MT","Malta",1),
		("MU","Mauritius",1),
		("MV","Maldives",1),
		("MW","Malawi",1),
		("MX","Mexico",1),
		("MY","Malaysia",1),
		("MZ","Mozambique",1),
		("NA","Namibia",1),
		("NC","New Caledonia",1),
		("NE","Niger",1),
		("NF","Norfolk Island",1),
		("NG","Nigeria",1),
		("NI","Nicaragua",1),
		("NL","Netherlands",1),
		("NO","Norway",1),
		("NP","Nepal",1),
		("NR","Nauru",1),
		("NU","Niue",1),
		("NZ","New Zealand",1),
		("OM","Oman",1),
		("PA","Panama",1),
		("PE","Peru",1),
		("PF","French Polynesia",1),
		("PG","Papua New Guinea",1),
		("PH","Philippines",1),
		("PK","Pakistan",1),
		("PL","Poland",1),
		("PM","Saint Pierre and Miquelon",1),
		("PN","Pitcairn",1),
		("PR","Puerto Rico",1),
		("PS","Palestinian Territory, Occupied",1),
		("PT","Portugal",1),
		("PW","Palau",1),
		("PY","Paraguay",1),
		("QA","Qatar",1),
		("RE","Réunion",1),
		("RO","Romania",1),
		("RS","Serbia",1),
		("RU","Russian Federation",1),
		("RW","Rwanda",1),
		("SA","Saudi Arabia",1),
		("SB","Solomon Islands",1),
		("SC","Seychelles",1),
		("SD","Sudan",1),
		("SE","Sweden",1),
		("SG","Singapore",1),
		("SH","Saint Helena",1),
		("SI","Slovenia",1),
		("SJ","Svalbard and Jan Mayen",1),
		("SK","Slovakia",1),
		("SL","Sierra Leone",1),
		("SM","San Marino",1),
		("SN","Senegal",1),
		("SO","Somalia",1),
		("SR","Suriname",1),
		("ST","Sao Tome and Principe",1),
		("SV","El Salvador",1),
		("SY","Syrian Arab Republic",1),
		("SZ","Swaziland",1),
		("TC","Turks and Caicos Islands",1),
		("TD","Chad",1),
		("TF","French Southern Territories",1),
		("TG","Togo",1),
		("TH","Thailand",1),
		("TJ","Tajikistan",1),
		("TK","Tokelau",1),
		("TL","Timor-Leste",1),
		("TM","Turkmenistan",1),
		("TN","Tunisia",1),
		("TO","Tonga",1),
		("TR","Turkey",1),
		("TT","Trinidad and Tobago",1),
		("TV","Tuvalu",1),
		("TW","Taiwan, Province of China",1),
		("TZ","Tanzania, United Republic of",1),
		("UA","Ukraine",1),
		("UG","Uganda",1),
		("UM","United States Minor Outlying Islands",1),
		("US","United States",1),
		("UY","Uruguay",1),
		("UZ","Uzbekistan",1),
		("VA","Holy See (Vatican City State)",1),
		("VC","Saint Vincent and the Grenadines",1),
		("VE","Venezuela, Bolivarian Republic of",1),
		("VG","Virgin Islands, British",1),
		("VI","Virgin Islands, U.S.",1),
		("VN","Vietnam",1),
		("VU","Vanuatu",1),
		("WF","Wallis and Futuna",1),
		("WS","Samoa",1),
		("YE","Yemen",1),
		("YT","Mayotte",1),
		("ZA","South Africa",1),
		("ZM","Zambia",1),
		("ZW","Zimbabwe",1)
_sql
,
<<<_sql
DROP TABLE IF EXISTS [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions;
_sql
,
<<<_sql
	CREATE TABLE IF NOT EXISTS [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions (
		`id` varchar(5) NOT NULL,
		`country_id` varchar(5) NOT NULL,
		`name` varchar(255),
		`active` tinyint(1),
		PRIMARY KEY (`id`,`country_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8
_sql
);
revision( 16
, <<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions
CHANGE COLUMN id code varchar(5), 
ADD COLUMN id int(10) AUTO_INCREMENT FIRST,
DROP PRIMARY KEY, 
ADD PRIMARY KEY (`id`);
_sql
);
revision( 17
, <<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions
ADD CONSTRAINT UNIQUE (`code`,`country_id`);
_sql
);
revision(25,
<<<_sql
INSERT IGNORE
	[[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions (name,code,country_id,active) 
VALUES 
	('Alabama','AL','US',1),
	('Alaska','AK','US',1),
	('Arizona','AZ','US',1),
	('Arkansas','AR','US',1),
	('California','CA','US',1),
	('Colorado','CO','US',1),
	('Connecticut','CT','US',1),
	('Delaware','DE','US',1),
	('District of Columbia','DC','US',1),
	('Florida','FL','US',1),
	('Georgia','GA','US',1),
	('Hawaii','HI','US',1),
	('Idaho','ID','US',1),
	('Illinois','IL','US',1),
	('Indiana','IN','US',1),
	('Iowa','IA','US',1),
	('Kansas','KS','US',1),
	('Kentucky','KY','US',1),
	('Louisiana','LA','US',1),
	('Maine','ME','US',1),
	('Maryland','MD','US',1),
	('Massachusetts','MA','US',1),
	('Michigan','MI','US',1),
	('Minnesota','MN','US',1),
	('Mississippi','MS','US',1),
	('Missouri','MO','US',1),
	('Montana','MT','US',1),
	('Nebraska','NE','US',1),
	('Nevada','NV','US',1),
	('New Hampshire','NH','US',1),
	('New Jersey','NJ','US',1),
	('New Mexico','NM','US',1),
	('New York','NY','US',1),
	('North Carolina','NC','US',1),
	('North Dakota','ND','US',1),
	('Ohio','OH','US',1),
	('Oklahoma','OK','US',1),
	('Oregon','OR','US',1),
	('Pennsylvania','PA','US',1),
	('RhodeIsland','RI','US',1),
	('South Carolina','SC','US',1),
	('South Dakota','SD','US',1),
	('Tennessee','TN','US',1),
	('Texas','TX','US',1),
	('Utah','UT','US',1),
	('Vermont','VT','US',1),
	('Virginia','VA','US',1),
	('Washington','WA','US',1),
	('West Virginia','WV','US',1),
	('Wisconsin','WI','US',1),
	('Wyoming','WY','US',1),
	('Alberta','AB','CA',1),
	('British Columbia','BC','CA',1),
	('Manitoba','MB','CA',1),
	('New Brunswick','NB','CA',1),
	('Newfoundland and Labrador','NL','CA',1),
	('Northwest Territories','NT','CA',1),
	('Nova Scotia','NS','CA',1),
	('Nunavut','NU','CA',1),
	('Ontario','ON','CA',1),
	('Prince Edward Island','PE','CA',1),
	('Quebec','QC','CA',1),
	('Saskatchewan','SK','CA',1),
	('Yukon','YT','CA',1);
_sql
);
revision(27
, <<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions
ADD COLUMN `parent_id` int(10) NOT NULL DEFAULT 0 AFTER `id`
_sql
);
revision(28,
<<<_sql
ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions
DROP KEY `code`,
ADD CONSTRAINT UNIQUE  (`name`,`parent_id`,`country_id`)
_sql
);
revision(58,
<<<_sql
	ALTER TABLE [[DB_NAME_PREFIX]][[ZENARIO_COUNTRY_MANAGER_PREFIX]]country_manager_regions 
	ADD COLUMN `region_type` enum('region', 'state', 'city') NOT NULL DEFAULT 'region',
	ADD KEY (`region_type`);
_sql
);