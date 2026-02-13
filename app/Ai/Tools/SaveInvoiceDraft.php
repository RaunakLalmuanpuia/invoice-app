<?php

namespace App\Ai\Tools;

use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class SaveInvoiceDraft implements Tool
{
    public function description(): string
    {
        return 'Saves extracted invoice details (seller, client, items, dates) to the system memory. Call this immediately when the user provides any new information.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            // Seller Details
            'seller_company_name' => $schema->string()->nullable(),
            'seller_gst_number'   => $schema->string()->nullable(),
            'seller_state'        => $schema->string()->nullable(),
            'seller_state_code'   => $schema->string()->nullable(),

            // Client Details
            'client_name'         => $schema->string()->nullable(),
            'client_email'        => $schema->string()->nullable(),
            'client_address'      => $schema->string()->nullable(),
            'client_gst_number'   => $schema->string()->nullable(),
            'client_state'        => $schema->string()->nullable(),
            'client_state_code'   => $schema->string()->nullable(),

            // Invoice Details
            'invoice_date'        => $schema->string()->nullable(),
            'due_date'            => $schema->string()->nullable(),

            // Line Items (as JSON string)
            'line_items_json'     => $schema->string()->nullable()->description('JSON array of items. Example: [{"description": "Web Design", "quantity": 1, "rate": 5000, "unit": "Nos"}]'),

            // Bank/Payment
            'payment_terms'       => $schema->string()->nullable(),
            'bank_account_name'   => $schema->string()->nullable(),
            'bank_account_number' => $schema->string()->nullable(),
            'bank_ifsc_code'      => $schema->string()->nullable(),
        ];
    }

    public function handle(Request $request): string
    {
        // We simply return the data. The Controller will catch this
        // and merge it into the Cache/Session.
        return json_encode([
            'action' => 'save_draft',
            'data'   => array_filter($request->all(), fn($value) => !is_null($value))
        ]);
    }
}
