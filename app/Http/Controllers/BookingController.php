<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function store(Request $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Check availability
        $bookedQuantity = Booking::where('ticket_id', $ticketId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->sum('quantity');

        if ($bookedQuantity + $validated['quantity'] > $ticket->quantity) {
            return $this->error('Not enough tickets available', null, 409);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'ticket_id' => $ticketId,
            'quantity' => $validated['quantity'],
            'status' => 'pending',
        ]);

        return $this->success($booking, 'Booking created successfully', 201);
    }

    public function index(Request $request)
    {
        $query = Booking::where('user_id', $request->user()->id)
            ->with(['ticket.event']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('created_at')->get();
        return $this->success($bookings, 'Bookings retrieved successfully');
    }

    public function cancel($id)
    {
        $booking = Booking::where('id', $id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($booking->status === 'cancelled') {
            return $this->error('Booking is already cancelled');
        }

        $booking->update(['status' => 'cancelled']);

        if ($booking->payment && $booking->payment->status === 'success') {
            $booking->payment->update(['status' => 'refunded']);
        }

        return $this->success($booking, 'Booking cancelled successfully');
    }

    public function adminIndex(Request $request)
    {
        $query = Booking::with(['ticket.event', 'user']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('created_at')->get();
        return $this->success($bookings, 'All bookings retrieved successfully');
    }

    public function adminCancel($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status === 'cancelled') {
            return $this->error('Booking is already cancelled');
        }

        $booking->update(['status' => 'cancelled']);

        if ($booking->payment && $booking->payment->status === 'success') {
            $booking->payment->update(['status' => 'refunded']);
        }

        return $this->success($booking, 'Booking cancelled successfully');
    }
}