<?php

namespace App\Http\Controllers;

use App\Ai\Agents\InvoiceAssistant;
use App\Services\MockDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InvoiceChatController extends Controller
{
    public function index()
    {
        // Use storage_path to get the full system path
        $fullPath = storage_path('app/private/invoices/all_invoices.json');

        if (file_exists($fullPath)) {
            $jsonContent = file_get_contents($fullPath);
            $invoices = json_decode($jsonContent, true);
        } else {
            $invoices = [];
            // Debug: This will show you exactly where PHP is looking
            // dd("File not found at: " . $fullPath);
        }

        return inertia('Invoices/Index', [
            'invoices' => array_values($invoices ?? [])
        ]);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'nullable|string',
        ]);

        try {
            $stateKey = 'invoice_state_' . ($request->conversation_id ?? 'new');
            $invoiceState = Cache::get($stateKey, []);

            if (empty($invoiceState) || !isset($invoiceState['seller_company_name'])) {
                $sellerData = MockDataService::getSellerDetails();
                $invoiceState = array_merge($invoiceState, $sellerData);

                // Optional: Update cache immediately so it persists
                Cache::put($stateKey, $invoiceState, now()->addHours(24));
            }

            $agent = new InvoiceAssistant($invoiceState);

            if ($request->conversation_id) {
                $response = $agent->continue($request->conversation_id)->prompt($request->message);
            } else {
                $response = $agent->prompt($request->message);
            }

            // Extract tool results
            $updates = $this->processToolResults($response);

            // --- SCENARIO 1: START NEW INVOICE ---
            // The user said "New Invoice". We wipe the state, but we DO NOT delete the old file.
            if (!empty($updates['reset'])) {
                Cache::forget($stateKey);

                return response()->json([
                    'response' => $response->text, // "Sure, starting fresh..."
                    'conversation_id' => $response->conversationId,
                    'invoice_data' => [], // Empty state
                    'pdf_url' => null,
                    'invoice_number' => null,
                ]);
            }

            // --- SCENARIO 2: EDITING EXISTING INVOICE ---
            // The user changed data (but didn't say "new"). The old PDF is now invalid.
            if (!empty($updates['saved_data'])) {
                $invoiceState = array_merge($invoiceState, $updates['saved_data']);

                // Delete the OLD PDF because it contains outdated info
                if (isset($invoiceState['current_pdf_path']) && Storage::exists($invoiceState['current_pdf_path'])) {
                    Storage::delete($invoiceState['current_pdf_path']);
                    unset($invoiceState['current_pdf_path']);
                    unset($invoiceState['pdf_url']);
                }
            }

            // --- SCENARIO 3: GENERATING PDF ---
            $pdfUrl = null;
            $invoiceNumber = $invoiceState['invoice_number'] ?? null;
            $isFinalInvoice = false; // <-- NEW FLAG

            if (!empty($updates['pdf_result'])) {
                $pdfData = $updates['pdf_result'];
                if ($pdfData['pdf_generated']) {
                    $invoiceNumber = $pdfData['invoice_number'];
                    $invoiceState['current_pdf_path'] = $pdfData['pdf_path'];
                    $invoiceState['invoice_number'] = $invoiceNumber;
                    $pdfUrl = route('invoices.download', ['filename' => basename($pdfData['pdf_path'])]);

                    // <-- NEW: Check if this was a finalized invoice
                    if (isset($pdfData['is_draft']) && $pdfData['is_draft'] === false) {
                        $isFinalInvoice = true;
                    }
                }
            }

            if (empty($pdfUrl) && isset($invoiceState['current_pdf_path'])) {
                $pdfUrl = route('invoices.download', ['filename' => basename($invoiceState['current_pdf_path'])]);
            }

            // === NEW: AUTO-RESET ON FINALIZATION ===
            if ($isFinalInvoice) {
                // Wipe the backend state so the next message starts completely fresh
                Cache::forget($stateKey);

                return response()->json([
                    'response' => $response->text,
                    'conversation_id' => $response->conversationId, // Keep chat history flowing
                    'invoice_data' => [], // Give the frontend an empty state
                    'pdf_url' => $pdfUrl, // Give them the final download link!
                    'invoice_number' => $invoiceNumber,
                ]);
            }

            // Save state (Only if NOT final)
            Cache::put($stateKey, $invoiceState, now()->addHours(24));

            return response()->json([
                'response' => $response->text,
                'conversation_id' => $response->conversationId,
                'invoice_data' => $invoiceState,
                'pdf_url' => $pdfUrl,
                'invoice_number' => $invoiceNumber,
            ]);

        } catch (\Exception $e) {
            Log::error('Invoice Chat Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'response' => 'I encountered a system error. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error',
            ], 500);
        }
    }

    private function processToolResults($response): array
    {
        $results = ['saved_data' => [], 'pdf_result' => [], 'reset' => false];

        if (isset($response->toolResults)) {
            foreach ($response->toolResults as $toolResult) {
                $data = json_decode($toolResult->result, true);

                if (($data['action'] ?? '') === 'save_draft') {
                    $results['saved_data'] = array_merge($results['saved_data'], $data['data']);
                }
                if (($data['action'] ?? '') === 'reset_invoice') {
                    $results['reset'] = true;
                }
                if (($data['pdf_generated'] ?? false) === true) {
                    $results['pdf_result'] = $data;
                }
            }
        }
        return $results;
    }

    public function download($filename)
    {
        // 1. Check if it is a Final Invoice
        $finalPath = 'invoices/' . $filename;
        if (Storage::exists($finalPath)) {
            return Storage::download($finalPath, $filename);
        }

        // 2. Check if it is a Draft
        $draftPath = 'temp/' . $filename;
        if (Storage::exists($draftPath)) {
            return Storage::download($draftPath, $filename);
        }

        abort(404, 'File not found');
    }
    public function create()
    {
        return inertia('Invoices/CreateInvoice');
    }
}
