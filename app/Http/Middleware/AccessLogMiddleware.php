<?php

namespace App\Http\Middleware;

use App\Models\AccessLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $ua = $request->userAgent() ?? '';

        // 简单解析浏览器和平台
        $browser = 'Unknown';
        if (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Edg')) $browser = 'Edge';
        elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($ua, 'Safari')) $browser = 'Safari';

        $platform = 'Unknown';
        if (str_contains($ua, 'Windows')) $platform = 'Windows';
        elseif (str_contains($ua, 'Mac')) $platform = 'macOS';
        elseif (str_contains($ua, 'Linux')) $platform = 'Linux';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $platform = 'iOS';
        elseif (str_contains($ua, 'Android')) $platform = 'Android';

        // 只记录页面访问（跳过静态资源）
        $path = $request->path();
        if (!preg_match('/\.(css|js|ico|png|jpg|jpeg|gif|svg|woff|woff2|ttf|map)$/i', $path)) {
            try {
                AccessLog::create([
                    'user_id' => auth()->id(),
                    'user_name' => auth()->check() ? auth()->user()->name : null,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_agent' => $ua,
                    'browser' => $browser,
                    'platform' => $platform,
                    'created_at' => now(),
                ]);
            } catch (\Exception $e) {
                // 静默处理
            }
        }

        return $next($request);
    }
}
