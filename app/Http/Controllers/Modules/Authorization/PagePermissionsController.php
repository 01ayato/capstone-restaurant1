<?php

declare(strict_types=1);

namespace App\Http\Controllers\Modules\Authorization;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PagePermissionsController extends Controller
{
    public function index(): View
    {
        $this->authorizeStaff();

        $pages = Page::query()
            ->select('pages.*')
            ->join('modules', 'modules.id', '=', 'pages.module_id')
            ->where('pages.is_active', true)
            ->where('modules.is_active', true)
            ->with([
                'module',
                'roles' => fn ($q) => $q->orderBy('id'),
            ])
            ->orderBy('modules.sequence')
            ->orderBy('modules.id')
            ->orderBy('pages.sequence_number')
            ->orderBy('pages.id')
            ->get();

        $roles = Role::query()->orderBy('id')->get();

        return view('modules.authorization.pages.page_permissions.index', [
            'pages' => $pages,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorizeStaff();

        $pageIds = Page::query()
            ->where('is_active', true)
            ->whereHas('module', fn ($q) => $q->where('is_active', true))
            ->pluck('id')
            ->all();

        $permissions = $request->input('permissions', []);
        if (! is_array($permissions)) {
            $permissions = [];
        }

        $rules = [];
        foreach ($pageIds as $pid) {
            $rules['permissions.'.$pid] = ['nullable', 'array'];
            $rules['permissions.'.$pid.'.*'] = ['integer', 'distinct', 'exists:roles,id'];
        }

        $validator = Validator::make(['permissions' => $permissions], $rules);
        if ($validator->fails()) {
            return redirect()
                ->route('page.authorization.page_permissions.index')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();
        $clean = $validated['permissions'] ?? [];

        DB::transaction(function () use ($pageIds, $clean): void {
            foreach ($pageIds as $pageId) {
                $raw = $clean[(string) $pageId] ?? $clean[$pageId] ?? [];
                if (! is_array($raw)) {
                    $raw = [];
                }
                $ids = array_values(array_unique(array_map('intval', $raw)));
                $page = Page::query()->find($pageId);
                if ($page !== null) {
                    $page->roles()->sync($ids);
                }
            }
        });

        return redirect()
            ->route('page.authorization.page_permissions.index')
            ->with('success', __('Page visibility saved.'));
    }

    private function authorizeStaff(): void
    {
        $actor = Auth::user();
        if ($actor === null || (! $actor->isSuperadmin() && ! $actor->isAdmin())) {
            abort(403);
        }
    }
}
