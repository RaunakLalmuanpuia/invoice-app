<?php

namespace App\Services;

class MockDataService
{
    // === YOUR COMPANY DETAILS (SELLER) ===
    private static array $seller = [
        'seller_company_name' => 'Tili Technologies',
        'seller_gst_number'   => '32AAAAA8888A1Z5',
        'seller_state'        => 'Kerala',
        'seller_state_code'   => '32',
    ];

    // === YOUR CLIENT LIST ===
    private static array $clients = [
        [
            'name' => 'Acme Corp',
            'email' => 'accounts@acme.com',
            'address' => '123 Industrial Estate, Mumbai, Maharashtra',
            'gst_number' => '27AAAAA0000A1Z5',
            'state' => 'Maharashtra',
            'state_code' => '27'
        ],
        [
            'name' => 'Wayne Enterprises',
            'email' => 'alfred@wayne.com',
            'address' => '1007 Mountain Drive, Gotham, Gujarat',
            'gst_number' => '24BBBBB1111B1Z6',
            'state' => 'Gujarat',
            'state_code' => '24'
        ],
        [
            'name' => 'Stark Industries',
            'email' => 'pepper@stark.com',
            'address' => 'Stark Tower, New York, Delhi',
            'gst_number' => '07CCCCC2222C1Z7',
            'state' => 'Delhi',
            'state_code' => '07'
        ],
    ];

    // === YOUR INVENTORY / PRODUCTS ===
    private static array $inventory = [
        [
            'name' => 'Web Development Service',
            'rate' => 50000.00,
            'hsn_code' => '9983',
            'unit' => 'Service'
        ],
        [
            'name' => 'Annual Maintenance Contract',
            'rate' => 12000.00,
            'hsn_code' => '9987',
            'unit' => 'Year'
        ],
        [
            'name' => 'Hosting Server (Basic)',
            'rate' => 5000.00,
            'hsn_code' => '998311',
            'unit' => 'Year'
        ],
    ];
    public static function getSellerDetails(): array
    {
        return self::$seller;
    }
    public static function searchClients(string $query): array
    {
        return array_filter(self::$clients, function ($client) use ($query) {
            return str_contains(strtolower($client['name']), strtolower($query));
        });
    }

    public static function searchInventory(string $query): array
    {
        return array_filter(self::$inventory, function ($item) use ($query) {
            return str_contains(strtolower($item['name']), strtolower($query));
        });
    }
}
