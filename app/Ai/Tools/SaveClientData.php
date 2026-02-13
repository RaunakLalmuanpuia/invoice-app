<?php

namespace App\Ai\Tools;

use App\Services\MockDataService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;

class SaveClientData implements Tool
{
    public function description(): string
    {
        return 'Saves a NEW client to the permanent records. Use this ONLY after the user has provided all client details (Name, Email, Address, GST, State).';
    }

    public function handle(Request $request): string
    {
        $data = $request->all();

        // Validate minimal requirements
        if (empty($data['name']) || empty($data['address'])) {
            return json_encode(['error' => 'Client Name and Address are required to save.']);
        }

        $newClient = [
            'name' => $data['name'],
            'email' => $data['email'] ?? '',
            'address' => $data['address'],
            'gst_number' => $data['gst_number'] ?? '',
            'state' => $data['state'] ?? '',
            'state_code' => $data['state_code'] ?? '',
        ];

        MockDataService::addClient($newClient);

        return json_encode([
            'success' => true,
            'message' => "Client '{$newClient['name']}' saved to records.",
            'client_data' => $newClient
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required(),
            'email' => $schema->string()->description('Client email address'),
            'address' => $schema->string()->required(),
            'gst_number' => $schema->string()->description('15-digit GSTIN'),
            'state' => $schema->string()->description('State Name (e.g. Kerala)'),
            'state_code' => $schema->string()->description('2-digit State Code (e.g. 32)'),
        ];
    }
}
