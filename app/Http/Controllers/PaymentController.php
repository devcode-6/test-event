<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function store(Request $request, $bookingId)
    {
        $booking = Booking::where('id', $bookingId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            return $this->error('Booking is not in pending status');
        }

        $result = $this->paymentService->processPayment($booking);
        return $this->success($result, 'Payment processed');
    }

    public function show($id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->booking->user_id !== auth()->id()) {
            return $this->forbidden('You can only view your own payments');
        }

        return $this->success($payment, 'Payment retrieved successfully');
    }
}