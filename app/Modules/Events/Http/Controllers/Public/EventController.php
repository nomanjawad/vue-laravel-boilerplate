<?php

namespace App\Modules\Events\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Events/Public/Events/Index', [
            'events' => Event::query()
                ->where('is_active', true)
                ->latest('published_at')
                ->paginate(12),
        ]);
    }

    public function show(Event $event): Response
    {
        abort_unless($event->is_active, 404);

        return Inertia::render('Events/Public/Events/Show', [
            'event' => $event,
        ]);
    }
}
