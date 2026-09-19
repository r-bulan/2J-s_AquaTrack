<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Two J’s Water Filling Station - BIR Summary Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 4px 0 0 0;
            font-size: 12px;
            color: #2563eb;
            font-weight: normal;
        }
        .meta {
            margin-bottom: 15px;
            font-size: 10px;
            color: #64748b;
        }
        .meta table {
            width: 100%;
        }
        .summary-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 4px 8px;
            font-size: 11px;
        }
        .summary-table .label {
            color: #64748b;
            width: 30%;
        }
        .summary-table .value {
            font-weight: bold;
            text-align: right;
        }
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-top: 15px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            padding: 6px 8px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        table.data-table td {
            padding: 5px 8px;
            border: 1px solid #e2e8f0;
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 9px;
            color: #94a3b8;
            text-align: center;
        }
        .income-text {
            color: #059669;
        }
        .expense-text {
            color: #e11d48;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Two J’s Water Filling Station</h1>
        <h2>BIR-Style Summary Report — {{ $periodLabel }}</h2>
    </div>

    <div class="meta">
        <table>
            <tr>
                <td><strong>Period:</strong> {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}</td>
                <td style="text-align: right;"><strong>Generated:</strong> {{ $generatedAt }}</td>
            </tr>
        </table>
    </div>

    <!-- Summary Box -->
    <div class="summary-box">
        <table class="summary-table">
            <tr>
                <td class="label">Gross Sales / Revenue:</td>
                <td class="value income-text">PHP {{ number_format($totalIncome, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total Operating Expenses:</td>
                <td class="value expense-text">PHP {{ number_format($totalExpense, 2) }}</td>
            </tr>
            <tr style="border-top: 1px solid #cbd5e1;">
                <td class="label" style="font-weight: bold; color: #0f172a;">Net Operating Income:</td>
                <td class="value" style="font-size: 13px; color: {{ $netProfit >= 0 ? '#059669' : '#e11d48' }};">
                    PHP {{ number_format($netProfit, 2) }}
                </td>
            </tr>
        </table>
    </div>

    <!-- Sales Log -->
    <div class="section-title">I. Sales Log (Income Receipts)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 25%;">Category</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 20%;" class="text-right">Amount (PHP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($salesLogs as $tx)
                <tr>
                    <td>{{ $tx->date->format('Y-m-d') }}</td>
                    <td>{{ $tx->category }}</td>
                    <td>{{ $tx->description }}</td>
                    <td class="text-right income-text">{{ number_format($tx->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8;">No sales records found for this period.</td>
                </tr>
            @endforelse
            @if ($salesLogs->isNotEmpty())
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="3" class="text-right">Total Sales:</td>
                    <td class="text-right income-text">PHP {{ number_format($totalIncome, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <!-- Expense Log -->
    <div class="section-title">II. Expense Log (Operating Deductions)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 15%;">Date</th>
                <th style="width: 25%;">Category</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 20%;" class="text-right">Amount (PHP)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($expenseLogs as $tx)
                <tr>
                    <td>{{ $tx->date->format('Y-m-d') }}</td>
                    <td>{{ $tx->category }}</td>
                    <td>{{ $tx->description }}</td>
                    <td class="text-right expense-text">{{ number_format($tx->amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; color: #94a3b8;">No expense records found for this period.</td>
                </tr>
            @endforelse
            @if ($expenseLogs->isNotEmpty())
                <tr style="font-weight: bold; background-color: #f8fafc;">
                    <td colspan="3" class="text-right">Total Expenses:</td>
                    <td class="text-right expense-text">PHP {{ number_format($totalExpense, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="footer">
        This document is an automated financial summary generated by Two J’s AquaTrack Management System.
    </div>
</body>
</html>
