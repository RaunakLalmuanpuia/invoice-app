<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Import Str for singularization

class MockDataService
{
    // Seller: A Large Department Store
    private static $seller = [
        'seller_company_name' => 'Grand Central Retail Ltd',
        'seller_gst_number'   => '27AAAAA7777A1Z5',
        'seller_state'        => 'Maharashtra',
        'seller_state_code'   => '27',
    ];

    public static function getSellerDetails(): array
    {
        return self::$seller;
    }

    // === SMART SEARCH HELPER ===
    private static function fuzzySearch(array $items, string $query): array
    {
        $queryWords = explode(' ', strtolower($query));

        return array_filter($items, function ($item) use ($queryWords) {
            $itemName = strtolower($item['name']);
            foreach ($queryWords as $word) {
                // Ignore small words like "and", "for", "the"
                if (strlen($word) > 2 && str_contains($itemName, Str::singular($word))) {
                    return true;
                }
            }
            return false;
        });
    }

    // === CLIENTS (Corporate Accounts & Loyalty Customers) ===
    private static function getClients(): array
    {
        if (!Storage::exists('data/clients.json')) {
            $defaults = [
                [
                    'name' => 'Infosys Guest House',
                    'email' => 'admin@infosys-gh.com',
                    'address' => 'Electronic City, Bangalore, Karnataka',
                    'gst_number' => '29AAAAA5678A1Z5',
                    'state' => 'Karnataka',
                    'state_code' => '29'
                ],
                [
                    'name' => 'Marriott Hotel Supplies',
                    'email' => 'purchase@marriott.com',
                    'address' => 'Juhu Tara Road, Mumbai, Maharashtra',
                    'gst_number' => '27BBBBB1234B1Z6',
                    'state' => 'Maharashtra',
                    'state_code' => '27'
                ],
                [
                    'name' => 'Urban Clap Services',
                    'email' => 'partners@urbanclap.com',
                    'address' => 'Udyog Vihar, Gurgaon, Haryana',
                    'gst_number' => '06CCCCC9876C1Z7',
                    'state' => 'Haryana',
                    'state_code' => '06'
                ],
            ];
            Storage::put('data/clients.json', json_encode($defaults, JSON_PRETTY_PRINT));
        }

        return json_decode(Storage::get('data/clients.json'), true);
    }

    public static function searchClients(string $query): array
    {
        return self::fuzzySearch(self::getClients(), $query);
    }

    public static function addClient(array $newClient): void
    {
        $clients = self::getClients();
        $clients[] = $newClient;
        Storage::put('data/clients.json', json_encode($clients, JSON_PRETTY_PRINT));
    }

    // === INVENTORY (Mixed Departments) ===
    private static function getInventory(): array
    {
        if (!Storage::exists('data/inventory.json')) {
            $defaults = [
                // Electronics Dept
                ['name' => 'Samsung 55" 4K Smart TV', 'rate' => 54999.00, 'hsn_code' => '8528', 'unit' => 'Unit'],
                ['name' => 'Philips Mixer Grinder 750W', 'rate' => 3400.00, 'hsn_code' => '8509', 'unit' => 'Unit'],

                // Grocery / FMCG
                ['name' => 'Royal Basmati Rice (5kg Bag)', 'rate' => 850.00, 'hsn_code' => '1006', 'unit' => 'Bag'],
                ['name' => 'Fortune Sunflower Oil (1L)', 'rate' => 145.00, 'hsn_code' => '1512', 'unit' => 'Pouch'],

                // Apparel / Clothing
                ['name' => 'Levi\'s Men\'s Denim Jeans', 'rate' => 2499.00, 'hsn_code' => '6203', 'unit' => 'Pair'],
                ['name' => 'Cotton Polo T-Shirt (Bulk)', 'rate' => 450.00, 'hsn_code' => '6105', 'unit' => 'Piece'],

                // Home & Living
                ['name' => 'Prestige Pressure Cooker (5L)', 'rate' => 1800.00, 'hsn_code' => '7615', 'unit' => 'Unit'],
            ];
            Storage::put('data/inventory.json', json_encode($defaults, JSON_PRETTY_PRINT));
        }
        return json_decode(Storage::get('data/inventory.json'), true);
    }

    public static function searchInventory(string $query): array
    {
        return self::fuzzySearch(self::getInventory(), $query);
    }

    public static function addInventoryItem(array $newItem): void
    {
        $inventory = self::getInventory();
        $inventory[] = $newItem;
        Storage::put('data/inventory.json', json_encode($inventory, JSON_PRETTY_PRINT));
    }
}
