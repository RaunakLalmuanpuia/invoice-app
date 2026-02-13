<?php

namespace App\Ai\Tools;

use App\Services\MockDataService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SaveInventoryData implements Tool
{
    public function description(): string
    {
        return 'Saves a NEW product or service to the permanent inventory records. Use this ONLY after the user provides details (Name, Rate, HSN, Unit).';
    }

    public function handle(Request $request): string
    {
        $data = $request->all();

        // Validate minimal requirements
        if (empty($data['name']) || empty($data['rate'])) {
            return json_encode(['error' => 'Item Name and Rate are required to save.']);
        }

        $newItem = [
            'name' => $data['name'],
            'rate' => (float) $data['rate'],
            'hsn_code' => $data['hsn_code'] ?? '9983', // Default SAC code for generic service
            'unit' => $data['unit'] ?? 'Nos',
        ];

        MockDataService::addInventoryItem($newItem);

        return json_encode([
            'success' => true,
            'message' => "Item '{$newItem['name']}' saved to inventory.",
            'item_data' => $newItem
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->description('Product or Service Name'),
            'rate' => $schema->number()->required()->description('Price per unit'),
            'hsn_code' => $schema->string()->description('HSN or SAC code (e.g., 9983)'),
            'unit' => $schema->string()->description('Unit of measurement (e.g., Nos, Kg, Year)'),
        ];
    }
}
