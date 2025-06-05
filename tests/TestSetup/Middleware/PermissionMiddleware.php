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
     * @param  string  ...$permissions
     * @return mixed|Response|void
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (! Auth::check()) {
            abort(403, 'Unauthorized');
        }

        /** @var UserModel $user */
        $user = Auth::user();

        // Flatten permissions array to ensure it's a list of strings
        $flatPermissions = [];
        foreach ($permissions as $permission) {
            if (is_array($permission)) {
                $flatPermissions = array_merge($flatPermissions, array_values($permission));
            } else {
                $flatPermissions[] = (string) $permission;
            }
        }

        if ($user->hasPermissionTo($flatPermissions)) {
            return $next($request);
        }
        abort(403, 'Unauthorized');
    }
}
