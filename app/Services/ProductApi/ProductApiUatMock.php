<?php

namespace App\Services\ProductApi;

/**
 * Deterministic sample payloads for UAT — never calls the aggregator.
 */
class ProductApiUatMock
{
    /**
     * @return array<string, mixed>
     */
    public function allProductCategories(): array
    {
        return [
            'status' => true,
            'data' => [
                ['id' => 13, 'title' => 'Bank Accounts'],
                ['id' => 14, 'title' => 'Credit Cards'],
            ],
            'message' => 'UAT sample — product categories. Use production credentials for live data.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productsByCategory(int $categoryId): array
    {
        return [
            'status' => true,
            'data' => [
                [
                    'product_id' => '12345',
                    'title' => 'HDFC Millennia Credit Card',
                    'sub_title' => '5% cashback on Amazon, Flipkart & more',
                    'logo' => 'https://cdn.example.test/products/hdfc-millennia.png',
                ],
                [
                    'product_id' => '12346',
                    'title' => 'SBI SimplyCLICK Credit Card',
                    'sub_title' => '10X rewards on online spends',
                    'logo' => 'https://cdn.example.test/products/sbi-simplyclick.png',
                ],
            ],
            'message' => 'UAT sample — products for category '.$categoryId.'. Use production credentials for live data.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productDetails(string $productId): array
    {
        return [
            'status' => true,
            'data' => [
                'url' => 'https://apply.example.test/campaign/uat-'.$productId,
                'campaign_url' => 'https://apply.example.test/campaign/uat-'.$productId,
            ],
            'message' => 'UAT sample — product details. Use production credentials for live data.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createLeadProfile(array $data): array
    {
        $customerId = isset($data['customer_id']) && $data['customer_id'] !== null && $data['customer_id'] !== ''
            ? (string) $data['customer_id']
            : 'UAT-CUST-'.substr((string) ($data['mobile_no'] ?? '0000'), -4);
        $isUpdate = isset($data['customer_id']) && $data['customer_id'] !== null && $data['customer_id'] !== '';

        return [
            'status' => true,
            'data' => [
                'mobile_no' => (string) ($data['mobile_no'] ?? ''),
                'profile_details' => [
                    'customer_id' => $customerId,
                    'category_id' => (string) ($data['category_id'] ?? ''),
                    'product_id' => null,
                ],
            ],
            'message' => $isUpdate
                ? 'Customer profile has been updated.'
                : 'Customer profile has been created.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createLead(string $productId): array
    {
        return [
            'status' => true,
            'data' => [
                'lead_code' => 'UAT-LEAD-'.$productId,
                'campaign_url' => 'https://apply.example.test/campaign/uat-lead-'.$productId,
            ],
            'message' => 'UAT sample — lead created. Use production credentials for live data.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function leadStatus(string $leadCode): array
    {
        return [
            'status' => true,
            'data' => [
                'lead_code' => $leadCode,
                'lead_status' => 'pending',
            ],
            'message' => 'UAT sample — lead status. Use production credentials for live data.',
        ];
    }
}
