<?php

namespace App\Ai\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class StartNewInvoice implements Tool
{
    public function description(): string
    {
        return 'Resets the workspace to start a completely new invoice. Use this when the user says "start over", "new invoice", or "create another one".';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'confirmation' => $schema->boolean()->description('Always set to true'),
        ];
    }

    public function handle(Request $request): string
    {
        return json_encode([
            'action' => 'reset_invoice',
            'success' => true
        ]);
    }
}
