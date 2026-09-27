<?php
namespace Shopnear\DynamicModules;

class DemoPaymentController
{
    public static function processPaymentSimulator(array $payload): array
    {
        $cardNumber = $payload['card_number'] ?? '4000000000003100';
        $amount = (float)($payload['amount'] ?? 0.0);
        $simulateFailure = !empty($payload['simulate_failure']);

        if ($simulateFailure || str_ends_with($cardNumber, '0000')) {
            return [
                'status' => 'FAILED',
                'code' => 'CARD_DECLINED',
                'message' => 'Demo Payment Simulation: Card was declined.',
                'transaction_id' => 'TXN_MOCK_FAIL_' . time()
            ];
        }

        return [
            'status' => 'SUCCESS',
            'code' => 'APPROVED',
            'message' => 'Demo Payment Simulation: Transaction approved.',
            'transaction_id' => 'TXN_MOCK_' . strtoupper(bin2hex(random_bytes(6))),
            'amount' => $amount,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}
