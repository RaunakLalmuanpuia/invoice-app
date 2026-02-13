<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #000;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .invoice-box {
            max-width: 800px;
            margin: auto;
            font-size: 11px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .border-all td, .border-all th {
            border: 1px solid #000;
            padding: 8px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-left {
            text-align: left;
        }
        .bold {
            font-weight: bold;
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
        }
        .small-text {
            font-size: 9px;
        }
        .items-table th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
            padding: 8px 4px;
        }
        .items-table td {
            padding: 8px 4px;
        }
        .total-row {
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="invoice-box">
    <!-- Header -->
    <div class="header-title">Tax Invoice</div>

    <!-- Company & Client Information -->
    <table class="border-all">
        <tr>
            <td colspan="3" class="bold">
                {{ $invoice->seller_company_name }}<br>
                <span class="small-text">
                    @if($invoice->seller_gst_number)
                        GSTIN/UIN: {{ $invoice->seller_gst_number }}<br>
                    @endif
                    @if($invoice->seller_state)
                        State Name: {{ $invoice->seller_state }}@if($invoice->seller_state_code), Code: {{ $invoice->seller_state_code }}@endif
                    @endif
                </span>
            </td>
            <td colspan="3">
                <strong>Invoice No.</strong><br>
                {{ $invoice->invoice_number }}
            </td>
        </tr>
        <tr>
            <td colspan="3" rowspan="2">
                <strong>Consignee (Ship to)</strong><br>
                <span class="bold">{{ $invoice->client_name }}</span><br>
                <span class="small-text">
                    @if($invoice->client_gst_number)
                        GSTIN/UIN: {{ $invoice->client_gst_number }}<br>
                    @endif
                    {{ $invoice->client_address }}<br>
                    @if($invoice->client_state)
                        State Name: {{ $invoice->client_state }}@if($invoice->client_state_code), Code: {{ $invoice->client_state_code }}@endif
                    @endif
                </span>
            </td>
            <td colspan="3">
                <strong>Dated</strong><br>
                {{ $invoice->invoice_date->format('d-M-y') }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <strong>Delivery Note</strong><br>
                -
            </td>
        </tr>
        <tr>
            <td colspan="3" rowspan="2">
                <strong>Buyer (Bill to)</strong><br>
                <span class="bold">{{ $invoice->client_name }}</span><br>
                <span class="small-text">
                    @if($invoice->client_gst_number)
                        GSTIN/UIN: {{ $invoice->client_gst_number }}<br>
                    @endif
                    {{ $invoice->client_address }}<br>
                    @if($invoice->client_state)
                        State Name: {{ $invoice->client_state }}@if($invoice->client_state_code), Code: {{ $invoice->client_state_code }}@endif
                    @endif
                </span>
            </td>
            <td colspan="3">
                <strong>Mode/Terms of Payment</strong><br>
                {{ $invoice->payment_terms }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <strong>Delivery Note Date</strong><br>
                {{ $invoice->invoice_date->format('d-M-y') }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <strong>Buyer's Order No.</strong><br>
                -
            </td>
            <td colspan="3">
                <strong>Dated</strong><br>
                {{ $invoice->invoice_date->format('d-M-y') }}
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <strong>Dispatch Doc No.</strong><br>
                -
            </td>
            <td colspan="3">
                <strong>Destination</strong><br>
                -
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <strong>Dispatched through</strong><br>
                -
            </td>
            <td colspan="3">
                <strong>Terms of Delivery</strong><br>
                -
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="border-all items-table" style="margin-top: 10px;">
        <thead>
        <tr>
            <th style="width: 5%;">Sl<br>No.</th>
            <th style="width: 35%;">Description of Goods</th>
            <th style="width: 10%;">HSN/SAC</th>
            <th style="width: 10%;">Quantity</th>
            <th style="width: 10%;">Rate</th>
            <th style="width: 10%;">per</th>
            <th style="width: 10%;">Disc. %</th>
            <th style="width: 10%;">Amount</th>
        </tr>
        </thead>
        <tbody>
        @php
            $slNo = 1;
            $totalQty = 0;
            $firstUnit = 'Nos';
        @endphp
        @foreach($invoice->line_items as $item)
            @php
                $itemAmount = $item['amount'] ?? ($item['quantity'] * $item['rate']);
                $totalQty += $item['quantity'];
                if ($slNo === 1) {
                    $firstUnit = $item['unit'] ?? 'Nos';
                }
            @endphp
            <tr>
                <td class="text-center">{{ $slNo++ }}</td>
                <td>{{ $item['description'] }}</td>
                <td class="text-center">{{ $item['hsn_code'] ?? '-' }}</td>
                <td class="text-right">{{ $item['quantity'] }} {{ $item['unit'] ?? 'Nos' }}</td>
                <td class="text-right">{{ number_format($item['rate'], 2) }}</td>
                <td class="text-center">{{ $item['unit'] ?? 'Nos' }}</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ number_format($itemAmount, 2) }}</td>
            </tr>
        @endforeach

        <!-- Tax Rows -->
        <tr>
            <td colspan="7" class="text-right bold">CGST</td>
            <td class="text-right bold">{{ number_format($invoice->gst_amount / 2, 2) }}</td>
        </tr>
        <tr>
            <td colspan="7" class="text-right bold">SGST</td>
            <td class="text-right bold">{{ number_format($invoice->gst_amount / 2, 2) }}</td>
        </tr>

        <!-- Total Row -->
        <tr class="total-row">
            <td colspan="3" class="text-left">
                <strong>Total</strong>
            </td>
            <td class="text-right">
                {{ $totalQty }} {{ $firstUnit }}
            </td>
            <td colspan="3" class="text-right"></td>
            <td class="text-right">₹ {{ number_format($invoice->total_amount, 2) }}</td>
        </tr>
        </tbody>
    </table>

    <!-- Amount in Words -->
    <table class="border-all" style="margin-top: 10px;">
        <tr>
            <td colspan="6">
                <strong>Amount Chargeable (in words)</strong> E. & O.E<br>
                @php
                    $formatter = new NumberFormatter('en_IN', NumberFormatter::SPELLOUT);
                    $amountInWords = ucwords($formatter->format($invoice->total_amount));
                @endphp
                <span class="bold">INR {{ $amountInWords }} Only</span>
            </td>
        </tr>
    </table>

    <!-- Tax Breakdown Table -->
    <table class="border-all" style="margin-top: 10px;">
        <thead>
        <tr>
            <th>HSN/SAC</th>
            <th>Total Taxable<br>Value</th>
            <th colspan="2">CGST</th>
            <th colspan="2">SGST/UTGST</th>
            <th>Tax Amount</th>
        </tr>
        <tr>
            <th></th>
            <th></th>
            <th>Rate</th>
            <th>Amount</th>
            <th>Rate</th>
            <th>Amount</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="text-center">-</td>
            <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
            <td class="text-center">9%</td>
            <td class="text-right">{{ number_format($invoice->gst_amount / 2, 2) }}</td>
            <td class="text-center">9%</td>
            <td class="text-right">{{ number_format($invoice->gst_amount / 2, 2) }}</td>
            <td class="text-right">{{ number_format($invoice->gst_amount, 2) }}</td>
        </tr>
        <tr class="total-row">
            <td class="text-center bold">Total</td>
            <td class="text-right bold">{{ number_format($invoice->subtotal, 2) }}</td>
            <td></td>
            <td class="text-right bold">{{ number_format($invoice->gst_amount / 2, 2) }}</td>
            <td></td>
            <td class="text-right bold">{{ number_format($invoice->gst_amount / 2, 2) }}</td>
            <td class="text-right bold">{{ number_format($invoice->gst_amount, 2) }}</td>
        </tr>
        </tbody>
    </table>

    <div style="margin-top: 10px;">
        @php
            $taxInWords = ucwords($formatter->format($invoice->gst_amount));
        @endphp
        <strong>Tax Amount (in words):</strong> INR {{ $taxInWords }} Only
    </div>

    <!-- Declaration and Signature -->
    <table style="margin-top: 30px; border: none;">
        <tr>
            <td style="width: 50%; vertical-align: top; border: none;">
                <div style="font-size: 10px;">
                    <strong>Declaration</strong><br>
                    We declare that this invoice shows the actual price of the<br>
                    goods described and that all particulars are true and<br>
                    correct.
                </div>
            </td>
            <td style="width: 50%; vertical-align: bottom; text-align: right; border: none;">
                <div style="margin-top: 50px;">
                    <strong>for {{ $invoice->seller_company_name }}</strong><br><br><br>
                    <strong>Authorised Signatory</strong>
                </div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-top: 20px; font-size: 9px; font-style: italic;">
        This is a Computer Generated Invoice
    </div>
</div>
</body>
</html>
