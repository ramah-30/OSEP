<?php

namespace Tests\Feature\Client;

use App\Enums\AccountType;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoicePaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RoleSeeder::class, PermissionSeeder::class]);
    }

    /** @return array{0: User, 1: User, 2: Invoice} */
    private function scenario(): array
    {
        $planner = User::factory()->accountType(AccountType::EventPlanner)->create();
        $client = User::factory()->accountType(AccountType::Client)->create();
        $event = Event::create([
            'planner_id' => $planner->id,
            'client_id' => $client->id,
            'title' => "Amina's Wedding",
            'status' => 'planning',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-000001',
            'planner_id' => $planner->id,
            'client_id' => $client->id,
            'event_id' => $event->id,
            'title' => 'Deposit',
            'issue_date' => now()->toDateString(),
            'total' => 1_000_000,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return [$planner, $client, $invoice];
    }

    public function test_client_can_pay_an_invoice_in_two_parts(): void
    {
        [$planner, $client, $invoice] = $this->scenario();
        Sanctum::actingAs($client);

        $half = $this->postJson("/api/v1/invoices/{$invoice->id}/pay", [
            'amount' => 500_000,
            'payer_phone' => '0712345678',
            'network' => 'vodacom',
        ])->assertOk();

        $half->assertJsonPath('data.payment.status', 'completed');
        $half->assertJsonPath('data.invoice.status', 'partially_paid');
        $half->assertJsonPath('data.invoice.balance', 500000);

        $this->assertDatabaseHas('receipts', ['invoice_id' => $invoice->id, 'amount' => 500000]);
        $this->assertDatabaseHas('notifications', ['user_id' => $planner->id, 'type' => 'payment_received']);

        $rest = $this->postJson("/api/v1/invoices/{$invoice->id}/pay", [
            'amount' => 500_000,
            'payer_phone' => '0712345678',
            'network' => 'vodacom',
        ])->assertOk();

        $rest->assertJsonPath('data.invoice.status', 'paid');
        $rest->assertJsonPath('data.invoice.balance', 0);
    }

    public function test_the_reserved_fake_number_always_declines(): void
    {
        [, $client, $invoice] = $this->scenario();
        Sanctum::actingAs($client);

        $response = $this->postJson("/api/v1/invoices/{$invoice->id}/pay", [
            'amount' => 500_000,
            'payer_phone' => '0000000000',
            'network' => 'airtel',
        ])->assertOk();

        $response->assertJsonPath('data.payment.status', 'failed');
        $response->assertJsonPath('data.invoice.status', 'sent');
        $response->assertJsonPath('data.invoice.balance', 1000000);
        $this->assertDatabaseMissing('receipts', ['invoice_id' => $invoice->id]);
    }

    public function test_a_client_cannot_pay_another_clients_invoice(): void
    {
        [, , $invoice] = $this->scenario();
        $stranger = User::factory()->accountType(AccountType::Client)->create();
        Sanctum::actingAs($stranger);

        $this->postJson("/api/v1/invoices/{$invoice->id}/pay", [
            'amount' => 500_000,
            'payer_phone' => '0712345678',
            'network' => 'airtel',
        ])->assertNotFound();
    }

    public function test_amount_cannot_exceed_the_balance(): void
    {
        [, $client, $invoice] = $this->scenario();
        Sanctum::actingAs($client);

        $this->postJson("/api/v1/invoices/{$invoice->id}/pay", [
            'amount' => 2_000_000,
            'payer_phone' => '0712345678',
            'network' => 'airtel',
        ])->assertStatus(422);
    }
}
