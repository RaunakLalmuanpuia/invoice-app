<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GenerateInvoicePdf;
use App\Ai\Tools\SaveInvoiceDraft;
use App\Ai\Tools\StartNewInvoice;
use App\Ai\Tools\SearchBusinessData;
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
        $stateInfo = !empty($this->invoiceState)
            ? "\n\n=== CURRENT KNOWN DATA ===\n" . json_encode($this->invoiceState, JSON_PRETTY_PRINT) . "\n=========================\n"
            : "\n\n=== CURRENT KNOWN DATA ===\n(No data collected yet)\n=========================\n";

        return <<<PROMPT
        You are an expert GST Tax Invoice assistant.

        {$stateInfo}

        **YOUR GOAL:** Collect info and generate an invoice.

        **CRITICAL WORKFLOW:**
        1. **CHECK RECORDS FIRST:** - If the user mentions a Client Name (e.g., "Invoice for Acme"), run `SearchBusinessData` (type='client').
           - If found, call `SaveInvoiceDraft` with the returned details immediately. DO NOT ask the user for address/GST if found.

        2. **CHECK INVENTORY:**
           - If the user mentions an Item (e.g., "Add Hosting"), run `SearchBusinessData` (type='product').
           - If found, use the Rate and HSN from the result to add the line item.

        3. **MANUAL FALLBACK:** - Only ask the user for details (Address, GST, Rate) if `SearchBusinessData` returns "found": false.

        **REQUIRED FIELDS SEQUENCE:**
        1. Client Details (Name -> Search -> Save)
        2. Line Items (Item Name -> Search -> Save)
        3. Invoice Dates

        **FINAL STEP:**
        Once fields are present in 'CURRENT KNOWN DATA', ask for confirmation to generate PDF.
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new SearchBusinessData,
            new SaveInvoiceDraft,
            new GenerateInvoicePdf,
            new StartNewInvoice,
        ];
    }
}
