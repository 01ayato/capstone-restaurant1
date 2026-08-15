<?php

namespace App\Http\Controllers\Modules\SmokeNGrill;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\TableGroup;
use App\Models\TableOccupancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TablesConfigurationsController extends Controller
{
    public function index(): View
    {
        $groups = TableGroup::query()
            ->with(['diningTables.activeOccupancy'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $initial = [
            'groups' => $groups->map(fn (TableGroup $group) => $this->serializeGroup($group))->values()->all(),
            'routes' => [
                'storeGroup' => route('page.smoke_n_grill.tables_configurations.groups.store'),
                'updateGroupBase' => url('/modules/smoke_n_grill/tables_configurations/groups'),
                'destroyGroupBase' => url('/modules/smoke_n_grill/tables_configurations/groups'),
                'uploadMapBase' => url('/modules/smoke_n_grill/tables_configurations/groups'),
                'storeTableBase' => url('/modules/smoke_n_grill/tables_configurations/groups'),
                'updateTableBase' => url('/modules/smoke_n_grill/tables_configurations/tables'),
                'updateTablePositionBase' => url('/modules/smoke_n_grill/tables_configurations/tables'),
                'destroyTableBase' => url('/modules/smoke_n_grill/tables_configurations/tables'),
                'updateOccupancyBase' => url('/modules/smoke_n_grill/tables_configurations/tables'),
                'monitor' => route('page.smoke_n_grill.tables_configurations.monitor'),
            ],
        ];

        return view('modules.smoke_n_grill.pages.tables_configurations.index', [
            'pageInitial' => $initial,
        ]);
    }

    public function monitor(): JsonResponse
    {
        $groups = TableGroup::query()
            ->with(['diningTables.activeOccupancy'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'groups' => $groups->map(fn (TableGroup $group) => $this->serializeGroup($group))->values()->all(),
        ]);
    }

    public function storeGroup(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $group = TableGroup::query()->create([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if ($request->expectsJson()) {
            $group->load('diningTables.activeOccupancy');

            return response()->json([
                'message' => 'Group created.',
                'group' => $this->serializeGroup($group),
            ]);
        }

        return back()->with('success', 'Group created.');
    }

    public function updateGroup(Request $request, TableGroup $group): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $group->update([
            'name' => $validated['name'],
            'sort_order' => $validated['sort_order'] ?? $group->sort_order,
        ]);

        if ($request->expectsJson()) {
            $group->load('diningTables.activeOccupancy');

            return response()->json([
                'message' => 'Group updated.',
                'group' => $this->serializeGroup($group),
            ]);
        }

        return back()->with('success', 'Group updated.');
    }

    public function destroyGroup(TableGroup $group): RedirectResponse|JsonResponse
    {
        if ($group->map_image) {
            $this->deleteMapImage($group->map_image);
        }

        $group->delete();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Group deleted.']);
        }

        return back()->with('success', 'Group deleted.');
    }

    public function uploadMap(Request $request, TableGroup $group): RedirectResponse|JsonResponse
    {
        $request->validate([
            'map_image' => ['required', 'image', 'max:4096'],
        ]);

        if ($group->map_image) {
            $this->deleteMapImage($group->map_image);
        }

        $path = $this->storeMapImage($request->file('map_image'));
        $group->update(['map_image' => $path]);

        if ($request->expectsJson()) {
            $group->load('diningTables.activeOccupancy');

            return response()->json([
                'message' => 'Floor map uploaded.',
                'group' => $this->serializeGroup($group),
            ]);
        }

        return back()->with('success', 'Floor map uploaded.');
    }

    public function storeTable(Request $request, TableGroup $group): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'chair_count' => ['required', 'integer', 'min:1', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $exists = $group->diningTables()
            ->where('table_number', $validated['table_number'])
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This table number already exists in the group.',
                    'errors' => ['table_number' => ['This table number already exists in the group.']],
                ], 422);
            }

            return back()->withErrors(['table_number' => 'This table number already exists in the group.']);
        }

        $table = $group->diningTables()->create([
            'table_number' => $validated['table_number'],
            'chair_count' => $validated['chair_count'],
            'sit_code' => $this->generateSitCode($group, $validated['table_number']),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        if ($request->expectsJson()) {
            $table->load('activeOccupancy');

            return response()->json([
                'message' => 'Table added.',
                'table' => $this->serializeTable($table),
                'group_id' => $group->id,
            ]);
        }

        return back()->with('success', 'Table added.');
    }

    public function updateTable(Request $request, DiningTable $table): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:20'],
            'chair_count' => ['required', 'integer', 'min:1', 'max:50'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $exists = DiningTable::query()
            ->where('table_group_id', $table->table_group_id)
            ->where('table_number', $validated['table_number'])
            ->where('id', '!=', $table->id)
            ->exists();

        if ($exists) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This table number already exists in the group.',
                    'errors' => ['table_number' => ['This table number already exists in the group.']],
                ], 422);
            }

            return back()->withErrors(['table_number' => 'This table number already exists in the group.']);
        }

        $table->update([
            'table_number' => $validated['table_number'],
            'chair_count' => $validated['chair_count'],
            'sort_order' => $validated['sort_order'] ?? $table->sort_order,
        ]);

        if ($request->expectsJson()) {
            $table->load('activeOccupancy');

            return response()->json([
                'message' => 'Table updated.',
                'table' => $this->serializeTable($table),
            ]);
        }

        return back()->with('success', 'Table updated.');
    }

    public function destroyTable(DiningTable $table): RedirectResponse|JsonResponse
    {
        $groupId = $table->table_group_id;
        $table->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Table deleted.',
                'group_id' => $groupId,
                'table_id' => $table->id,
            ]);
        }

        return back()->with('success', 'Table deleted.');
    }

    public function updateOccupancy(Request $request, DiningTable $table): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'occupied_chairs' => ['required', 'integer', 'min:0', 'max:'.$table->chair_count],
        ]);

        if ($validated['occupied_chairs'] === 0) {
            TableOccupancy::release($table);
        } else {
            TableOccupancy::occupy($table, $validated['occupied_chairs']);
        }

        $table->load('activeOccupancy');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Seat occupancy updated.',
                'table' => $this->serializeTable($table),
            ]);
        }

        return back()->with('success', 'Seat occupancy updated.');
    }

    public function updateTablePosition(Request $request, DiningTable $table): JsonResponse
    {
        $validated = $request->validate([
            'map_x' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'map_y' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $mapX = $validated['map_x'] ?? null;
        $mapY = $validated['map_y'] ?? null;

        if ($mapX === null || $mapY === null) {
            $table->update(['map_x' => null, 'map_y' => null]);
        } else {
            $table->update([
                'map_x' => round((float) $mapX, 2),
                'map_y' => round((float) $mapY, 2),
            ]);
        }

        $table->load('activeOccupancy');

        return response()->json([
            'message' => $table->isPlacedOnMap() ? 'Table position saved.' : 'Table position cleared.',
            'table' => $this->serializeTable($table),
        ]);
    }

    private function generateSitCode(TableGroup $group, string $tableNumber): string
    {
        $base = Str::slug($group->name.'-'.$tableNumber, '-');
        $code = $base;
        $suffix = 1;

        while (DiningTable::query()->where('sit_code', $code)->exists()) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        return $code;
    }

    private function storeMapImage(\Illuminate\Http\UploadedFile $file): string
    {
        $directory = public_path('images/table-maps');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->move($directory, $filename);

        return 'images/table-maps/'.$filename;
    }

    private function deleteMapImage(?string $path): void
    {
        if (! $path) {
            return;
        }

        $fullPath = public_path($path);
        if (is_file($fullPath)) {
            unlink($fullPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeGroup(TableGroup $group): array
    {
        return [
            'id' => $group->id,
            'name' => $group->name,
            'map_image_url' => $group->mapImageUrl(),
            'sort_order' => $group->sort_order,
            'tables' => $group->diningTables->map(fn (DiningTable $table) => $this->serializeTable($table))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeTable(DiningTable $table): array
    {
        return [
            'id' => $table->id,
            'table_group_id' => $table->table_group_id,
            'table_number' => $table->table_number,
            'chair_count' => $table->chair_count,
            'sit_code' => $table->sit_code,
            'sit_url' => $table->sitUrl(),
            'sort_order' => $table->sort_order,
            'occupied_chairs' => $table->occupiedChairs(),
            'available_chairs' => $table->availableChairs(),
            'is_occupied' => $table->isOccupied(),
            'map_x' => $table->map_x !== null ? (float) $table->map_x : null,
            'map_y' => $table->map_y !== null ? (float) $table->map_y : null,
            'is_placed_on_map' => $table->isPlacedOnMap(),
        ];
    }
}
