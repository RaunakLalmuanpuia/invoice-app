<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GenerateInvoicePdf;
use App\Ai\Tools\SaveInvoiceDraft;
use App\Ai\Tools\StartNewInvoice;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Promptable;

#[Provider('openai')]
class InvoiceAssistant implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    private array $invoiceState = [];

    public function __construct(array $invoiceState = [])
    {
        $this->invoiceState = $invoiceState;
    }

    public function instructions(): string
    {
        // Convert state to readable text for the AI
        $stateInfo = !empty($this->invoiceState)
            ? "\n\n=== CURRENT KNOWN DATA ===\n" . json_encode($this->invoiceState, JSON_PRETTY_PRINT) . "\n=========================\n"
            : "\n\n=== CURRENT KNOWN DATA ===\n(No data collected yet)\n=========================\n";

        return <<<PROMPT
        You are an expert GST Tax Invoice assistant for Indian businesses.

        {$stateInfo}

        **YOUR PRIMARY GOAL:**
        Collect missing information to generate a valid GST invoice.

        **RULES FOR HANDLING DATA:**
        1. **Check 'CURRENT KNOWN DATA' first.** If a field exists there, DO NOT ask for it again.
        2. **When user provides info:** You MUST call the `SaveInvoiceDraft` tool immediately to save it.
        3. **After saving:** Acknowledge what was saved, then ask for the *next* missing field from the required list below.

        **REQUIRED FIELDS SEQUENCE:**
        1. Seller Details (Name, GSTIN, State)
        2. Client Details (Name, Email, Address, GSTIN, State)
        3. Invoice Dates (Date, Due Date)
        4. Line Items (Description, Qty, Rate, HSN)

        **FINAL STEP:**
        Once all fields are present in 'CURRENT KNOWN DATA', show a summary and ask for confirmation to generate the PDF.
        Only call `GenerateInvoicePdf` if the user explicitly confirms (e.g., "Yes", "Create it").
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new SaveInvoiceDraft,   // <--- The AI uses this to "remember"
            new GenerateInvoicePdf, // <--- The AI uses this to "finish"
            new StartNewInvoice,
        ];
    }
}
