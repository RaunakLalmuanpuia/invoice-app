<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Import Str for singularization

class MockDataService
{
    // ... (keep seller details same) ...
    private static $seller = [
        'seller_company_name' => 'Tili Technologies',
        'seller_gst_number'   => '32AAAAA8888A1Z5',
        'seller_state'        => 'Kerala',
        'seller_state_code'   => '32',
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
            // Check if ANY word from the query exists in the item name
            // (e.g. "Hosting" from "Hosting Servers" will match "Hosting Server")
            foreach ($queryWords as $word) {
                // Ignore small words like "and", "for", "the"
                if (strlen($word) > 2 && str_contains($itemName, Str::singular($word))) {
                    return true;
                }
            }
            return false;
        });
    }

    // === CLIENTS ===
    private static function getClients(): array
    {
        if (!Storage::exists('data/clients.json')) {
            $defaults = [
                [
                    'name' => 'Acme Corp',
                    'email' => 'accounts@acme.com',
                    'address' => '123 Industrial Estate, Mumbai, Maharashtra',
                    'gst_number' => '27AAAAA0000A1Z5',
                    'state' => 'Maharashtra',
                    'state_code' => '27'
                ],
                // ADDED BACK WAYNE & STARK
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

    // === INVENTORY ===
    private static function getInventory(): array
    {
        if (!Storage::exists('data/inventory.json')) {
            $defaults = [
                ['name' => 'Web Development Service', 'rate' => 50000.00, 'hsn_code' => '9983', 'unit' => 'Service'],
                ['name' => 'Annual Maintenance Contract', 'rate' => 12000.00, 'hsn_code' => '9987', 'unit' => 'Year'],
                ['name' => 'Hosting Server (Basic)', 'rate' => 5000.00, 'hsn_code' => '998311', 'unit' => 'Year'],
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
