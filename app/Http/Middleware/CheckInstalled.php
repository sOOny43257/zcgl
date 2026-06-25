<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = storage_path('app/installed');

        if (!file_exists($installed) && !str_starts_with($request->path(), 'install')) {
            return redirect('/install');
        }

        return $next($request);
    }
}
