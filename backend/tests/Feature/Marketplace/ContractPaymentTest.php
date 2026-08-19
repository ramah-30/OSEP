<?php

namespace Tests\Feature\Marketplace;

use App\Enums\AccountType;
use App\Models\Contract;
use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    /** @return array{0: User, 1: User, 2: Contract} */
    private function scenario(): array
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $vendor = User::factory()->accountType(AccountType::Vendor)->create();
        $event = Event::create([
            'planner_id' => $planner->id,
            'title' => "Amina's Wedding",
            'status' => 'planning',
        ]);

        $contract = Contract::create([
            'planner_id' => $planner->id,
            'vendor_id' => $vendor->id,
            'event_id' => $event->id,
            'reference' => 'CTR-2026-000001',
            'title' => 'Catering contract',
            'status' => 'signed',
            'amount' => 800_000,
            'signed_at' => now(),
        ]);

        return [$planner, $vendor, $contract];
    }

    public function test_planner_can_pay_a_signed_contract_in_full(): void
    {
        [$planner, $vendor, $contract] = $this->scenario();
        Sanctum::actingAs($planner);

        $response = $this->postJson("/api/v1/marketplace/contracts/{$contract->id}/pay", [
            'amount' => 800_000,
            'payer_phone' => '0765432109',
            'network' => 'mixx_by_yas',
        ])->assertOk();

        $response->assertJsonPath('data.payment.status', 'completed');
        $response->assertJsonPath('data.payment.direction', 'outgoing');
        $response->assertJsonPath('data.contract.payment_status', 'paid');
        $response->assertJsonPath('data.contract.status', 'signed');
        $response->assertJsonPath('data.contract.balance', 0);

        $this->assertDatabaseHas('receipts', ['contract_id' => $contract->id, 'vendor_id' => $vendor->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $vendor->id, 'type' => 'payment_sent']);
    }

    public function test_partial_payment_leaves_status_untouched(): void
    {
        [$planner, , $contract] = $this->scenario();
        Sanctum::actingAs($planner);

        $response = $this->postJson("/api/v1/marketplace/contracts/{$contract->id}/pay", [
            'amount' => 300_000,
            'payer_phone' => '0765432109',
            'network' => 'halotel',
        ])->assertOk();

        $response->assertJsonPath('data.contract.payment_status', 'partially_paid');
        $response->assertJsonPath('data.contract.status', 'signed');
    }

    public function test_an_unsigned_contract_cannot_be_paid(): void
    {
        [$planner, , $contract] = $this->scenario();
        $contract->update(['status' => 'draft']);
        Sanctum::actingAs($planner);

        $this->postJson("/api/v1/marketplace/contracts/{$contract->id}/pay", [
            'amount' => 100_000,
            'payer_phone' => '0765432109',
            'network' => 'halotel',
        ])->assertStatus(422);
    }

    public function test_the_reserved_fake_number_declines_and_leaves_the_contract_unpaid(): void
    {
        [$planner, $vendor, $contract] = $this->scenario();
        Sanctum::actingAs($planner);

        $response = $this->postJson("/api/v1/marketplace/contracts/{$contract->id}/pay", [
            'amount' => 800_000,
            'payer_phone' => '0000000000',
            'network' => 'airtel',
        ])->assertOk();

        $response->assertJsonPath('data.payment.status', 'failed');
        $response->assertJsonPath('data.contract.payment_status', 'unpaid');
        $this->assertDatabaseMissing('notifications', ['user_id' => $vendor->id, 'type' => 'payment_sent']);
    }
}
