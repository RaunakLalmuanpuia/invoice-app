<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class MockDataService
{
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

    // === DYNAMIC CLIENT MANAGEMENT ===

    private static function getClients(): array
    {
        // 1. Check if file exists, if not create it with default data
        if (!Storage::exists('data/clients.json')) {
            $defaults = [
                [
                    'name' => 'Acme Corp',
                    'email' => 'accounts@acme.com',
                    'address' => '123 Industrial Estate, Mumbai',
                    'gst_number' => '27AAAAA0000A1Z5',
                    'state' => 'Maharashtra',
                    'state_code' => '27'
                ]
            ];
            Storage::put('data/clients.json', json_encode($defaults, JSON_PRETTY_PRINT));
        }

        return json_decode(Storage::get('data/clients.json'), true);
    }

    public static function searchClients(string $query): array
    {
        $clients = self::getClients();
        return array_filter($clients, fn($client) => str_contains(strtolower($client['name']), strtolower($query)));
    }

    public static function addClient(array $newClient): void
    {
        $clients = self::getClients();

        // Prevent duplicates (simple check by name)
        foreach ($clients as $client) {
            if (strtolower($client['name']) === strtolower($newClient['name'])) {
                return;
            }
        }

        $clients[] = $newClient;
        Storage::put('data/clients.json', json_encode($clients, JSON_PRETTY_PRINT));
    }
    private static function getInventory(): array
    {
        // 1. Check if file exists, if not create it with default data
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
        $inventory = self::getInventory();
        return array_filter($inventory, fn($item) => str_contains(strtolower($item['name']), strtolower($query)));
    }

    public static function addInventoryItem(array $newItem): void
    {
        $inventory = self::getInventory();

        // Prevent duplicates (simple check by name)
        foreach ($inventory as $item) {
            if (strtolower($item['name']) === strtolower($newItem['name'])) {
                return;
            }
        }

        $inventory[] = $newItem;
        Storage::put('data/inventory.json', json_encode($inventory, JSON_PRETTY_PRINT));
    }
}
