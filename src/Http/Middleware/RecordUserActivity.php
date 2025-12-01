<?php

namespace Edwink\FilamentUserActivity\Http\Middleware;

use Closure;
use Edwink\FilamentUserActivity\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RecordUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $raw = config('filament-user-activity.ignore_urls', []);
        $patterns = collect(Arr::flatten($raw))
            ->filter()
            ->map(function (string $pattern): string {
                // If a full URL was provided, reduce to path; remove leading slash for Request::is()
                $path = parse_url($pattern, PHP_URL_PATH) ?? $pattern;
                return ltrim($path, '/');
            })
            ->values()
            ->all();
        
        if ($patterns !== [] && $request->is($patterns)) {
            return $response; // early return: skip logging for ignored paths
        }

        // Perform action
        if (Auth::id() !== null) {
            UserActivity::create([
                'url' => config('app.url').'/'.$request->path(),
                'user_id' => Auth::id(),
            ]);
        }

        return $response;
    }
}
