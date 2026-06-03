<?php

namespace App\Services;

class DummyPaymentService
{
    public function charge(float $amount, array $paymentDetails = []): object
    {
        // Simulate payment processing with 90% success rate
        $success = rand(1, 100) <= 90;

        return (object) [
            'success' => $success,
            'transaction_id' => $success ? 'TXN-' . strtoupper(substr(uniqid(), -8)) : null,
            'message' => $success ? 'Payment processed successfully.' : 'Payment failed. Please try again.',
            'amount' => $amount,
        ];
    }
}
