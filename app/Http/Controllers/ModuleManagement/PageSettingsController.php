<?php

namespace App\Http\Controllers\ModuleManagement;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Page;
use App\Services\RegistryLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PageSettingsController extends Controller
{
    public function __construct(
        private RegistryLogService $registryLog,
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $editingPage = null;
        $deletingPage = null;

        if ($request->filled('delete_page')) {
            $deletingPage = Page::query()->with('module')->find($request->integer('delete_page'));
            if ($deletingPage === null) {
                return redirect()
                    ->route('module-management.page-settings')
                    ->with('error', __('That page no longer exists.'));
            }
        } elseif ($request->filled('edit')) {
            $editingPage = Page::query()->with('module')->find($request->integer('edit'));
        }

        $moduleGroups = Module::query()
            ->with(['pages' => fn ($q) => $q->orderBy('sequence_number')->orderBy('id')])
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();

        $modules = Module::query()
            ->where(function ($q) use ($editingPage): void {
                $q->where('is_active', true);
                if ($editingPage !== null) {
                    $q->orWhere('id', $editingPage->module_id);
                }
            })
            ->orderBy('sequence')
            ->orderBy('id')
            ->get();
        $nextSequence = ((int) Page::query()->max('id')) + 1;
        $restorablePages = $this->registryLog->archivedPagesNotInDatabase();

        return view('module-management.page-settings', [
            'moduleGroups' => $moduleGroups,
            'modules' => $modules,
            'nextSequence' => $nextSequence,
            'editingPage' => $editingPage,
            'deletingPage' => $deletingPage,
            'restorablePages' => $restorablePages,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'page_name' => ['required', 'string', 'max:255'],
            'page_alias' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('pages', 'page_alias')->where(
                    fn ($q) => $q->where('module_id', (int) $request->input('module_id'))
                ),
            ],
            'sequence_number' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'page_alias.unique' => __('This page alias is already used in this module. Edit the alias or change the name so it generates a different value.'),
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('module-management.page-settings')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $sequence = ($validated['sequence_number'] ?? null) !== null
            ? (int) $validated['sequence_number']
            : ((int) Page::query()->max('id')) + 1;

        Page::create([
            'page_name' => $validated['page_name'],
            'page_alias' => $validated['page_alias'],
            'module_id' => (int) $validated['module_id'],
            'sequence_number' => $sequence,
            'icon' => $validated['icon'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('module-management.page-settings')
            ->with('success', __('Page added.'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'page_name' => ['required', 'string', 'max:255'],
            'page_alias' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('pages', 'page_alias')
                    ->where(fn ($q) => $q->where('module_id', (int) $request->input('module_id')))
                    ->ignore($page->id),
            ],
            'sequence_number' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'icon' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ], [
            'page_alias.unique' => __('This page alias is already used in this module. Choose a different alias.'),
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('module-management.page-settings', ['edit' => $page->id])
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $sequence = ($validated['sequence_number'] ?? null) !== null
            ? (int) $validated['sequence_number']
            : $page->sequence_number;

        $page->update([
            'page_name' => $validated['page_name'],
            'page_alias' => $validated['page_alias'],
            'module_id' => (int) $validated['module_id'],
            'sequence_number' => $sequence,
            'icon' => $validated['icon'] ?? null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('module-management.page-settings')
            ->with('success', __('Page updated.'));
    }

    public function destroy(Request $request, Page $page): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'delete_confirmed' => ['accepted'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('module-management.page-settings', ['delete_page' => $page->id])
                ->withErrors($validator);
        }

        $page->delete();

        return redirect()
            ->route('module-management.page-settings')
            ->with('success', __('Page deleted.'));
    }

    public function restoreFromLog(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'module_alias' => ['nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'page_alias' => ['nullable', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'restore_all' => ['sometimes', 'boolean'],
        ]);

        try {
            if ($request->boolean('restore_all')) {
                $rows = $this->registryLog->archivedPagesNotInDatabase()
                    ->filter(fn (array $row): bool => $row['module_exists']);

                if ($rows->isEmpty()) {
                    $blocked = $this->registryLog->archivedPagesNotInDatabase()
                        ->filter(fn (array $row): bool => ! $row['module_exists']);

                    if ($blocked->isNotEmpty()) {
                        return redirect()
                            ->route('module-management.page-settings')
                            ->with('error', __('Restore the missing module(s) first: :modules', [
                                'modules' => $blocked->pluck('module_alias')->unique()->join(', '),
                            ]));
                    }

                    return redirect()
                        ->route('module-management.page-settings')
                        ->with('info', __('No archived pages in the log need restoring.'));
                }

                foreach ($rows as $row) {
                    $this->registryLog->restorePage($row['module_alias'], $row['page_alias']);
                }

                return redirect()
                    ->route('module-management.page-settings')
                    ->with('success', __('Restored :count page(s) from the log.', ['count' => $rows->count()]));
            }

            $moduleAlias = (string) ($validated['module_alias'] ?? '');
            $pageAlias = (string) ($validated['page_alias'] ?? '');
            if ($moduleAlias === '' || $pageAlias === '') {
                return redirect()
                    ->route('module-management.page-settings')
                    ->with('error', __('Choose a page to restore.'));
            }

            $this->registryLog->restorePage($moduleAlias, $pageAlias);

            return redirect()
                ->route('module-management.page-settings')
                ->with('success', __('Page :page restored from the log.', ['page' => $pageAlias]));
        } catch (\InvalidArgumentException $e) {
            return redirect()
                ->route('module-management.page-settings')
                ->with('error', $e->getMessage());
        }
    }
}
