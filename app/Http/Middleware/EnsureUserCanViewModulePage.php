<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Module;
use App\Models\Page;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserCanViewModulePage
{
    /**
     * Named routes `page.{module_alias}.{page_alias}.*` require explicit module_role and page_role
     * assignments (allow-list). Unchecked roles have no access. Superadmin bypasses all checks.
     *
     * @param  \Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        if ($user === null || $user->isSuperadmin()) {
            return $next($request);
        }

        $name = $request->route()?->getName();
        if ($name === null || ! str_starts_with($name, 'page.')) {
            return $next($request);
        }

        $parts = explode('.', $name);
        if (count($parts) < 3) {
            return $next($request);
        }

        $moduleAlias = $parts[1];
        $pageAlias = $parts[2];

        // Front of House pages must be reachable by staff (admin/cashier). The sidebar already
        // hides Management/User Settings from cashiers, so do not 403 staff over missing
        // module_role/page_role allow-list rows for Smoke n' Grill FoH pages.
        $frontOfHousePages = ['pos', 'orders', 'reservations', 'customers', 'view_website'];
        if ($moduleAlias === 'smoke_n_grill'
            && in_array($pageAlias, $frontOfHousePages, true)
            && $user->isStaff()) {
            return $next($request);
        }

        $module = Module::query()
            ->where('alias', $moduleAlias)
            ->where('is_active', true)
            ->with('roles')
            ->first();

        if ($module === null) {
            return $next($request);
        }

        if ($module->roles->isEmpty()) {
            abort(403);
        }

        if ($user->role_id === null) {
            abort(403);
        }

        $allowedModule = $module->roles->contains(
            fn ($role) => (int) $role->id === (int) $user->role_id
        );
        if (! $allowedModule) {
            abort(403);
        }

        $page = Page::query()
            ->where('module_id', $module->id)
            ->where('page_alias', $pageAlias)
            ->where('is_active', true)
            ->with('roles')
            ->first();

        if ($page === null) {
            return $next($request);
        }

        if ($page->roles->isEmpty()) {
            abort(403);
        }

        $allowedPage = $page->roles->contains(
            fn ($role) => (int) $role->id === (int) $user->role_id
        );
        if (! $allowedPage) {
            abort(403);
        }

        return $next($request);
    }
}
