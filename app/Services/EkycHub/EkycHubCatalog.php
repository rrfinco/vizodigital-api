<?php

namespace App\Services\EkycHub;

/**
 * Static opcode and circle references for EkycHub plan / operator APIs.
 */
class EkycHubCatalog
{
    public const MOBILE_OPCODES = [
        'A' => 'Airtel',
        'V' => 'Vodafone',
        'J' => 'Jio',
        'BT' => 'BSNL Topup',
        'BS' => 'BSNL Special',
    ];

    public const DTH_OPCODES = [
        'ATV' => 'Airtel DTH',
        'STV' => 'Sun DTH',
        'DTV' => 'Dish TV',
        'TTV' => 'Tata Play',
        'VTV' => 'Videocon DTH',
    ];

    public const CIRCLE_CODES = [
        '105' => 'JHARKHAND',
        '104' => 'MIZZORAM',
        '103' => 'MEGHALAY',
        '102' => 'GOA',
        '101' => 'CHHATISGARH',
        '100' => 'TRIPURA',
        '99' => 'SIKKIM',
        '49' => 'AP',
        '95' => 'KERALA',
        '94' => 'TAMIL NADU',
        '40' => 'CHENNAI',
        '06' => 'KARNATAKA',
        '52' => 'BIHAR',
        '16' => 'NESA',
        '56' => 'ASSAM',
        '53' => 'ORISSA',
        '51' => 'West Bengal',
        '31' => 'KOLKATTA',
        '70' => 'RAJASTHAN',
        '93' => 'MP',
        '98' => 'GUJARAT',
        '90' => 'MAHARASHTRA',
        '92' => 'MUMBAI',
        '54' => 'UP(East)',
        '55' => 'J&K',
        '96' => 'HARYANA',
        '03' => 'HP',
        '02' => 'PUNJAB',
        '97' => 'UP(West)',
        '10' => 'DELHI',
    ];
}
