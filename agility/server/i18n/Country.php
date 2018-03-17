<?php

/*
userFunctions.php

Copyright  2013-2018 by Juan Antonio Martinez ( juansgaviota at gmail dot com )

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation;
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/


require_once(__DIR__."/../logging.php");
require_once(__DIR__."/../tools.php");
require_once(__DIR__."/../auth/Config.php");
require_once(__DIR__."/../auth/AuthManager.php");
class Country {

    /* standard coi abreviatures. Where not found use ISO-3166-1 codes */
// public static $coi_list = array (
public static $countryList = array(
    "AFG" => "Afghanistan",
    "ALB" => "Albania",
    "ALG" => "Algeria",
    "ASA" => "American Samoa",
    "AND" => "Andorra",
    "ANG" => "Angola",
    "AIA" => "Anguilla",
    "ATA" => "Antarctica",
    "ANT" => "Antigua and Barbuda",
    "ARG" => "Argentina",
    "ARM" => "Armenia",
    "ARU" => "Aruba",
    "AUS" => "Australia",
    "AUT" => "Austria",
    "AZE" => "Azerbaijan",
    "BAH" => "Bahamas",
    "BRN" => "Bahrain",
    "BAN" => "Bangladesh",
    "BAR" => "Barbados",
    "BLR" => "Belarus",
    "BEL" => "Belgium",
    "BIZ" => "Belize",
    "BEN" => "Benin",
    "BER" => "Bermuda",
    "BHU" => "Bhutan",
    "BOL" => "Bolivia",
    "BIH" => "Bosnia and Herzegovina",
    "BOT" => "Botswana",
    // "BV" => "Bouvet Island",
    "BRA" => "Brazil",
    //"BQ" => "British Antarctic Territory",
    "IOT" => "British Indian Ocean Territory",
    "IVB" => "British Virgin Islands",
    "BRU" => "Brunei",
    "BUL" => "Bulgaria",
    "BUR" => "Burkina Faso",
    "BDI" => "Burundi",
    "CAM" => "Cambodia",
    "CMR" => "Cameroon",
    "CAN" => "Canada",
    // "CT" => "Canton and Enderbury Islands",
    "CPV" => "Cape Verde",
    "CAY" => "Cayman Islands",
    "CAF" => "Central African Republic",
    "CHA" => "Chad",
    "CLI" => "Chile",
    "CHN" => "China",
    "CXR" => "Christmas Island",
    "CCK" => "Cocos [Keeling] Islands",
    "COL" => "Colombia",
    "COM" => "Comoros",
    "CGO" => "Congo - Brazzaville",
    "COD" => "Congo - Kinshasa",
    "COK" => "Cook Islands",
    "CRC" => "Costa Rica",
    "CRO" => "Croatia",
    "CUB" => "Cuba",
    "CYP" => "Cyprus",
    "CZE" => "Czech Republic",
    "CIV" => "Côte d’Ivoire",
    "DEN" => "Denmark",
    "DJI" => "Djibouti",
    "DMA" => "Dominica",
    "DOM" => "Dominican Republic",
    // "NQ" => "Dronning Maud Land",
    "GDR" => "East Germany",
    "ECU" => "Ecuador",
    "EGY" => "Egypt",
    "ESA" => "El Salvador",
    "GEQ" => "Equatorial Guinea",
    "ERI" => "Eritrea",
    "EST" => "Estonia",
    "ETH" => "Ethiopia",
    // "FK" => "Falkland Islands",
    "FRO" => "Faroe Islands",
    "FIJ" => "Fiji",
    "FIN" => "Finland",
    "FRA" => "France",
    "GUF" => "French Guiana",
    "PYF" => "French Polynesia",
    //"TF" => "French Southern Territories",
    "ATF" => "French Southern and Antarctic Territories",
    "GAB" => "Gabon",
    "GAM" => "Gambia",
    "GEO" => "Georgia",
    "GER" => "Germany",
    "GHA" => "Ghana",
    "GIB" => "Gibraltar",
    "GRE" => "Greece",
    "GRL" => "Greenland",
    "GRN" => "Grenada",
    "GLP" => "Guadeloupe",
    "GUM" => "Guam",
    "GUA" => "Guatemala",
    "GGY" => "Guernsey",
    "GUI" => "Guinea",
    "GBS" => "Guinea-Bissau",
    "GUY" => "Guyana",
    "HAI" => "Haiti",
    "HMD" => "Heard Island and McDonald Islands",
    "HON" => "Honduras",
    "HKG" => "Hong Kong SAR China",
    "HUN" => "Hungary",
    "ISL" => "Iceland",
    "IND" => "India",
    "INA" => "Indonesia",
    "IRI" => "Iran",
    "IRQ" => "Iraq",
    "IRL" => "Ireland",
    "IMN" => "Isle of Man",
    "ISR" => "Israel",
    "ITA" => "Italy",
    "JAM" => "Jamaica",
    "JPN" => "Japan",
    "JEY" => "Jersey",
    // "JT" => "Johnston Island",
    "JOR" => "Jordan",
    "KAZ" => "Kazakhstan",
    "KEN" => "Kenya",
    "KIR" => "Kiribati",
    "KUW" => "Kuwait",
    "KGZ" => "Kyrgyzstan",
    "LAO" => "Laos",
    "LAT" => "Latvia",
    "LIB" => "Lebanon",
    "LES" => "Lesotho",
    "LBR" => "Liberia",
    "LBA" => "Libya",
    "LIE" => "Liechtenstein",
    "LTU" => "Lithuania",
    "LUX" => "Luxembourg",
    "MAC" => "Macau SAR China",
    "MKD" => "Macedonia",
    "MAD" => "Madagascar",
    "MAW" => "Malawi",
    "MAS" => "Malaysia",
    "MDV" => "Maldives",
    "MLI" => "Mali",
    "MLT" => "Malta",
    "MHL" => "Marshall Islands",
    "MTQ" => "Martinique",
    "MTN" => "Mauritania",
    "MRI" => "Mauritius",
    "MYT" => "Mayotte",
    //"FX" => "Metropolitan France",
    "MEX" => "Mexico",
    "FSM" => "Micronesia",
    // "MI" => "Midway Islands",
    "MDA" => "Moldova",
    "MON" => "Monaco",
    "MGL" => "Mongolia",
    "MNE" => "Montenegro",
    "MSR" => "Montserrat",
    "MAR" => "Morocco",
    "MOZ" => "Mozambique",
    "MYA" => "Myanmar [Burma]",
    "NAM" => "Namibia",
    "NRU" => "Nauru",
    "NEP" => "Nepal",
    "NED" => "Netherlands",
    "AOH" => "Netherlands Antilles",
    // "NT" => "Neutral Zone",
    "NCL" => "New Caledonia",
    "NZL" => "New Zealand",
    "NCA" => "Nicaragua",
    "NIG" => "Niger",
    "NGR" => "Nigeria",
    "NIU" => "Niue",
    "NFK" => "Norfolk Island",
    "PRK" => "North Korea",
    // "VIE" => "North Vietnam", // Vietnam
    "MNP" => "Northern Mariana Islands",
    "NOR" => "Norway",
    "OMA" => "Oman",
    // "PC" => "Pacific Islands Trust Territory",
    "PAK" => "Pakistan",
    "PLW" => "Palau",
    "PLE" => "Palestinian Territories",
    "PAN" => "Panama",
    // "PZ" => "Panama Canal Zone",
    "PNG" => "Papua New Guinea",
    "PAR" => "Paraguay",
    // "YD" => "People's Democratic Republic of Yemen",
    "PER" => "Peru",
    "PHI" => "Philippines",
    "PCN" => "Pitcairn Islands",
    "POL" => "Poland",
    "POR" => "Portugal",
    "PUR" => "Puerto Rico",
    "QAT" => "Qatar",
    "ROU" => "Romania",
    "RUS" => "Russia",
    "RWA" => "Rwanda",
    "REU" => "Réunion",
    "BLM" => "Saint Barthélemy",
    "SHN" => "Saint Helena",
    "SKN" => "Saint Kitts and Nevis",
    "LCA" => "Saint Lucia",
    "MAF" => "Saint Martin",
    "SPM" => "Saint Pierre and Miquelon",
    "VIN" => "Saint Vincent and the Grenadines",
    "SAM" => "Samoa",
    "SMR" => "San Marino",
    "KSA" => "Saudi Arabia",
    "SEN" => "Senegal",
    "SRB" => "Serbia",
    // "CS" => "Serbia and Montenegro",
    "SEY" => "Seychelles",
    "SLE" => "Sierra Leone",
    "SIN" => "Singapore",
    "SVK" => "Slovakia",
    "SLO" => "Slovenia",
    "SOL" => "Solomon Islands",
    "SOM" => "Somalia",
    "RSA" => "South Africa",
    "SGS" => "South Georgia and the South Sandwich Islands",
    "KOR" => "South Korea",
    "ESP" => "Spain",
    "SRI" => "Sri Lanka",
    "SUD" => "Sudan",
    "SUR" => "Suriname",
    "SJM" => "Svalbard and Jan Mayen",
    "SWZ" => "Swaziland",
    "SWE" => "Sweden",
    "SUI" => "Switzerland",
    "SYR" => "Syria",
    "STP" => "São Tomé and Príncipe",
    // "TW" => "Taiwan",
    "TJK" => "Tajikistan",
    "TAN" => "Tanzania",
    "THA" => "Thailand",
    "TLS" => "Timor-Leste",
    "TOG" => "Togo",
    "TKL" => "Tokelau",
    "TGA" => "Tonga",
    "TTO" => "Trinidad and Tobago",
    "TUN" => "Tunisia",
    "TUR" => "Turkey",
    "TKM" => "Turkmenistan",
    "TCA" => "Turks and Caicos Islands",
    "TUV" => "Tuvalu",
    "UMI" => "U.S. Minor Outlying Islands",
    // "PU" => "U.S. Miscellaneous Pacific Islands",
    "ISV" => "U.S. Virgin Islands", // Virgin Islands
    "UGA" => "Uganda",
    "UKR" => "Ukraine",
    // "SU" => "Union of Soviet Socialist Republics",
    "UAE" => "United Arab Emirates",
    "GBR" => "Great Britain", // Great Bretain
    "USA" => "United States",
    // "ZZ" => "Unknown or Invalid Region",
    "URU" => "Uruguay",
    "UZB" => "Uzbekistan",
    "VAN" => "Vanuatu",
    "VAT" => "Vatican City",
    "VEN" => "Venezuela",
    "VIE" => "Vietnam",
    // "WK" => "Wake Island",
    "WLF" => "Wallis and Futuna",
    "ESH" => "Western Sahara",
    "YEM" => "Yemen",
    "ZAM" => "Zambia",
    "ZIM" => "Zimbabwe",
    "ALA" => "Åland Islands",
);

    public static $isoList = array(
"AF" => "Afghanistan",
"AL" => "Albania",
"DZ" => "Algeria",
"AS" => "American Samoa",
"AD" => "Andorra",
"AO" => "Angola",
"AI" => "Anguilla",
"AQ" => "Antarctica",
"AG" => "Antigua and Barbuda",
"AR" => "Argentina",
"AM" => "Armenia",
"AW" => "Aruba",
"AU" => "Australia",
"AT" => "Austria",
"AZ" => "Azerbaijan",
"BS" => "Bahamas",
"BH" => "Bahrain",
"BD" => "Bangladesh",
"BB" => "Barbados",
"BY" => "Belarus",
"BE" => "Belgium",
"BZ" => "Belize",
"BJ" => "Benin",
"BM" => "Bermuda",
"BT" => "Bhutan",
"BO" => "Bolivia",
"BA" => "Bosnia and Herzegovina",
"BW" => "Botswana",
"BV" => "Bouvet Island",
"BR" => "Brazil",
"BQ" => "British Antarctic Territory",
"IO" => "British Indian Ocean Territory",
"VG" => "British Virgin Islands",
"BN" => "Brunei",
"BG" => "Bulgaria",
"BF" => "Burkina Faso",
"BI" => "Burundi",
"KH" => "Cambodia",
"CM" => "Cameroon",
"CA" => "Canada",
"CT" => "Canton and Enderbury Islands",
"CV" => "Cape Verde",
"KY" => "Cayman Islands",
"CF" => "Central African Republic",
"TD" => "Chad",
"CL" => "Chile",
"CN" => "China",
"CX" => "Christmas Island",
"CC" => "Cocos [Keeling] Islands",
"CO" => "Colombia",
"KM" => "Comoros",
"CG" => "Congo - Brazzaville",
"CD" => "Congo - Kinshasa",
"CK" => "Cook Islands",
"CR" => "Costa Rica",
"HR" => "Croatia",
"CU" => "Cuba",
"CY" => "Cyprus",
"CZ" => "Czech Republic",
"CI" => "Côte d’Ivoire",
"DK" => "Denmark",
"DJ" => "Djibouti",
"DM" => "Dominica",
"DO" => "Dominican Republic",
"NQ" => "Dronning Maud Land",
"DD" => "East Germany",
"EC" => "Ecuador",
"EG" => "Egypt",
"SV" => "El Salvador",
"GQ" => "Equatorial Guinea",
"ER" => "Eritrea",
"EE" => "Estonia",
"ET" => "Ethiopia",
"FK" => "Falkland Islands",
"FO" => "Faroe Islands",
"FJ" => "Fiji",
"FI" => "Finland",
"FR" => "France",
"GF" => "French Guiana",
"PF" => "French Polynesia",
"TF" => "French Southern Territories",
"FQ" => "French Southern and Antarctic Territories",
"GA" => "Gabon",
"GM" => "Gambia",
"GE" => "Georgia",
"DE" => "Germany",
"GH" => "Ghana",
"GI" => "Gibraltar",
"GR" => "Greece",
"GL" => "Greenland",
"GD" => "Grenada",
"GP" => "Guadeloupe",
"GU" => "Guam",
"GT" => "Guatemala",
"GG" => "Guernsey",
"GN" => "Guinea",
"GW" => "Guinea-Bissau",
"GY" => "Guyana",
"HT" => "Haiti",
"HM" => "Heard Island and McDonald Islands",
"HN" => "Honduras",
"HK" => "Hong Kong SAR China",
"HU" => "Hungary",
"IS" => "Iceland",
"IN" => "India",
"ID" => "Indonesia",
"IR" => "Iran",
"IQ" => "Iraq",
"IE" => "Ireland",
"IM" => "Isle of Man",
"IL" => "Israel",
"IT" => "Italy",
"JM" => "Jamaica",
"JP" => "Japan",
"JE" => "Jersey",
"JT" => "Johnston Island",
"JO" => "Jordan",
"KZ" => "Kazakhstan",
"KE" => "Kenya",
"KI" => "Kiribati",
"KW" => "Kuwait",
"KG" => "Kyrgyzstan",
"LA" => "Laos",
"LV" => "Latvia",
"LB" => "Lebanon",
"LS" => "Lesotho",
"LR" => "Liberia",
"LY" => "Libya",
"LI" => "Liechtenstein",
"LT" => "Lithuania",
"LU" => "Luxembourg",
"MO" => "Macau SAR China",
"MK" => "Macedonia",
"MG" => "Madagascar",
"MW" => "Malawi",
"MY" => "Malaysia",
"MV" => "Maldives",
"ML" => "Mali",
"MT" => "Malta",
"MH" => "Marshall Islands",
"MQ" => "Martinique",
"MR" => "Mauritania",
"MU" => "Mauritius",
"YT" => "Mayotte",
"FX" => "Metropolitan France",
"MX" => "Mexico",
"FM" => "Micronesia",
"MI" => "Midway Islands",
"MD" => "Moldova",
"MC" => "Monaco",
"MN" => "Mongolia",
"ME" => "Montenegro",
"MS" => "Montserrat",
"MA" => "Morocco",
"MZ" => "Mozambique",
"MM" => "Myanmar [Burma]",
"NA" => "Namibia",
"NR" => "Nauru",
"NP" => "Nepal",
"NL" => "Netherlands",
"AN" => "Netherlands Antilles",
"NT" => "Neutral Zone",
"NC" => "New Caledonia",
"NZ" => "New Zealand",
"NI" => "Nicaragua",
"NE" => "Niger",
"NG" => "Nigeria",
"NU" => "Niue",
"NF" => "Norfolk Island",
"KP" => "North Korea",
"VD" => "North Vietnam",
"MP" => "Northern Mariana Islands",
"NO" => "Norway",
"OM" => "Oman",
"PC" => "Pacific Islands Trust Territory",
"PK" => "Pakistan",
"PW" => "Palau",
"PS" => "Palestinian Territories",
"PA" => "Panama",
"PZ" => "Panama Canal Zone",
"PG" => "Papua New Guinea",
"PY" => "Paraguay",
"YD" => "People's Democratic Republic of Yemen",
"PE" => "Peru",
"PH" => "Philippines",
"PN" => "Pitcairn Islands",
"PL" => "Poland",
"PT" => "Portugal",
"PR" => "Puerto Rico",
"QA" => "Qatar",
"RO" => "Romania",
"RU" => "Russia",
"RW" => "Rwanda",
"RE" => "Réunion",
"BL" => "Saint Barthélemy",
"SH" => "Saint Helena",
"KN" => "Saint Kitts and Nevis",
"LC" => "Saint Lucia",
"MF" => "Saint Martin",
"PM" => "Saint Pierre and Miquelon",
"VC" => "Saint Vincent and the Grenadines",
"WS" => "Samoa",
"SM" => "San Marino",
"SA" => "Saudi Arabia",
"SN" => "Senegal",
"RS" => "Serbia",
"CS" => "Serbia and Montenegro",
"SC" => "Seychelles",
"SL" => "Sierra Leone",
"SG" => "Singapore",
"SK" => "Slovakia",
"SI" => "Slovenia",
"SB" => "Solomon Islands",
"SO" => "Somalia",
"ZA" => "South Africa",
"GS" => "South Georgia and the South Sandwich Islands",
"KR" => "South Korea",
"ES" => "Spain",
"LK" => "Sri Lanka",
"SD" => "Sudan",
"SR" => "Suriname",
"SJ" => "Svalbard and Jan Mayen",
"SZ" => "Swaziland",
"SE" => "Sweden",
"CH" => "Switzerland",
"SY" => "Syria",
"ST" => "São Tomé and Príncipe",
"TW" => "Taiwan",
"TJ" => "Tajikistan",
"TZ" => "Tanzania",
"TH" => "Thailand",
"TL" => "Timor-Leste",
"TG" => "Togo",
"TK" => "Tokelau",
"TO" => "Tonga",
"TT" => "Trinidad and Tobago",
"TN" => "Tunisia",
"TR" => "Turkey",
"TM" => "Turkmenistan",
"TC" => "Turks and Caicos Islands",
"TV" => "Tuvalu",
"UM" => "U.S. Minor Outlying Islands",
"PU" => "U.S. Miscellaneous Pacific Islands",
"VI" => "U.S. Virgin Islands",
"UG" => "Uganda",
"UA" => "Ukraine",
"SU" => "Union of Soviet Socialist Republics",
"AE" => "United Arab Emirates",
"GB" => "Great Britain",
"UK" => "Great Britain", // European Union has made a request to ISO to update this
"US" => "United States",
"ZZ" => "Unknown or Invalid Region",
"UY" => "Uruguay",
"UZ" => "Uzbekistan",
"VU" => "Vanuatu",
"VA" => "Vatican City",
"VE" => "Venezuela",
"VN" => "Vietnam",
"WK" => "Wake Island",
"WF" => "Wallis and Futuna",
"EH" => "Western Sahara",
"YE" => "Yemen",
"ZM" => "Zambia",
"ZW" => "Zimbabwe",
"AX" => "Åland Islands",
);

    /**
     * Returns a json easyui datagrid style list of countries
     */
    function enumerate() {
        $data=array();
        // to be used on country search
        $q=strtolower(http_request("q","s",""));
        // parse country list
        foreach(Country::$countryList as $key => $val) {
            if ($q==="") { // no search key, just add
                array_push($data,array( 'ID' => $key, "Country" => $val ));
                continue;
            }
            $k=strtolower($key); // search 2-letter country code
            if (strpos($k,$q)!==false) {
                array_push($data,array( 'ID' => $key, "Country" => $val ));
                continue;
            }
            $v=strtolower($val); // search for country name
            if ($v===$q) array_push($data,array( 'ID' => $key, "Country" => $val ));
        }
        $result=array('total'=>count($data),'rows'=>$data);
        return $result;
    }
/*
    static function replace(){
        foreach( Country::$coi_list as $coi => $country) {
            foreach (Country::$countryList as $iso => $pais) {
                if ($country!==$pais) continue;
                echo "UPDATE clubes SET Pais='$coi' WHERE Pais='$iso';\n";
                break;
            }
        }
    }
*/
}
// Country::replace();
?>
