@extends('layouts.app')

@section('title', 'Tables Configurations')

@section('page-header')
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-orange-600">Dining room</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-900">Tables Configurations</h1>
            <p class="mt-1 text-sm text-gray-500">Manage table groups, floor maps, QR codes, and seat occupancy</p>
        </div>
    </div>
@endsection

@section('content')
    <script type="application/json" id="tables-page-initial">@json($pageInitial)</script>

    <div x-data="tablesConfigurationsPage()" class="space-y-6">
        <input type="file" x-ref="mapFileInput" accept="image/*" class="hidden" x-on:change="uploadMap($event)">
        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 rounded-2xl border border-gray-200 bg-white p-2 shadow-sm">
            <button
                type="button"
                class="rounded-[10px] px-4 py-2.5 text-sm font-semibold transition"
                x-bind:class="activeTab === 'list' ? 'bg-[#0D2232] text-white shadow-sm' : 'text-gray-600 hover:bg-slate-100'"
                x-on:click="activeTab = 'list'"
            >
                <i class="fa-solid fa-table-cells mr-1.5 text-xs" aria-hidden="true"></i>
                Table List
            </button>
            <button
                type="button"
                class="rounded-[10px] px-4 py-2.5 text-sm font-semibold transition"
                x-bind:class="activeTab === 'monitor' ? 'bg-[#0D2232] text-white shadow-sm' : 'text-gray-600 hover:bg-slate-100'"
                x-on:click="activeTab = 'monitor'; refreshMonitor()"
            >
                <i class="fa-solid fa-chair mr-1.5 text-xs" aria-hidden="true"></i>
                Monitor Occupied Seats
            </button>
        </div>

        {{-- Tab: Table List --}}
        <div x-show="activeTab === 'list'" class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-gray-500">
                    <span x-text="groups.length"></span> group(s) · <span x-text="totalTables()"></span> table(s)
                </p>
                <button
                    type="button"
                    class="admin-btn-primary"
                    x-on:click="openGroupCreate()"
                >
                    <i class="fa-solid fa-plus text-xs" aria-hidden="true"></i>
                    Add Group
                </button>
            </div>

            <template x-if="groups.length === 0">
                <div class="rounded-xl border border-dashed border-gray-200 bg-slate-50 px-6 py-16 text-center">
                    <p class="text-sm font-medium text-gray-700">No table groups yet</p>
                    <p class="mt-1 text-xs text-gray-500">Create a group (e.g. Group 1) and add tables with chair counts.</p>
                </div>
            </template>

            <template x-for="group in groups" :key="group.id">
                <section class="admin-table-shell">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 bg-slate-50 px-5 py-4">
                        <div>
                            <h2 class="text-base font-bold text-gray-900" x-text="group.name"></h2>
                            <p class="mt-0.5 text-xs text-gray-500">
                                <span x-text="group.tables.length"></span> table(s)
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50" x-on:click="openGroupEdit(group)">Edit</button>
                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50" x-on:click="triggerMapUpload(group.id)">
                                <i class="fa-solid fa-image mr-1" aria-hidden="true"></i> Upload Map
                            </button>
                            <button type="button" class="rounded-lg bg-[#0D2232] px-3 py-1.5 text-xs font-semibold text-white hover:bg-[#16364D]" x-on:click="openTableCreate(group.id)">
                                <i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> Add Table
                            </button>
                            <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100" x-on:click="deleteGroup(group)">Delete</button>
                        </div>
                    </div>

                    <div class="grid gap-6 p-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
                        <div>
                            <template x-if="group.tables.length === 0">
                                <p class="py-8 text-center text-sm text-gray-500">No tables in this group yet.</p>
                            </template>

                            <div class="space-y-2">
                                <template x-for="table in group.tables" :key="table.id">
                                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-gray-200 bg-slate-50/50 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">
                                                Table <span x-text="table.table_number"></span>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <span x-text="table.chair_count"></span> chairs ·
                                                <span class="font-mono" x-text="table.sit_code"></span>
                                            </p>
                                            <p class="mt-0.5 truncate text-xs text-[#16364D]" x-text="table.sit_url"></p>
                                            <p class="mt-0.5 text-xs" x-bind:class="table.is_placed_on_map ? 'text-emerald-600' : 'text-gray-400'">
                                                <i class="fa-solid fa-location-dot mr-1" aria-hidden="true"></i>
                                                <span x-text="table.is_placed_on_map ? 'Placed on map' : 'Not placed on map'"></span>
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap gap-2">
                                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50" x-on:click="openMapEditor(group); selectPlacementTable(table.id)" title="Place on map">
                                                <i class="fa-solid fa-map-pin mr-1" aria-hidden="true"></i> Place
                                            </button>
                                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-[#16364D] hover:bg-[#204865]/10" x-on:click="showQr(table)">
                                                <i class="fa-solid fa-qrcode mr-1" aria-hidden="true"></i> QR Code
                                            </button>
                                            <button type="button" class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50" x-on:click="openTableEdit(table)">Edit</button>
                                            <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100" x-on:click="deleteTable(table, group.id)">Delete</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-xs font-bold uppercase tracking-wider text-gray-500">Floor map</p>
                                <span class="text-[10px] text-gray-400" x-show="group.map_image_url">Click to assign tables</span>
                            </div>
                            <template x-if="group.map_image_url">
                                <button
                                    type="button"
                                    class="group relative block w-full overflow-hidden rounded-lg border border-gray-200 transition hover:border-[#204865] hover:shadow-md focus:outline-none focus:ring-2 focus:ring-[#204865]"
                                    x-on:click="openMapEditor(group)"
                                    title="Open floor map editor"
                                >
                                    <img x-bind:src="group.map_image_url" alt="Floor map" class="w-full object-cover">
                                    <div class="pointer-events-none absolute inset-0">
                                        <template x-for="table in group.tables.filter(t => t.is_placed_on_map)" :key="'thumb-marker-' + table.id">
                                            <span
                                                class="absolute flex size-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-[9px] font-bold text-white shadow"
                                                x-bind:class="table.is_occupied ? 'bg-amber-500' : 'bg-[#0D2232]'"
                                                x-bind:style="mapMarkerStyle(table)"
                                                x-text="table.table_number"
                                            ></span>
                                        </template>
                                    </div>
                                    <span class="absolute inset-x-0 bottom-0 bg-[#0D2232]/70 px-2 py-1 text-center text-[10px] font-semibold text-white opacity-0 transition group-hover:opacity-100">
                                        Open map editor
                                    </span>
                                </button>
                                <p class="mt-1.5 text-center text-[10px] text-gray-500">
                                    <span x-text="placedTablesCount(group)"></span> / <span x-text="group.tables.length"></span> placed
                                </p>
                            </template>
                            <template x-if="!group.map_image_url">
                                <div class="flex aspect-square items-center justify-center rounded-lg border border-dashed border-gray-200 bg-slate-50 text-center text-xs text-gray-500">
                                    No map uploaded
                                </div>
                            </template>
                        </div>
                    </div>
                </section>
            </template>
        </div>

        {{-- Tab: Monitor --}}
        <div x-show="activeTab === 'monitor'" x-cloak class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-4 text-sm">
                    <div class="rounded-lg border border-gray-200 bg-white px-4 py-2">
                        <span class="text-gray-500">Tables occupied:</span>
                        <span class="ml-1 font-bold text-gray-900" x-text="`${occupiedTablesCount()} / ${totalTables()}`"></span>
                    </div>
                    <div class="rounded-lg border border-gray-200 bg-white px-4 py-2">
                        <span class="text-gray-500">Seats in use:</span>
                        <span class="ml-1 font-bold text-gray-900" x-text="`${occupiedChairsCount()} / ${totalChairs()}`"></span>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-full bg-emerald-600"></span> Available</span>
                        <span class="inline-flex items-center gap-1.5"><span class="size-3 rounded-full bg-amber-500"></span> Occupied</span>
                    </div>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-slate-50 disabled:opacity-50"
                    x-on:click="refreshMonitor()"
                    x-bind:disabled="refreshing"
                >
                    <i class="fa-solid fa-rotate" x-bind:class="refreshing ? 'animate-spin' : ''" aria-hidden="true"></i>
                    Refresh
                </button>
            </div>

            <template x-if="groups.length === 0">
                <div class="rounded-xl border border-dashed border-gray-200 bg-slate-50 px-6 py-16 text-center text-sm text-gray-500">
                    No tables configured yet. Add groups and tables in the Table List tab.
                </div>
            </template>

            <template x-for="group in groups" :key="'monitor-' + group.id">
                <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-slate-50 px-5 py-3">
                        <div>
                            <h2 class="text-sm font-bold text-gray-900" x-text="group.name"></h2>
                            <p class="text-xs text-gray-500">
                                <span x-text="group.tables.filter(t => t.is_occupied).length"></span> occupied ·
                                <span x-text="placedTablesCount(group)"></span> on map
                            </p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-slate-50"
                            x-show="group.map_image_url"
                            x-on:click="openMapEditor(group)"
                        >
                            <i class="fa-solid fa-map-pin mr-1" aria-hidden="true"></i> Edit map
                        </button>
                    </div>

                    <template x-if="group.map_image_url && group.tables.length > 0">
                        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_18rem]">
                            <div class="border-b border-gray-200 bg-slate-100 p-4 lg:border-b-0 lg:border-r">
                                <div class="relative mx-auto max-w-4xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
                                    <img
                                        x-bind:src="group.map_image_url"
                                        alt="Floor map"
                                        class="block w-full select-none"
                                        draggable="false"
                                    >
                                    <template x-for="table in group.tables.filter(t => t.is_placed_on_map)" :key="'monitor-map-' + table.id">
                                        <button
                                            type="button"
                                            class="absolute flex size-10 -translate-x-1/2 -translate-y-1/2 flex-col items-center justify-center rounded-full text-xs font-bold text-white shadow-lg transition hover:scale-110"
                                            x-bind:class="mapMarkerClass(table)"
                                            x-bind:style="mapMarkerStyle(table)"
                                            x-on:click="selectMonitorTable(table.id)"
                                            x-bind:title="'Table ' + table.table_number + ' — ' + table.occupied_chairs + '/' + table.chair_count + ' seats'"
                                        >
                                            <span x-text="table.table_number"></span>
                                        </button>
                                    </template>
                                </div>
                                <p class="mt-2 text-center text-xs text-gray-500">Click a table on the map to update occupancy</p>
                            </div>

                            <aside class="flex flex-col bg-white p-4">
                                <template x-if="monitorSelectedTable() && monitorSelectedGroup()?.id === group.id">
                                    <div class="flex flex-1 flex-col">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Selected table</p>
                                        <div class="mt-3 rounded-xl border p-4" x-bind:class="monitorSelectedTable().is_occupied ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50/60'">
                                            <div class="flex items-start justify-between gap-2">
                                                <div>
                                                    <p class="text-lg font-bold text-gray-900">
                                                        Table <span x-text="monitorSelectedTable().table_number"></span>
                                                    </p>
                                                    <p class="text-sm text-gray-600">
                                                        <span x-text="monitorSelectedTable().occupied_chairs"></span> / <span x-text="monitorSelectedTable().chair_count"></span> seats in use
                                                    </p>
                                                </div>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase"
                                                    x-bind:class="monitorSelectedTable().is_occupied ? 'bg-amber-200 text-amber-900' : 'bg-emerald-200 text-emerald-900'"
                                                    x-text="monitorSelectedTable().is_occupied ? 'Occupied' : 'Available'"
                                                ></span>
                                            </div>

                                            <div class="mt-4">
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500">Status</label>
                                                <div class="grid grid-cols-2 gap-2">
                                                    <button
                                                        type="button"
                                                        class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                                        x-bind:class="!monitorSelectedTable().is_occupied ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-emerald-400'"
                                                        x-on:click="setTableStatus(monitorSelectedTable(), 'available')"
                                                    >
                                                        <i class="fa-solid fa-circle-check mr-1" aria-hidden="true"></i> Available
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="rounded-lg border px-3 py-2 text-sm font-semibold transition"
                                                        x-bind:class="monitorSelectedTable().is_occupied ? 'border-amber-500 bg-amber-500 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-amber-400'"
                                                        x-on:click="setTableStatus(monitorSelectedTable(), 'occupied')"
                                                    >
                                                        <i class="fa-solid fa-user-group mr-1" aria-hidden="true"></i> Occupied
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="mt-4">
                                                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500">Set occupied seats</label>
                                                <div class="flex gap-2">
                                                    <select
                                                        class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white px-2 py-2 text-sm"
                                                        x-bind:id="'monitor-occ-' + monitorSelectedTable().id"
                                                    >
                                                        <template x-for="n in chairOptions(monitorSelectedTable().chair_count)" :key="'mon-' + n">
                                                            <option x-bind:value="n" x-bind:selected="n === monitorSelectedTable().occupied_chairs" x-text="n === 0 ? '0 (clear)' : n + ' seat' + (n > 1 ? 's' : '')"></option>
                                                        </template>
                                                    </select>
                                                    <button
                                                        type="button"
                                                        class="shrink-0 rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white hover:bg-[#16364D]"
                                                        x-on:click="updateOccupancy(monitorSelectedTable(), document.getElementById('monitor-occ-' + monitorSelectedTable().id).value)"
                                                    >
                                                        Update
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!monitorSelectedTable() || monitorSelectedGroup()?.id !== group.id">
                                    <div class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-slate-50 px-4 py-10 text-center">
                                        <i class="fa-solid fa-hand-pointer text-2xl text-gray-300" aria-hidden="true"></i>
                                        <p class="mt-3 text-sm font-medium text-gray-700">Select a table on the map</p>
                                        <p class="mt-1 text-xs text-gray-500">Markers show live seat status</p>
                                    </div>
                                </template>

                                <template x-if="unplacedTables(group).length > 0">
                                    <div class="mt-4 border-t border-gray-100 pt-4">
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Not on map</p>
                                        <ul class="mt-2 space-y-2">
                                            <template x-for="table in unplacedTables(group)" :key="'unplaced-' + table.id">
                                                <li class="rounded-lg border border-gray-200 bg-slate-50 p-3">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="text-sm font-semibold text-gray-900">Table <span x-text="table.table_number"></span></p>
                                                        <span class="text-[10px] font-bold uppercase" x-bind:class="table.is_occupied ? 'text-amber-600' : 'text-emerald-600'" x-text="table.is_occupied ? 'Occupied' : 'Available'"></span>
                                                    </div>
                                                    <div class="mt-2 flex gap-2">
                                                        <select class="min-w-0 flex-1 rounded border border-gray-200 bg-white px-2 py-1 text-xs" x-bind:id="'occ-' + table.id">
                                                            <template x-for="n in chairOptions(table.chair_count)" :key="n">
                                                                <option x-bind:value="n" x-bind:selected="n === table.occupied_chairs" x-text="n === 0 ? '0 (clear)' : n + ' seat' + (n > 1 ? 's' : '')"></option>
                                                            </template>
                                                        </select>
                                                        <button type="button" class="rounded bg-[#0D2232] px-2 py-1 text-xs font-semibold text-white" x-on:click="updateOccupancy(table, document.getElementById('occ-' + table.id).value)">Update</button>
                                                    </div>
                                                </li>
                                            </template>
                                        </ul>
                                    </div>
                                </template>
                            </aside>
                        </div>
                    </template>

                    <template x-if="!group.map_image_url || group.tables.length === 0">
                        <div class="p-5">
                            <template x-if="!group.map_image_url && group.tables.length > 0">
                                <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                    No floor map uploaded. Upload one in <button type="button" class="font-semibold underline" x-on:click="activeTab = 'list'">Table List</button> to use map monitoring.
                                </div>
                            </template>
                            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                <template x-for="table in group.tables" :key="'monitor-fallback-' + table.id">
                                    <article class="rounded-xl border p-4" x-bind:class="table.is_occupied ? 'border-amber-300 bg-amber-50' : 'border-emerald-200 bg-emerald-50/50'">
                                        <div class="flex items-start justify-between gap-2">
                                            <div>
                                                <p class="text-sm font-bold text-gray-900">Table <span x-text="table.table_number"></span></p>
                                                <p class="text-xs text-gray-500"><span x-text="table.occupied_chairs"></span> / <span x-text="table.chair_count"></span> seats</p>
                                            </div>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" x-bind:class="table.is_occupied ? 'bg-amber-200 text-amber-900' : 'bg-emerald-200 text-emerald-900'" x-text="table.is_occupied ? 'Occupied' : 'Available'"></span>
                                        </div>
                                        <div class="mt-3 flex gap-2">
                                            <select class="min-w-0 flex-1 rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs" x-bind:id="'occ-fb-' + table.id">
                                                <template x-for="n in chairOptions(table.chair_count)" :key="n">
                                                    <option x-bind:value="n" x-bind:selected="n === table.occupied_chairs" x-text="n === 0 ? '0 (clear)' : n + ' seat' + (n > 1 ? 's' : '')"></option>
                                                </template>
                                            </select>
                                            <button type="button" class="shrink-0 rounded-lg bg-[#0D2232] px-3 py-1.5 text-xs font-semibold text-white" x-on:click="updateOccupancy(table, document.getElementById('occ-fb-' + table.id).value)">Update</button>
                                        </div>
                                    </article>
                                </template>
                            </div>
                        </div>
                    </template>
                </section>
            </template>
        </div>

        {{-- Group modal --}}
        <div x-show="groupModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Group">
            <div class="absolute inset-0 bg-[#0D2232]/40" x-on:click="closeGroupModal()"></div>
            <div class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                <h3 class="text-base font-bold text-gray-900" x-text="groupModalMode === 'create' ? 'Add Group' : 'Edit Group'"></h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Group name</label>
                        <input type="text" x-model="groupDraft.name" placeholder="e.g. Group 1" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Sort order</label>
                        <input type="number" x-model="groupDraft.sort_order" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700" x-on:click="closeGroupModal()">Cancel</button>
                    <button type="button" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-on:click="saveGroup()" x-bind:disabled="loading">Save</button>
                </div>
            </div>
        </div>

        {{-- Table modal --}}
        <div x-show="tableModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Table">
            <div class="absolute inset-0 bg-[#0D2232]/40" x-on:click="closeTableModal()"></div>
            <div class="relative w-full max-w-md rounded-xl border border-gray-200 bg-white p-6 shadow-xl">
                <h3 class="text-base font-bold text-gray-900" x-text="tableModalMode === 'create' ? 'Add Table' : 'Edit Table'"></h3>
                <div class="mt-4 space-y-3">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Table number</label>
                        <input type="text" x-model="tableDraft.table_number" placeholder="e.g. 1" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Chair count</label>
                        <input type="number" x-model="tableDraft.chair_count" min="1" max="50" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-700">Sort order</label>
                        <input type="number" x-model="tableDraft.sort_order" min="0" class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700" x-on:click="closeTableModal()">Cancel</button>
                    <button type="button" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" x-on:click="saveTable()" x-bind:disabled="loading">Save</button>
                </div>
            </div>
        </div>

        {{-- Map editor modal --}}
        <div x-show="mapEditorOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Map editor">
            <div class="absolute inset-0 bg-[#0D2232]/50" x-on:click="closeMapEditor()"></div>
            <div class="relative flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
                <div class="flex shrink-0 items-center justify-between border-b border-gray-200 bg-slate-50 px-5 py-4">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">
                            Assign table positions — <span x-text="mapEditorGroup?.name"></span>
                        </h3>
                        <p class="mt-0.5 text-xs text-gray-500">Select a table, then click on the map to place it</p>
                    </div>
                    <button type="button" class="flex size-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:bg-slate-100" x-on:click="closeMapEditor()">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="grid min-h-0 flex-1 lg:grid-cols-[14rem_minmax(0,1fr)]">
                    <aside class="overflow-y-auto border-b border-gray-200 bg-white p-4 lg:border-b-0 lg:border-r">
                        <p class="text-[10px] font-bold uppercase tracking-wider text-gray-500">Tables</p>
                        <ul class="mt-3 space-y-2">
                            <template x-for="table in (mapEditorGroup?.tables ?? [])" :key="'map-list-' + table.id">
                                <li>
                                    <button
                                        type="button"
                                        class="w-full rounded-lg border px-3 py-2.5 text-left text-sm transition"
                                        x-bind:class="isPlacementSelected(table.id) ? 'border-[#204865] bg-[#204865]/10' : 'border-gray-200 bg-slate-50 hover:border-gray-200'"
                                        x-on:click="selectPlacementTable(table.id)"
                                    >
                                        <p class="font-semibold text-gray-900">Table <span x-text="table.table_number"></span></p>
                                        <p class="text-xs text-gray-500"><span x-text="table.chair_count"></span> chairs</p>
                                        <p class="mt-1 text-[10px]" x-bind:class="table.is_placed_on_map ? 'text-emerald-600' : 'text-gray-400'" x-text="table.is_placed_on_map ? 'Placed' : 'Not placed'"></p>
                                    </button>
                                </li>
                            </template>
                        </ul>

                        <template x-if="selectedPlacementTableId">
                            <div class="mt-4 border-t border-gray-100 pt-4">
                                <button
                                    type="button"
                                    class="w-full rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50"
                                    x-on:click="clearTablePosition(selectedPlacementTable())"
                                    x-bind:disabled="savingPosition || !selectedPlacementTable()?.is_placed_on_map"
                                >
                                    Clear position
                                </button>
                            </div>
                        </template>
                    </aside>

                    <div class="min-h-0 overflow-auto bg-slate-100 p-4">
                        <div
                            class="relative mx-auto max-w-3xl cursor-crosshair overflow-hidden rounded-xl border-2 border-dashed border-[#204865]/40 bg-white shadow-inner"
                            x-on:click="handleMapClick($event)"
                            x-bind:class="savingPosition ? 'pointer-events-none opacity-70' : ''"
                        >
                            <img
                                x-bind:src="mapEditorGroup?.map_image_url"
                                alt="Floor map"
                                class="block w-full select-none"
                                draggable="false"
                            >
                            <template x-for="table in (mapEditorGroup?.tables ?? []).filter(t => t.is_placed_on_map)" :key="'map-marker-' + table.id">
                                <button
                                    type="button"
                                    class="absolute flex size-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-xs font-bold text-white shadow-lg transition hover:scale-110"
                                    x-bind:class="mapMarkerClass(table)"
                                    x-bind:style="mapMarkerStyle(table)"
                                    x-on:click.stop="selectPlacementTable(table.id)"
                                    x-text="table.table_number"
                                    x-bind:title="'Table ' + table.table_number"
                                ></button>
                            </template>
                        </div>
                        <p class="mt-3 text-center text-xs text-gray-500">
                            <span x-show="selectedPlacementTable()">
                                Placing <strong x-text="'Table ' + (selectedPlacementTable()?.table_number ?? '')"></strong> — click anywhere on the map
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- QR modal --}}
        <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Table QR code">
            <div class="absolute inset-0 bg-[#0D2232]/40" x-on:click="closeQrModal()"></div>
            <div class="relative w-full max-w-sm rounded-xl border border-gray-200 bg-white p-6 text-center shadow-xl">
                <h3 class="text-base font-bold text-gray-900">Table QR Code</h3>
                <p class="mt-1 text-xs text-gray-500" x-show="qrTable">
                    Scan to open <span class="font-mono text-[#16364D]" x-text="qrTable?.sit_url"></span>
                </p>
                <img x-show="qrDataUrl" x-bind:src="qrDataUrl" alt="QR Code" class="mx-auto mt-4 rounded-lg border border-gray-200">
                <p class="mt-3 text-sm font-semibold text-gray-900" x-show="qrTable">
                    Table <span x-text="qrTable?.table_number"></span> · <span x-text="qrTable?.chair_count"></span> chairs
                </p>
                <div class="mt-5 flex justify-center gap-2">
                    <button type="button" class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700" x-on:click="closeQrModal()">Close</button>
                    <button type="button" class="rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white" x-on:click="downloadQr()">
                        <i class="fa-solid fa-download mr-1" aria-hidden="true"></i> Download
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
