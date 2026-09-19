<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->query('period', 'month'); // week, month, year
        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period);

        $salesLogs = Transaction::where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $expenseLogs = Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalIncome = (float) $salesLogs->sum('amount');
        $totalExpense = (float) $expenseLogs->sum('amount');
        $netProfit = $totalIncome - $totalExpense;

        return view('reports.index', compact(
            'period',
            'periodLabel',
            'startDate',
            'endDate',
            'salesLogs',
            'expenseLogs',
            'totalIncome',
            'totalExpense',
            'netProfit'
        ));
    }

    public function exportPdf(Request $request)
    {
        $period = $request->query('period', 'month');
        [$startDate, $endDate, $periodLabel] = $this->resolveDateRange($period);

        $salesLogs = Transaction::where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $expenseLogs = Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $totalIncome = (float) $salesLogs->sum('amount');
        $totalExpense = (float) $expenseLogs->sum('amount');
        $netProfit = $totalIncome - $totalExpense;
        $generatedAt = now()->format('F d, Y h:i A');

        $pdf = Pdf::loadView('reports.pdf', compact(
            'periodLabel',
            'startDate',
            'endDate',
            'salesLogs',
            'expenseLogs',
            'totalIncome',
            'totalExpense',
            'netProfit',
            'generatedAt'
        ));

        $filename = 'twojs_report_' . now()->format('Ymd') . '.pdf';

        return $pdf->download($filename);
    }

    public function exportSalesCsv(Request $request): StreamedResponse
    {
        $period = $request->query('period', 'month');
        [$startDate, $endDate] = $this->resolveDateRange($period);

        $transactions = Transaction::where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $filename = 'sales_log_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Category', 'Description', 'Amount']);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->date->format('Y-m-d'),
                    'Income',
                    $tx->category,
                    $tx->description,
                    number_format($tx->amount, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function exportExpenseCsv(Request $request): StreamedResponse
    {
        $period = $request->query('period', 'month');
        [$startDate, $endDate] = $this->resolveDateRange($period);

        $transactions = Transaction::where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'asc')
            ->get();

        $filename = 'expense_log_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Category', 'Description', 'Amount']);

            foreach ($transactions as $tx) {
                fputcsv($handle, [
                    $tx->date->format('Y-m-d'),
                    'Expense',
                    $tx->category,
                    $tx->description,
                    number_format($tx->amount, 2, '.', ''),
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function resolveDateRange(string $period): array
    {
        $today = now()->startOfDay();

        return match ($period) {
            'week' => [
                now()->subDays(6)->toDateString(),
                $today->toDateString(),
                'Past 7 Days (Weekly Summary)',
            ],
            'year' => [
                now()->subDays(364)->toDateString(),
                $today->toDateString(),
                'Past 365 Days (Yearly Summary)',
            ],
            default => [
                now()->subDays(29)->toDateString(),
                $today->toDateString(),
                'Past 30 Days (Monthly Summary)',
            ],
        };
    }
}
