<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RedirectController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Redirects/Index', [
            'redirects' => Redirect::orderByDesc('hits')
                ->when($request->search, fn ($q, $s) => $q->where(
                    fn ($q) => $q->where('from_path', 'like', "%{$s}%")->orWhere('to_path', 'like', "%{$s}%")
                ))
                ->paginate(20)
                ->withQueryString(),
            'topNotFound' => NotFoundLog::orderByDesc('hit_count')->limit(10)
                ->get(['id', 'path', 'referrer', 'hit_count', 'last_seen_at']),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRedirect($request);

        Redirect::create($validated);

        // If this redirect resolves a logged 404, clear it from the report.
        NotFoundLog::where('path', $validated['from_path'])->delete();

        return back()->with('success', 'Redirect created.');
    }

    public function update(Request $request, Redirect $redirect)
    {
        $redirect->update($this->validateRedirect($request, $redirect));

        return back()->with('success', 'Redirect updated.');
    }

    public function destroy(Redirect $redirect)
    {
        $redirect->delete();

        return back()->with('success', 'Redirect deleted.');
    }

    private function validateRedirect(Request $request, ?Redirect $redirect = null): array
    {
        $validated = $request->validate([
            'from_path' => ['required', 'string', 'max:191', 'unique:redirects,from_path'.($redirect ? ','.$redirect->id : '')],
            'to_path' => ['required', 'string', 'max:191'],
            'status_code' => ['required', 'in:301,302'],
            'is_active' => ['boolean'],
        ]);

        $validated['from_path'] = Redirect::normalizePath($validated['from_path']);
        $validated['to_path'] = str_starts_with($validated['to_path'], 'http')
            ? $validated['to_path']
            : Redirect::normalizePath($validated['to_path']);

        abort_if($validated['from_path'] === $validated['to_path'], 422, 'A redirect cannot point to itself.');

        abort_if(
            $this->wouldCreateLoop($validated['from_path'], $validated['to_path'], $redirect?->id),
            422,
            'This redirect would create a loop.',
        );

        return $validated;
    }

    /**
     * Walk the active redirect map (including the proposed edge) and reject
     * two-hop cycles like /a→/b + /b→/a (F11 #26).
     */
    private function wouldCreateLoop(string $from, string $to, ?int $excludeId = null): bool
    {
        if (str_starts_with($to, 'http://') || str_starts_with($to, 'https://')) {
            return false;
        }

        $map = Redirect::query()
            ->where('is_active', true)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->pluck('to_path', 'from_path')
            ->all();

        $map[$from] = $to;

        $seen = [];
        $current = $to;
        while (isset($map[$current])) {
            if (isset($seen[$current]) || $current === $from) {
                return true;
            }
            $seen[$current] = true;
            $next = $map[$current];
            if (str_starts_with($next, 'http://') || str_starts_with($next, 'https://')) {
                return false;
            }
            $current = $next;
            if (count($seen) > 50) {
                return true;
            }
        }

        return false;
    }
}
