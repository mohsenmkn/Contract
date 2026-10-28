<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContractorAccessMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // اگر کاربر پیمانکار است، فقط به قراردادهای خودش دسترسی داشته باشد
        if ($user->hasRole('contractor')) {
            if (!$user->party || $user->party->role !== 'contractor') {
                abort(403, 'شما به عنوان پیمانکار ثبت نشده‌اید.');
            }
        }

        return $next($request);
    }
}
