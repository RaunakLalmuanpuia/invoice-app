<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str; // Import Str for singularization

class MockDataService
{
    private static $clientPath = 'data/clients.json';

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
        $cleanQuery = trim(strtolower($query));

        // === NEW: Return all records if requested ===
        if (empty($cleanQuery) || $cleanQuery === 'all') {
            return $items;
        }

        $queryWords = explode(' ', $cleanQuery);

        return array_filter($items, function ($item) use ($queryWords) {
            $itemName = strtolower($item['name']);
            foreach ($queryWords as $word) {
                if (strlen($word) > 2 && str_contains($itemName, \Illuminate\Support\Str::singular($word))) {
                    return true;
                }
            }
            return false;
        });
    }

    // === READ ALL ===
    public static function getClients(): array
    {
        if (!Storage::exists(self::$clientPath)) {
            $defaults = [
                ['name' => 'Infosys Guest House', 'email' => 'admin@infosys-gh.com', 'address' => 'Electronic City, Bangalore, Karnataka', 'gst_number' => '29AAAAA5678A1Z5', 'state' => 'Karnataka', 'state_code' => '29'],
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
            self::saveClients($defaults);
        }

        return json_decode(Storage::get(self::$clientPath), true) ?? [];
    }

    // === CREATE ===
    public static function addClient(array $newClient): void
    {
        $clients = self::getClients();
        $clients[] = $newClient;
        self::saveClients($clients);
    }

    // === UPDATE ===
    /**
     * Updates a client based on their email (acting as a unique ID)
     */
    public static function updateClient(string $email, array $updatedData): bool
    {
        $clients = self::getClients();
        $found = false;

        foreach ($clients as &$client) {
            if ($client['email'] === $email) {
                // Merge existing data with new data
                $client = array_merge($client, $updatedData);
                $found = true;
                break;
            }
        }

        if ($found) {
            self::saveClients($clients);
        }

        return $found;
    }

    // === DELETE ===
    public static function deleteClient(string $email): bool
    {
        $clients = self::getClients();
        $initialCount = count($clients);

        // Filter out the client with the matching email
        $clients = array_filter($clients, function ($client) use ($email) {
            return $client['email'] !== $email;
        });

        // Re-index array to prevent JSON from turning into an object
        $clients = array_values($clients);

        if (count($clients) < $initialCount) {
            self::saveClients($clients);
            return true;
        }

        return false;
    }

    // === SEARCH ===
    public static function searchClients(string $query): array
    {
        return self::fuzzySearch(self::getClients(), $query);
    }

    // Helper to persist data
    private static function saveClients(array $clients): void
    {
        Storage::put(self::$clientPath, json_encode($clients, JSON_PRETTY_PRINT));
    }

    // === INVENTORY (Mixed Departments) ===
    public static function getInventory(): array
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
    public static function updateInventoryItem(string $originalName, array $updatedData): bool
    {
        $inventory = self::getInventory();
        $found = false;

        foreach ($inventory as &$item) {
            if ($item['name'] === $originalName) {
                $item = array_merge($item, $updatedData);
                $found = true;
                break;
            }
        }

        if ($found) {
            Storage::put('data/inventory.json', json_encode($inventory, JSON_PRETTY_PRINT));
        }
        return $found;
    }

    public static function deleteInventoryItem(string $name): bool
    {
        $inventory = self::getInventory();
        $filtered = array_filter($inventory, fn($item) => $item['name'] !== $name);

        if (count($filtered) < count($inventory)) {
            Storage::put('data/inventory.json', json_encode(array_values($filtered), JSON_PRETTY_PRINT));
            return true;
        }
        return false;
    }
}
