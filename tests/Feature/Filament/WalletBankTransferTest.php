<?php

namespace Tests\Feature\Filament;

use App\Enums\Role;
use App\Filament\Resources\Deposits\Pages\ViewDeposit;
use App\Filament\Resources\Deposits\DepositResource;
use App\Filament\User\Pages\Wallet;
use App\Models\Deposit;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Portal\PortalSettings;
use Database\Seeders\CmsFoundationSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WalletBankTransferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(CmsFoundationSeeder::class);

        $settings = app(PortalSettings::class);
        $settings->set('rrfinco_account', 'TEST_ACCOUNT', 'payment');
        $settings->set('rrfinco_merchant_id', 'TEST_MERCHANT', 'payment');
        $settings->set('rrfinco_api_token', 'TEST_TOKEN', 'payment');
        $settings->set('wallet_online_enabled', true, 'payment');
        $settings->set('wallet_bank_transfer_enabled', true, 'payment');
        $settings->set('bank_account_name', 'Portal Ops', 'payment');
        $settings->set('bank_account_number', '1234567890', 'payment');
        $settings->set('bank_ifsc', 'HDFC0001234', 'payment');
        $settings->set('bank_name', 'HDFC Bank', 'payment');
    }

    public function test_developer_can_submit_bank_transfer_request(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['wallet_balance' => 100]);
        $user->assignRole(Role::Developer->value);

        Livewire::actingAs($user)
            ->test(Wallet::class)
            ->set('paymentMethod', 'bank_transfer')
            ->set('amount', 500)
            ->set('utr', 'UTR123456789')
            ->set('proof', UploadedFile::fake()->image('receipt.jpg'))
            ->call('submitBankTransfer')
            ->assertHasNoErrors();

        $deposit = Deposit::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(Deposit::METHOD_BANK_TRANSFER, $deposit->method);
        $this->assertEquals('pending', $deposit->status);
        $this->assertEquals('UTR123456789', $deposit->utr);
        $this->assertEquals(100.0, (float) $user->fresh()->wallet_balance);
        $this->assertNotNull($deposit->proof_path);
        Storage::disk('public')->assertExists($deposit->proof_path);
    }

    public function test_online_payment_blocked_when_disabled(): void
    {
        app(PortalSettings::class)->set('wallet_online_enabled', false, 'payment');

        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Http::fake();

        Livewire::actingAs($user)
            ->test(Wallet::class)
            ->set('amount', 250)
            ->call('submitPayment');

        Http::assertNothingSent();
        $this->assertDatabaseCount('deposits', 0);
    }

    public function test_admin_can_approve_bank_transfer_and_credit_wallet(): void
    {
        $user = User::factory()->create(['wallet_balance' => 50]);
        $user->assignRole(Role::Developer->value);

        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'order_id' => 'BANK_TEST_001',
            'amount' => 1000,
            'method' => Deposit::METHOD_BANK_TRANSFER,
            'status' => 'pending',
            'utr' => 'UTRAPPROVE001',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewDeposit::class, ['record' => $deposit->getKey()])
            ->callAction('approve', data: ['notes' => 'Verified in bank']);

        $deposit->refresh();
        $this->assertEquals('success', $deposit->status);
        $this->assertEquals($admin->id, $deposit->reviewed_by);
        $this->assertEquals(1050.0, (float) $user->fresh()->wallet_balance);

        $txn = WalletTransaction::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals('credit', $txn->type);
        $this->assertEquals(1000.0, (float) $txn->amount);
    }

    public function test_admin_can_reject_bank_transfer_without_crediting(): void
    {
        $user = User::factory()->create(['wallet_balance' => 50]);
        $user->assignRole(Role::Developer->value);

        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $deposit = Deposit::create([
            'user_id' => $user->id,
            'order_id' => 'BANK_TEST_002',
            'amount' => 750,
            'method' => Deposit::METHOD_BANK_TRANSFER,
            'status' => 'pending',
            'utr' => 'UTRREJECT001',
        ]);

        Livewire::actingAs($admin)
            ->test(ViewDeposit::class, ['record' => $deposit->getKey()])
            ->callAction('reject', data: ['reason' => 'UTR not found']);

        $deposit->refresh();
        $this->assertEquals('rejected', $deposit->status);
        $this->assertEquals('UTR not found', $deposit->admin_notes);
        $this->assertEquals(50.0, (float) $user->fresh()->wallet_balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }

    public function test_admin_can_access_deposits_resource(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::Admin->value);

        $this->actingAs($admin)
            ->get(DepositResource::getUrl('index'))
            ->assertOk();
    }

    public function test_duplicate_utr_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::Developer->value);

        Deposit::create([
            'user_id' => $user->id,
            'order_id' => 'BANK_EXISTING',
            'amount' => 100,
            'method' => Deposit::METHOD_BANK_TRANSFER,
            'status' => 'pending',
            'utr' => 'UTRDUPLICATE1',
        ]);

        Livewire::actingAs($user)
            ->test(Wallet::class)
            ->set('paymentMethod', 'bank_transfer')
            ->set('amount', 200)
            ->set('utr', 'UTRDUPLICATE1')
            ->call('submitBankTransfer');

        $this->assertEquals(1, Deposit::query()->count());
    }
}
