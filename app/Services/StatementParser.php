<?php

namespace App\Services;

use Spatie\PdfToText\Pdf;

class StatementParser
{
    public function parse($filePath)
    {
        try {
            // 1. Extract text with Layout
            $text = (new Pdf())
                ->setPdf($filePath)
                ->setOptions(['layout'])
                ->text();

            return $this->processText($text);

        } catch (\Exception $e) {
            return [
                'transactions' => [],
                'total_deposits' => 0,
                'total_withdrawals' => 0,
                'error' => "PDF Error: " . $e->getMessage()
            ];
        }
    }

    protected function processText($text)
    {
        $lines = explode("\n", $text);
        $rawTransactions = [];

        $datePattern = '/^(\d{1,2}[-\/\.]\d{1,2}[-\/\.]\d{2,4}|\d{1,2}\s+[A-Za-z]{3}\s+\d{2,4})/';
        $amountsPattern = '/([\d,]+\.\d{2})\s+([\d,]+\.\d{2})\s*$|([\d,]+\.\d{2})\s*$/';

        $currentTxn = null;
        $lastValidDate = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            $hasAmounts = preg_match($amountsPattern, $line, $amountMatches);
            $hasDate = preg_match($datePattern, $line, $dateMatches);

            if ($hasAmounts) {
                $txnDate = $hasDate ? $dateMatches[1] : $lastValidDate;

                // Check for Opening Balance specifically
                $isBalanceLine = stripos($line, 'Balance Forward') !== false || stripos($line, 'Opening Balance') !== false;

                if ($this->isIgnorable($line) || $isBalanceLine) {
                    if ($isBalanceLine) {
                        // Finalize previous
                        if ($currentTxn) $rawTransactions[] = $this->finalizeRaw($currentTxn);
                        $currentTxn = null;

                        // Create a temporary raw txn for the opening balance
                        $openingTxn = [
                            'is_opening' => true,
                            'date' => $txnDate,
                            'raw_line' => $line,
                            'amount_matches' => $amountMatches,
                            'desc_parts' => ['Opening Balance']
                        ];

                        // CRITICAL FIX: Finalize it immediately so it has 'amount' and 'balance' keys
                        $rawTransactions[] = $this->finalizeRaw($openingTxn);
                    }
                    continue;
                }

                if ($txnDate) {
                    $lastValidDate = $txnDate;

                    if ($currentTxn) {
                        $rawTransactions[] = $this->finalizeRaw($currentTxn);
                    }

                    $currentTxn = [
                        'date' => $txnDate,
                        'raw_line' => $line,
                        'desc_parts' => [],
                        'amount_matches' => $amountMatches,
                    ];

                    $desc = $line;
                    $desc = str_replace($amountMatches[0], '', $desc);
                    if ($hasDate) $desc = str_replace($dateMatches[0], '', $desc);

                    $currentTxn['desc_parts'][] = trim($desc);

                    continue;
                }
            }

            if ($currentTxn && !$hasAmounts && !$this->isIgnorable($line)) {
                $currentTxn['desc_parts'][] = trim($line);
            }
        }

        if ($currentTxn) {
            $rawTransactions[] = $this->finalizeRaw($currentTxn);
        }

        return $this->applyMathLogic($rawTransactions);
    }

    private function finalizeRaw($txn)
    {
        $matches = $txn['amount_matches'];
        $val1 = !empty($matches[1]) ? (float)str_replace(',', '', $matches[1]) : null;
        $val2 = !empty($matches[2]) ? (float)str_replace(',', '', $matches[2]) : null;
        $val3 = !empty($matches[3]) ? (float)str_replace(',', '', $matches[3]) : null;

        $amount = 0;
        $balance = 0;

        if ($val1 !== null && $val2 !== null) {
            $amount = $val1;
            $balance = $val2;
        } elseif ($val3 !== null) {
            $amount = $val3;
        }

        return [
            'is_opening' => $txn['is_opening'] ?? false,
            'date' => $txn['date'],
            'description' => implode(" ", $txn['desc_parts'] ?? []),
            'amount' => $amount,
            'balance' => $balance,
        ];
    }

    private function applyMathLogic($rawTxns)
    {
        $cleanTxns = [];
        $previousBalance = null;

        foreach ($rawTxns as $txn) {

            // Handle Opening Balance
            if ($txn['is_opening']) {
                $balance = max($txn['amount'], $txn['balance']);
                $previousBalance = $balance;
                continue;
            }

            $amount = $txn['amount'];
            $currentBalance = $txn['balance'];
            $type = 'paid';

            if ($previousBalance !== null && $currentBalance != 0) {
                $diff = round($currentBalance - $previousBalance, 2);
                $absDiff = abs($diff);
                $absAmount = round($txn['amount'], 2);

                if (abs($absDiff - $absAmount) < 0.5) {
                    $type = ($diff > 0) ? 'received' : 'paid';
                } else {
                    $type = $this->guessTypeByKeywords($txn['description']);
                }
                $previousBalance = $currentBalance;
            } else {
                $type = $this->guessTypeByKeywords($txn['description']);
                if ($currentBalance != 0) $previousBalance = $currentBalance;
            }

            $cleanTxns[] = [
                'date' => $txn['date'],
                'description' => $this->cleanDesc($txn['description']),
                'amount' => $amount,
                'type' => $type,
            ];
        }

        return $this->calculateTotals($cleanTxns);
    }

    private function guessTypeByKeywords($desc)
    {
        $text = strtolower($desc);
        if (str_contains($text, ' cr ') || str_contains($text, 'credit')) return 'received';
        if (str_contains($text, ' dr ') || str_contains($text, 'debit')) return 'paid';

        $deposits = ['deposit', 'salary', 'refund', 'interest', 'imps in', 'upi in', 'neft in', 'cradj', 'reversal'];
        foreach ($deposits as $k) {
            if (str_contains($text, $k)) return 'received';
        }
        return 'paid';
    }

    private function isIgnorable($line)
    {
        $keywords = ['Page', 'Account Statement', 'TOTAL', 'Generated on', 'Statement of', 'Date Value', 'Deposit Withdrawal'];
        foreach ($keywords as $k) {
            if (stripos($line, $k) !== false) return true;
        }
        return false;
    }

    private function cleanDesc($text)
    {
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    private function calculateTotals($transactions)
    {
        $deposits = 0;
        $withdrawals = 0;
        $transactions = array_filter($transactions, fn($t) => $t['amount'] > 0);

        foreach ($transactions as $t) {
            if ($t['type'] === 'received') $deposits += $t['amount'];
            else $withdrawals += $t['amount'];
        }

        return [
            'transactions' => array_values($transactions),
            'total_deposits' => $deposits,
            'total_withdrawals' => $withdrawals,
        ];
    }
}
