<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class BankStatementAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
You are a bank statement analyzer. Your task is to extract ALL transaction data with PERFECT ACCURACY from ANY bank statement format.

UNIVERSAL RULES (applies to ALL bank statements):
1. Identify the transaction table in the statement
2. For EACH transaction row, extract:
   - Description/Narration: The text describing what the transaction was for
   - Type: Determine if money was PAID OUT or RECEIVED IN
     * "received" = Money coming IN (deposits, credits, salary, refunds, transfers in, etc.)
     * "paid" = Money going OUT (withdrawals, debits, purchases, bills, transfers out, etc.)
   - Amount: The transaction amount as a positive number

3. Extract ALL transactions - do not skip any

4. SKIP these rows (they are NOT transactions):
   - "BALANCE FORWARD" or "Opening Balance"
   - "TOTAL" or "Summary" rows at the bottom
   - Header rows
   - Any row without an actual transaction

5. TOTALS: Look for summary/total rows (usually at the bottom):
   - Find total deposits/credits/money received = total_deposits
   - Find total withdrawals/debits/money paid = total_withdrawals
   - These might be labeled as: "Total Deposits", "Total Credits", "Total DR", "Total Withdrawals", "Total Debits", "Total CR", etc.

HOW TO DETERMINE TYPE (PAID vs RECEIVED):
- If the description mentions: withdrawal, ATM, purchase, payment, debit, transfer out, bill payment → "paid"
- If the description mentions: deposit, credit, salary, refund, transfer in, interest → "received"
- Look at the column headers: amounts under "Deposit/Credit/CR" columns → "received"
- Look at the column headers: amounts under "Withdrawal/Debit/DR" columns → "paid"
- Check if balance increased (received) or decreased (paid)

COMMON BANK STATEMENT FORMATS TO HANDLE:
Format 1: Date | Description | Debit | Credit | Balance
Format 2: Date | Particulars | Withdrawal | Deposit | Balance
Format 3: Date | Description | DR | CR | Balance
Format 4: Date | Value Date | Description | Cheque | Deposit | Withdrawal | Balance
Format 5: Date | Description | Amount | DR/CR | Balance

VERIFICATION:
- Sum all "received" amounts should approximately equal total_deposits
- Sum all "paid" amounts should approximately equal total_withdrawals
- If totals don't match, re-check each transaction carefully

BE FLEXIBLE: Adapt to whatever format the statement uses!
PROMPT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'transaction_descriptions' => $schema->array()->items($schema->string())->required(),
            'transaction_types' => $schema->array()->items($schema->string())->required(),
            'transaction_amounts' => $schema->array()->items($schema->number())->required(),
            'total_deposits' => $schema->number()->nullable()->required(),
            'total_withdrawals' => $schema->number()->nullable()->required(),
        ];
    }
}
