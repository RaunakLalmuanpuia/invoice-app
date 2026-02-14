<?php

namespace App\Http\Controllers;

use App\Ai\Agents\BankStatementAnalyzer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Ai\Files\Document;
use Illuminate\Support\Facades\Log;

class BankStatementController extends Controller
{
    public function index()
    {
        // Check if we have 'results' flashed to the session from a previous POST
        // If not, we pass null.
        return Inertia::render('Statement/Index', [
            'transactions' => session('transactions', null),
            'filename' => session('filename', null),
        ]);
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'statement' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        try {
            $file = $request->file('statement');

            // Your existing AI Logic
            $response = (new BankStatementAnalyzer)->prompt(
                'Analyze this bank statement and extract all transaction details. Return arrays for description, type (debit/credit), and amount.',
                attachments: [
                    Document::fromUpload($file),
                ]
            );

            $responseData = $response->toArray();
            $transactions = [];

            $count = count($responseData['transaction_descriptions'] ?? []);
            for ($i = 0; $i < $count; $i++) {
                $transactions[] = [
                    'description' => $responseData['transaction_descriptions'][$i] ?? null,
                    'type' => $responseData['transaction_types'][$i] ?? null,
                    'amount' => $responseData['transaction_amounts'][$i] ?? null,
                ];
            }

            // Calculate totals from transactions as verification
            $calculatedDeposits = array_sum(array_map(
                fn($t) => $t['type'] === 'credit' ? $t['amount'] : 0,
                $transactions
            ));

            $calculatedWithdrawals = array_sum(array_map(
                fn($t) => $t['type'] === 'debit' ? $t['amount'] : 0,
                $transactions
            ));

            Log::info('Analysis successful');
            Log::info('AI Total Deposits: ' . ($responseData['total_deposits'] ?? 'N/A'));
            Log::info('AI Total Withdrawals: ' . ($responseData['total_withdrawals'] ?? 'N/A'));
            Log::info('Calculated Deposits: ' . $calculatedDeposits);
            Log::info('Calculated Withdrawals: ' . $calculatedWithdrawals);

            // KEY CHANGE: Redirect back to index with data in Session
            return to_route('bank-statement.index')->with([
                'transactions' => $transactions,
                'filename' => $file->getClientOriginalName(),
                'total_deposits' => $responseData['total_deposits'] ?? null,
                'total_withdrawals' => $responseData['total_withdrawals'] ?? null,
                'calculated_deposits' => round($calculatedDeposits, 2),
                'calculated_withdrawals' => round($calculatedWithdrawals, 2),
            ]);

        } catch (\Exception $e) {
            Log::error('Bank statement analysis failed: ' . $e->getMessage());

            return back()->withErrors([
                'error' => 'Failed to analyze: ' . $e->getMessage()
            ]);
        }
    }
}
