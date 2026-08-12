<?php

namespace App\Console\Commands;

use App\Services\Portal\PortalSettings;
use App\Services\Recharge\MokshiqService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class MokshiqDiagnoseCommand extends Command
{
    protected $signature = 'mokshiq:diagnose
                            {--recharge : Run a live ₹10 Jio test recharge (charges wallet at Mokshiq)}
                            {--number=9431023126 : Mobile number for --recharge}
                            {--circle=Bihar Jharkhand : Circle for --recharge}';

    protected $description = 'Verify Mokshiq credentials and optionally run a live mobile recharge test';

    public function handle(PortalSettings $settings, MokshiqService $mokshiqService): int
    {
        $token = $settings->mokshiqToken();
        $pin = $settings->mokshiqPin();
        $origin = trim($settings->mokshiqOrigin());

        $this->table(['Setting', 'Status'], [
            ['Token configured', $token !== '' ? 'yes ('.strlen($token).' chars)' : 'MISSING'],
            ['PIN configured', $pin !== '' ? 'yes ('.strlen($pin).' chars)' : 'MISSING'],
            ['Origin', $origin !== '' ? $origin : 'MISSING'],
        ]);

        if ($token === '' || $pin === '' || $origin === '') {
            $this->error('Configure mokshiq_token, mokshiq_pin, and mokshiq_origin in Admin → Manage Settings.');

            return self::FAILURE;
        }

        $this->info('Checking get_operator…');

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer '.$token,
                'Origin' => $origin,
                'Accept' => 'application/json',
            ])
            ->get(rtrim($settings->mokshiqApiUrl(), '/').'/get_operator');

        if ($response->failed()) {
            $detail = trim((string) ($response->json('detail') ?? $response->json('message') ?? $response->body()));
            $this->error("get_operator failed: HTTP {$response->status()} — {$detail}");

            if (str_contains(strtolower($detail), 'invalid token')) {
                $this->line('');
                $this->warn('The token saved in settings does not match your working Postman token.');
                $this->warn('Open Filament → Manage Settings → Mokshiq and paste the full Bearer token again, then re-run this command.');
            }

            return self::FAILURE;
        }

        $operators = $response->json();
        $count = is_array($operators) ? count($operators) : 0;
        $this->info("get_operator OK ({$count} operators).");

        if (! $this->option('recharge')) {
            $this->line('Add --recharge to run a live mobile test (default: Jio, ₹10).');

            return self::SUCCESS;
        }

        if (! $this->confirm('This will place a real ₹10 Mokshiq recharge. Continue?', false)) {
            return self::SUCCESS;
        }

        $result = $mokshiqService->createMobileRecharge([
            'operator' => 'Jio',
            'number' => (string) $this->option('number'),
            'amount' => 10,
            'circle' => (string) $this->option('circle'),
        ]);

        $this->line(json_encode($result, JSON_PRETTY_PRINT));

        return $result['status'] === 'success' ? self::SUCCESS : self::FAILURE;
    }
}
