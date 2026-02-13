<?php

namespace App\Ai\Tools;

use App\Services\MockDataService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SearchBusinessData implements Tool
{
    public function description(): string
    {
        return 'Searches your internal records for existing Clients or Inventory Items. Use this BEFORE asking the user for details.';
    }

    public function handle(Request $request): string
    {
        // FIX: Use property access or ->all() instead of ->input()
        $type = $request->type ?? $request->all()['type'] ?? null;
        $query = $request->query ?? $request->all()['query'] ?? '';

        if ($type === 'client') {
            $results = MockDataService::searchClients($query);

            if (empty($results)) {
                return json_encode(['found' => false, 'message' => 'Client not found in records.']);
            }

            // Return the first match, formatted for the Invoice State
            $client = array_values($results)[0];
            return json_encode([
                'found' => true,
                'data_to_save' => [
                    'client_name' => $client['name'],
                    'client_email' => $client['email'],
                    'client_address' => $client['address'],
                    'client_gst_number' => $client['gst_number'],
                    'client_state' => $client['state'],
                    'client_state_code' => $client['state_code'],
                ]
            ]);
        }

        if ($type === 'product') {
            $results = MockDataService::searchInventory($query);

            if (empty($results)) {
                return json_encode(['found' => false, 'message' => 'Item not found in inventory.']);
            }

            // Return all matches so AI can pick the best one
            return json_encode([
                'found' => true,
                'items' => array_values($results)
            ]);
        }

        return json_encode(['error' => 'Invalid search type. Use "client" or "product".']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Either "client" or "product"'),
            'query' => $schema->string()->description('The name to search for (e.g. "Acme", "Hosting")'),
        ];
    }
}
