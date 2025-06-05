<?php

namespace Anil\FastApiCrud\Tests\TestSetup\Middleware;

use Anil\FastApiCrud\Tests\TestSetup\Models\UserModel;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  list<string>  $permissions
     * @return mixed|Response|void
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (! Auth::check()) {
            abort(403, 'Unauthorized');
        }

        /** @var UserModel $user */
        $user = Auth::user();
        if ($user->hasPermissionTo($permissions)) {
            return $next($request);
        }
        abort(403, 'Unauthorized');
    }
}
