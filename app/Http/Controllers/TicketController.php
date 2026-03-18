<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Ticket;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use ApiResponse;

    public function store(Request $request, $eventId)
    {
        $event = Event::findOrFail($eventId);

        if ($event->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->forbidden('Only event organizer can add tickets');
        }

        $validated = $request->validate([
            'type' => 'required|in:VIP,Standard,Economy',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ]);

        $ticket = Ticket::create([
            'event_id' => $eventId,
            'type' => $validated['type'],
            'price' => $validated['price'],
            'quantity' => $validated['quantity'],
        ]);

        return $this->success($ticket, 'Ticket created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $event = $ticket->event;

        if ($event->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->forbidden('Only event organizer can update tickets');
        }

        $ticket->update($request->only(['type', 'price', 'quantity']));
        return $this->success($ticket, 'Ticket updated successfully');
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $event = $ticket->event;

        if ($event->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            return $this->forbidden('Only event organizer can delete tickets');
        }

        $ticket->delete();
        return $this->success(null, 'Ticket deleted successfully');
    }
}