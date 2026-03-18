<?php
namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\BookingConfirmed;

class PaymentService
{
    public function processPayment(Booking $booking): array
    {
        $amount = $booking->ticket->price * $booking->quantity;

        // Simulate 80% success rate
        $success = rand(1, 100) <= 80;

        $payment = Payment::create([
            'booking_id' => $booking->id,
            'amount' => $amount,
            'status' => $success ? 'success' : 'failed',
        ]);

        if ($success) {
            $booking->update(['status' => 'confirmed']);
            // Queue notification
            $booking->user->notify(new BookingConfirmed($booking));
        }

        return [
            'status' => $payment->status,
            'amount' => $amount,
        ];
    }
}