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
        $type = $request->type ?? $request->all()['type'] ?? null;
        $query = $request->query ?? $request->all()['query'] ?? '';

        // Check if the user is asking for a list/all
        $isListRequest = empty($query) || in_array(strtolower($query), ['all', 'list', 'show all']);

        if ($type === 'client') {
            $results = MockDataService::searchClients($query);

            if (empty($results)) {
                return json_encode(['found' => false, 'message' => 'No clients found.']);
            }

            // If it's a list request, return a summary of all clients
            if ($isListRequest) {
                return json_encode([
                    'found' => true,
                    'is_list' => true,
                    'clients' => array_values($results)
                ]);
            }

            // Otherwise, return the specific match for invoice state
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
                return json_encode(['found' => false, 'message' => 'Inventory is empty.']);
            }

            return json_encode([
                'found' => true,
                'is_list' => $isListRequest,
                'items' => array_values($results)
            ]);
        }

        return json_encode(['error' => 'Invalid type. Use "client" or "product".']);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Either "client" or "product"'),
            'query' => $schema->string()->description('The name to search for (e.g. "Acme", "Hosting")'),
        ];
    }
}
