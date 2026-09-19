<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    public function test_pdf_export_returns_pdf_document(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reports/export-pdf?period=month');
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_sales_csv_export_returns_csv(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reports/export-sales-csv?period=month');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }

    public function test_expense_csv_export_returns_csv(): void
    {
        $admin = User::where('role', 'admin')->first();

        $response = $this->actingAs($admin)->get('/reports/export-expense-csv?period=month');
        $response->assertStatus(200);
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
    }
}
