<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Booking;

class PreventDoubleBooking
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $ticketId = $request->route('ticket');
        $userId = $request->user()->id;

        $existingBooking = Booking::where('user_id', $userId)
            ->where('ticket_id', $ticketId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($existingBooking) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active booking for this ticket'
            ], 409);
        }

        return $next($request);
    }
}
