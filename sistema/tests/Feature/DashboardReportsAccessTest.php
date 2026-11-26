<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class DashboardReportsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_dashboard_and_sales_history_only_show_their_own_sales(): void
    {
        $cashier = $this->userWithRole('Cajero');
        $otherSeller = $this->userWithRole('Administrador');
        $customer = Customer::create(['name' => 'Cliente de prueba', 'dni' => '12345678']);
        $ownSale = $this->sale($cashier, $customer, 25);
        $otherSale = $this->sale($otherSeller, $customer, 90);

        $this->actingAs($cashier)
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('Mis ventas de hoy')
            ->assertSee('#'.$ownSale->id)
            ->assertDontSee('#'.$otherSale->id);

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee((string) $ownSale->id)
            ->assertDontSee((string) $otherSale->id);

        $this->get(route('sales.show', $otherSale))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->delete(route('sales.destroy', $ownSale))->assertForbidden();
    }

    public function test_pharmacist_can_access_inventory_reports_but_not_sales_or_seller_reports(): void
    {
        $pharmacist = $this->userWithRole('Químico farmacéutico');

        $this->actingAs($pharmacist)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Estado de medicamentos e inventario');

        $this->get(route('reports.index', ['type' => 'sales']))->assertForbidden();
        $this->get(route('reports.index', ['type' => 'sellers']))->assertForbidden();
        $this->get(route('reports.export', ['type' => 'sales']))->assertForbidden();
        $this->get(route('reports.pdf', ['type' => 'sales']))->assertForbidden();
        $this->get(route('reports.pdf', ['type' => 'medicines']))->assertOk()->assertDownload();
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('medicines.index'))->assertOk();
    }

    public function test_administrator_can_download_filtered_xlsx_and_pdf_reports(): void
    {
        $administrator = $this->userWithRole('Administrador');
        $administrator->update(['name' => 'Andrés Huamán']);
        $customer = Customer::create(['name' => 'Abigail Ybarra Gamez', 'dni' => '87654321']);
        $otherCustomer = Customer::create(['name' => 'Cliente fuera del filtro', 'dni' => '23456789']);
        $matchingSale = $this->sale($administrator, $customer, 40);
        $matchingSale->update(['date' => now()->subDays(3)]);
        $excludedSale = $this->sale($administrator, $otherCustomer, 90);
        $excludedSale->update(['date' => now()->subDays(3)]);
        $filters = [
            'type' => 'sales',
            'period' => 'custom',
            'from' => now()->subDays(5)->toDateString(),
            'to' => now()->subDay()->toDateString(),
            'user_id' => $administrator->id,
            'customer_id' => $customer->id,
        ];

        $this->actingAs($administrator)
            ->get(route('reports.index', $filters))
            ->assertOk()
            ->assertSee('Ingresos acumulados')
            ->assertSee('#'.$matchingSale->id)
            ->assertDontSee('#'.$excludedSale->id);

        $excelResponse = $this->get(route('reports.export', $filters))
            ->assertOk()
            ->assertDownload();
        $excelContents = $excelResponse->streamedContent();
        $this->assertStringStartsWith('PK', $excelContents);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'farmacia-report-');

        try {
            file_put_contents($temporaryFile, $excelContents);
            $workbook = IOFactory::load($temporaryFile);
            $worksheet = $workbook->getActiveSheet();

            $this->assertSame('#'.$matchingSale->id, $worksheet->getCell('A2')->getValue());
            $this->assertSame('Abigail Ybarra Gamez', $worksheet->getCell('C2')->getValue());
            $this->assertSame('Andrés Huamán', $worksheet->getCell('D2')->getValue());
            $this->assertSame(2, $worksheet->getHighestDataRow());
            $this->assertSame('A2', $worksheet->getFreezePane());
            $workbook->disconnectWorksheets();
        } finally {
            unlink($temporaryFile);
        }

        $pdfResponse = $this->get(route('reports.pdf', $filters))
            ->assertOk()
            ->assertDownload();
        $this->assertStringStartsWith('%PDF-', $pdfResponse->getContent());

        $this->get(route('reports.index', ['type' => 'sellers']))->assertOk();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::create([
            'role_id' => $role->id,
            'name' => $roleName.' de prueba',
            'dni' => (string) random_int(10000000, 99999999),
            'email' => strtolower(str_replace(' ', '.', $roleName)).random_int(1, 99999).'@example.test',
            'password' => 'password',
        ]);
    }

    private function sale(User $seller, Customer $customer, float $total): Sale
    {
        return Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'date' => now(),
            'total' => $total,
            'igv' => round($total - $total / (1 + Sale::IGV_RATE), 2),
        ]);
    }
}
