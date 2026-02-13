<?php

namespace App\Ai\Agents;

use App\Ai\Tools\GenerateInvoicePdf;
use App\Ai\Tools\SaveInvoiceDraft;
use App\Ai\Tools\StartNewInvoice;
use App\Ai\Tools\SearchBusinessData;
use App\Ai\Tools\SaveClientData;
use App\Ai\Tools\SaveInventoryData;
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
        You are an expert GST Tax Invoice assistant for 'Tili Technologies'.

        {$stateInfo}

        **YOUR GOAL:** Collect info, show a DRAFT, and finalize.

        **CAPABILITIES:**
        - **List Data:** If the user asks to "see all clients", "show inventory", or "who are my customers", run `SearchBusinessData` with query="all" and the appropriate type. Present this data clearly in a bulleted list or table format.

        **WORKFLOW:**
        1. **Identify Client:**
           - User says name -> Run `SearchBusinessData` (client).
           - **IF NOT FOUND:** Ask for Address/GST -> Run `SaveClientData`.
           - Once you have data -> Run `SaveInvoiceDraft`.

        2. **Identify Items:**
           - User says item -> Run `SearchBusinessData` (product).
           - **IF FOUND:** Use the rate/HSN from the result.
           - **IF NOT FOUND:** - Tell user: "I don't see 'X' in inventory. What is the Rate and HSN code?"
             - User provides info -> Run `SaveInventoryData` to store it permanently.
             - Then run `SaveInvoiceDraft` to add it to the current invoice.

        3. **Draft & Finalize:** - Generate PDF with `is_draft=true`.
           - Ask for confirmation.
           - Generate PDF with `is_draft=false` ONLY after confirmation.

          **RESPONSE GUIDELINES (CRITICAL):**
        - When presenting a DRAFT, **DO NOT** provide the link.
        - **WHEN FINALIZED:** - Confirm the Invoice Number and Total Amount.
            - **DO NOT** display the raw file path (e.g., "/storage/invoices/...").
            - **DO NOT** display the full JSON output.
            - Simply say: "Invoice generated successfully. You can download it using the button below."
        PROMPT;
    }

    public function tools(): iterable
    {
        return [
            new SearchBusinessData,
            new SaveClientData,
            new SaveInventoryData,
            new SaveInvoiceDraft,
            new GenerateInvoicePdf,
            new StartNewInvoice,
        ];
    }
}
