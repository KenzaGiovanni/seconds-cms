<?php

namespace App\Http\Middleware;

use App\Support\Feature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEcommerceEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Feature::ecommerce(), 404);

        return $next($request);
    }
}
