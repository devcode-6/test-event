<?php
namespace App\Http\Controllers;

use App\Models\Event;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EventController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Event::with(['creator', 'tickets']);

        if ($request->has('search')) {
            $query->where('title', 'LIKE', '%' . $request->search . '%');
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('location')) {
            $query->where('location', 'LIKE', '%' . $request->location . '%');
        }

        $cacheKey = 'events_' . md5(serialize($request->all()));
        $events = Cache::remember($cacheKey, 600, function () use ($query) {
            return $query->paginate(10);
        });

        return $this->success($events, 'Events retrieved successfully');
    }

    public function show($id)
    {
        $event = Event::with('tickets')->findOrFail($id);
        return $this->success($event, 'Event retrieved successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'date' => 'required|date_format:Y-m-d H:i:s',
            'location' => 'required|string|max:255',
        ]);

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'location' => $validated['location'],
            'created_by' => $request->user()->id,
        ]);

        Cache::flush();
        return $this->success($event, 'Event created successfully', 201);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        if ($event->created_by !== $request->user()->id && !$request->user()->isAdmin()) {
            return $this->forbidden('You can only update your own events');
        }

        $event->update($request->only(['title', 'description', 'date', 'location']));

        Cache::flush();
        return $this->success($event, 'Event updated successfully');
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);

        if ($event->created_by !== auth()->id() && !auth()->user()->isAdmin()) {
            return $this->forbidden('You can only delete your own events');
        }

        $event->delete();
        Cache::flush();
        return $this->success(null, 'Event deleted successfully');
    }
}