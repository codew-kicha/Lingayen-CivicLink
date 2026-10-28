<?php

namespace App\Http\Middleware;

use App\Models\Accreditation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Expires lapsed accreditations once a day, on the first request of the day (shared hosting runs no
 * scheduler). Cache::add is atomic, so only one request does the work.
 */
class ExpireLapsedAccreditations
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::add('accreditations-expired-on-'.now()->toDateString(), true, now()->endOfDay())) {
            Accreditation::expireLapsed();
        }

        return $next($request);
    }
}
