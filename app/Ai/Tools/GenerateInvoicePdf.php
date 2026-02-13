<?php

namespace App\Ai\Tools;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf implements Tool
{
    public function description(): string
    {
        return 'Generates a PDF Invoice. Use is_draft=true for previews. Use is_draft=false ONLY when the user explicitly confirms the preview is correct.';
    }

    public function handle(Request $request): string
    {
        try {
            $data = $request->all();
            $isDraft = $data['is_draft'] ?? true;

            // Process Line Items with HSN codes and units
            $rawItems = isset($data['line_items_json'])
                ? json_decode($data['line_items_json'], true)
                : [];

            $lineItems = [];
            $subtotal = 0;

            foreach ($rawItems as $item) {
                $qty = (float)($item['quantity'] ?? 0);
                $rate = (float)($item['rate'] ?? 0);
                $amount = $qty * $rate;

                $lineItems[] = [
                    'description' => $item['description'] ?? 'Service',
                    'hsn_code'    => $item['hsn_code'] ?? null,
                    'quantity'    => $qty,
                    'unit'        => $item['unit'] ?? 'Nos',
                    'rate'        => $rate,
                    'amount'      => $amount,
                ];
                $subtotal += $amount;
            }

            // Calculate GST (18% split as 9% CGST + 9% SGST)
            $gstAmount = $subtotal * 0.18;
            $totalAmount = $subtotal + $gstAmount;

            // === FIX START: INTELLIGENT NUMBER GENERATION ===
            $providedNumber = $data['invoice_number'] ?? null;

            if ($isDraft) {
                // Always generate a new random draft ID to prevent caching issues
                $invoiceNumber = 'DRAFT-' . time();
            } else {
                // FINALIZATION LOGIC:
                // If no number provided, OR if the provided number is a "DRAFT", generate a real INV- number.
                if (empty($providedNumber) || str_starts_with($providedNumber, 'DRAFT-')) {
                    $invoiceNumber = 'INV-' . time();
                } else {
                    // Respect manual overrides only if they don't look like drafts
                    $invoiceNumber = $providedNumber;
                }
            }
            // === FIX END ===

            // Prepare invoice data for the view
            $invoiceData = (object)[
                'invoice_number' => $invoiceNumber,
                'invoice_date' => new \DateTime($data['invoice_date']),
                'due_date' => new \DateTime($data['due_date']),
                'seller_company_name' => $data['seller_company_name'],
                'seller_gst_number' => $data['seller_gst_number'] ?? null,
                'seller_state' => $data['seller_state'] ?? null,
                'seller_state_code' => $data['seller_state_code'] ?? null,
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_address' => $data['client_address'],
                'client_gst_number' => $data['client_gst_number'] ?? null,
                'client_state' => $data['client_state'] ?? null,
                'client_state_code' => $data['client_state_code'] ?? null,
                'line_items' => $lineItems,
                'subtotal' => $subtotal,
                'gst_amount' => $gstAmount,
                'total_amount' => $totalAmount,
                'payment_terms' => $data['payment_terms'] ?? 'Net 30',
                'bank_account_name' => $data['bank_account_name'] ?? null,
                'bank_account_number' => $data['bank_account_number'] ?? null,
                'bank_ifsc_code' => $data['bank_ifsc_code'] ?? null,
            ];

            // Generate the PDF
            $pdf = Pdf::loadView('invoices.sales_tax_template', ['invoice' => $invoiceData]);

            // --- 5. Determine Storage Path ---
            if ($isDraft) {
                // Save to temporary folder
                $path = 'temp/' . $invoiceNumber . '.pdf';
            } else {
                // Save to permanent folder
                $path = 'invoices/' . $invoiceNumber . '.pdf';
            }

            Storage::put($path, $pdf->output());
            // Generate public URL
            $url = Storage::url($path);

            return json_encode([
                'success' => true,
                'pdf_generated' => true,
                'is_draft' => $isDraft,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'pdf_path' => $path,
                'pdf_url' => $url,
            ]);

        } catch (\Exception $e) {
            \Log::error('PDF Tool Error: ' . $e->getMessage());
            return json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'is_draft' => $schema->boolean()->description('Set to TRUE for previews. Set to FALSE only when user confirms.'),
            'invoice_number' => $schema->string()->description('Auto-generated if left empty'),
            // Seller Details
            'seller_company_name' => $schema->string()->required()->description('Your company name'),
            'seller_gst_number' => $schema->string()->nullable()->description('Your 15 character GST number'),
            'seller_state' => $schema->string()->nullable()->description('Your state name (e.g., Kerala, Maharashtra)'),
            'seller_state_code' => $schema->string()->nullable()->description('Your 2-digit state code (e.g., 32, 27)'),
            // Client Details
            'client_name' => $schema->string()->required(),
            'client_email' => $schema->string()->required(),
            'client_address' => $schema->string()->required(),
            'client_gst_number' => $schema->string()->nullable()->description('Client 15 character GST number'),
            'client_state' => $schema->string()->nullable()->description('Client state name (e.g., Kerala, Maharashtra)'),
            'client_state_code' => $schema->string()->nullable()->description('Client 2-digit state code (e.g., 32, 27)'),

            // Invoice Details
            'invoice_date' => $schema->string()->required(),
            'due_date' => $schema->string()->required(),
            'line_items_json' => $schema->string()->required()->description('JSON array of items with description, hsn_code, quantity, unit, rate'),

            // Payment Details (Optional)
            'payment_terms' => $schema->string()->nullable()->description('e.g., Net 30, Due on Receipt'),
            'bank_account_name' => $schema->string()->nullable(),
            'bank_account_number' => $schema->string()->nullable(),
            'bank_ifsc_code' => $schema->string()->nullable(),

            'conversation_id' => $schema->string()->nullable(),
        ];
    }
}
