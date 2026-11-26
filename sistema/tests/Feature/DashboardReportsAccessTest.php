<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('medicines.index'))->assertOk();
    }

    public function test_administrator_can_view_sales_reports_and_export_csv(): void
    {
        $administrator = $this->userWithRole('Administrador');
        $customer = Customer::create(['name' => 'Cliente de prueba', 'dni' => '87654321']);
        $this->sale($administrator, $customer, 40);

        $this->actingAs($administrator)
            ->get(route('reports.index', ['type' => 'sales']))
            ->assertOk()
            ->assertSee('Ingresos acumulados');

        $this->get(route('reports.export', ['type' => 'sales']))
            ->assertOk()
            ->assertDownload();

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
