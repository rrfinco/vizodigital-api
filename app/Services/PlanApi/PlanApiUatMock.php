<?php

namespace App\Services\PlanApi;

use App\Services\EkycHub\EkycHubCatalog;

/**
 * Deterministic sample payloads for UAT — never calls the aggregator.
 */
class PlanApiUatMock
{
    /**
     * @param  array{mobile: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function operatorFetch(array $data): array
    {
        $mobile = $data['mobile'];
        $masked = substr($mobile, 0, 7).'xxx';

        return [
            'status' => 'Success',
            'number' => $masked,
            'company' => 'Jio',
            'circle' => 'Bihar',
            'circle_code' => '52',
            'message' => 'UAT sample — operator fetched. Use production credentials for live aggregator data.',
        ];
    }

    /**
     * @param  array{mobile: string, opcode: string, circle: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function operatorPlanFetch(array $data): array
    {
        $opcode = strtoupper($data['opcode']);
        $operator = EkycHubCatalog::MOBILE_OPCODES[$opcode] ?? $opcode;

        return [
            'status' => 'Success',
            'Operator' => $operator.' PREPAID',
            'message' => 'UAT sample — prepaid plans. Use production credentials for live aggregator data.',
            'data' => [
                'TOPUP' => [
                    [
                        'rs' => 10,
                        'validity' => 'NA',
                        'desc' => 'Sample talktime top-up (UAT)',
                        'Type' => 'talktime',
                    ],
                    [
                        'rs' => 20,
                        'validity' => 'NA',
                        'desc' => 'Sample talktime top-up (UAT)',
                        'Type' => 'talktime',
                    ],
                ],
                'DATA' => [
                    [
                        'rs' => 19,
                        'validity' => '1 Day',
                        'desc' => 'Sample 1GB data pack (UAT)',
                        'Type' => 'data',
                    ],
                    [
                        'rs' => 299,
                        'validity' => '28 Days',
                        'desc' => 'Sample 2GB/day data pack (UAT)',
                        'Type' => 'data',
                    ],
                ],
                'STV' => [
                    [
                        'rs' => 49,
                        'validity' => '28 Days',
                        'desc' => 'Sample unlimited calls STV (UAT)',
                        'Type' => 'stv',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function dthPlanFetch(array $data): array
    {
        $opcode = strtoupper($data['opcode']);
        $operator = EkycHubCatalog::DTH_OPCODES[$opcode] ?? $opcode;

        return [
            'status' => 'Success',
            'Operator' => $operator,
            'message' => 'UAT sample — DTH plans. Use production credentials for live aggregator data.',
            'data' => [
                'Combo' => [
                    [
                        'rs' => 299,
                        'validity' => '30 Days',
                        'desc' => 'Sample combo pack (UAT)',
                        'Type' => 'combo',
                    ],
                    [
                        'rs' => 499,
                        'validity' => '30 Days',
                        'desc' => 'Sample HD combo pack (UAT)',
                        'Type' => 'combo',
                    ],
                ],
                'AddOn' => [
                    [
                        'rs' => 50,
                        'validity' => '30 Days',
                        'desc' => 'Sample sports add-on (UAT)',
                        'Type' => 'addon',
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array{dth_number: string, opcode: string, orderid: string}  $data
     * @return array<string, mixed>
     */
    public function dthInfo(array $data): array
    {
        return [
            'status' => 'Success',
            'message' => 'UAT sample — DTH customer info. Use production credentials for live aggregator data.',
            'data' => [
                [
                    'VC' => $data['dth_number'],
                    'Name' => 'UAT Sample Customer',
                    'Balance' => '125.00',
                    'Minimum_recharge' => '200',
                ],
            ],
        ];
    }
}
