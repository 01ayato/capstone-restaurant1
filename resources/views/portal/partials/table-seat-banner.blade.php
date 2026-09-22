@php
    $tableSession = app(\App\Services\TableSessionService::class);
    $tableSelectInitial = [
        'current' => $currentTable ?? $tableSession->currentTablePayload(),
        'groups' => $tableSession->groupsForSelection(),
        'routes' => [
            'lookup' => route('sit.lookup'),
            'select' => route('sit.select'),
            'clear' => route('sit.clear'),
        ],
    ];
@endphp

<script type="application/json" id="portal-table-select-initial">@json($tableSelectInitial)</script>

<div
    x-data="portalTableSelect(JSON.parse(document.getElementById('portal-table-select-initial')?.textContent || '{}'))"
    x-on:open-table-select.window="openModal('manual')"
    id="table-seat-banner"
    class="mb-8"
>
    <div class="rounded-2xl border border-gray-200 bg-white p-4 backdrop-blur-sm sm:p-5">
        <template x-if="current">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/20 text-emerald-600">
                        <i class="fa-solid fa-chair" aria-hidden="true"></i>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-emerald-600">Your table</p>
                        <p class="mt-0.5 text-lg font-semibold text-gray-900" x-text="current.location_label"></p>
                        <p class="text-sm text-gray-500">Orders will be linked to this table</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-slate-50" x-on:click="openModal('manual')">
                        Change
                    </button>
                    <button type="button" class="rounded-xl border border-red-500/30 px-4 py-2 text-sm font-semibold text-red-700 transition hover:bg-red-500/10" x-on:click="clearTable()">
                        Clear
                    </button>
                </div>
            </div>
        </template>

        <template x-if="!current">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-3">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                        <i class="fa-solid fa-qrcode" aria-hidden="true"></i>
                    </span>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-widest text-orange-600">Where are you sitting?</p>
                        <p class="mt-0.5 text-base font-medium text-gray-900">Scan your table QR or enter your table number</p>
                        <p class="text-sm text-gray-500">This helps us bring your order to the right table</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-orange-400" x-on:click="openModal('scan')">
                        <i class="fa-solid fa-camera" aria-hidden="true"></i>
                        Scan QR
                    </button>
                    <button type="button" class="rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-900 transition hover:bg-slate-100" x-on:click="openModal('manual')">
                        Pick your table
                    </button>
                </div>
            </div>
        </template>
    </div>

    {{-- Modal (teleported to body so it stacks above the sticky navbar) --}}
    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[200] flex items-center justify-center p-4 pt-20 sm:pt-4">
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" x-on:click="closeModal()"></div>
            <div class="relative z-[201] flex max-h-[min(92vh,calc(100dvh-5rem))] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl md:max-w-4xl lg:max-w-5xl">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                <h2 class="font-display text-lg font-bold text-gray-900">Select your table</h2>
                <button type="button" class="flex size-9 items-center justify-center rounded-lg text-gray-500 hover:bg-slate-50 hover:text-gray-900" x-on:click="closeModal()">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>

            <div class="flex gap-1 border-b border-gray-200 p-2">
                <button type="button" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" x-bind:class="mode === 'scan' ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-slate-50'" x-on:click="setMode('scan')">
                    <i class="fa-solid fa-qrcode mr-1" aria-hidden="true"></i> Scan QR
                </button>
                <button type="button" class="flex-1 rounded-lg px-3 py-2 text-sm font-semibold transition" x-bind:class="mode === 'manual' ? 'bg-orange-500 text-white' : 'text-gray-500 hover:bg-slate-50'" x-on:click="setMode('manual')">
                    <i class="fa-solid fa-table-cells mr-1" aria-hidden="true"></i> Pick table
                </button>
            </div>

            <div class="overflow-y-auto p-5">
                <div x-show="mode === 'scan'">
                    <p class="text-sm text-gray-500">Point your camera at the QR code on your table.</p>
                    <div id="portal-qr-reader" class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-black"></div>
                    <p class="mt-3 text-center text-sm text-red-600" x-show="scanError" x-text="scanError"></p>
                </div>

                <div x-show="mode === 'manual'" class="space-y-4">
                    <template x-if="groups.length === 0">
                        <p class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-500">No tables configured yet. Ask staff for assistance.</p>
                    </template>

                    <template x-if="groups.length > 0">
                        <div class="space-y-4">
                            <div>
                                <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Area / Floor</label>
                                <select x-model="selectedGroupId" x-on:change="onGroupChange()" class="sg-input w-full">
                                    <template x-for="group in groups" :key="group.id">
                                        <option x-bind:value="group.id" x-text="group.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-emerald-500"></span> Available</span>
                                <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-amber-500"></span> In use</span>
                                <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-full bg-red-500"></span> Full</span>
                                <span class="ml-auto text-[10px] text-gray-500">Live · refreshes every few seconds</span>
                            </div>

                            <div class="md:grid md:grid-cols-2 md:items-start md:gap-6">
                                <template x-if="selectedGroup()?.map_image_url">
                                    <div class="relative overflow-hidden rounded-xl border border-gray-200 bg-black">
                                        <img x-bind:src="selectedGroup()?.map_image_url" alt="Floor map" class="block w-full select-none">
                                        <template x-for="table in (selectedGroup()?.tables ?? []).filter(t => t.is_placed_on_map)" :key="'pick-map-' + table.id">
                                            <button
                                                type="button"
                                                class="absolute flex size-9 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full text-xs font-bold text-gray-900 shadow-lg transition"
                                                x-bind:class="[mapMarkerClass(table), isTableSelectable(table) ? 'hover:scale-110' : 'cursor-not-allowed opacity-60']"
                                                x-bind:style="mapMarkerStyle(table)"
                                                x-bind:disabled="!isTableSelectable(table)"
                                                x-on:click="selectTable(table)"
                                                x-text="table.table_number"
                                                x-bind:title="'Table ' + table.table_number + ' — ' + tableStatusLabel(table)"
                                            ></button>
                                        </template>
                                    </div>
                                </template>

                                <div class="mt-4 md:mt-0" x-bind:class="selectedGroup()?.map_image_url ? '' : 'md:col-span-2'">
                                    <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Tap your table</label>
                                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-2">
                                        <template x-for="table in (selectedGroup()?.tables ?? [])" :key="'pick-grid-' + table.id">
                                            <button
                                                type="button"
                                                class="rounded-xl border p-3 text-left transition"
                                                x-bind:class="tableStatusClass(table)"
                                                x-bind:disabled="!isTableSelectable(table)"
                                                x-on:click="selectTable(table)"
                                            >
                                                <p class="text-sm font-bold text-gray-900">Table <span x-text="table.table_number"></span></p>
                                                <p class="mt-0.5 text-xs text-gray-500"><span x-text="table.chair_count"></span> chairs</p>
                                                <p
                                                    class="mt-1 text-[10px] font-semibold uppercase"
                                                    x-bind:class="table.is_full ? 'text-red-600' : (table.is_occupied ? 'text-orange-600' : 'text-emerald-600')"
                                                    x-text="tableStatusLabel(table)"
                                                ></p>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <template x-if="selectedTable()">
                                <div class="rounded-xl border border-orange-500/30 bg-orange-500/10 p-4">
                                    <p class="text-sm font-semibold text-gray-900">
                                        Selected: Table <span x-text="selectedTable().table_number"></span>
                                        <span class="text-gray-500">·</span>
                                        <span x-text="selectedGroup()?.name"></span>
                                    </p>
                                    <div class="mt-3">
                                        <label class="mb-2 block text-xs font-bold uppercase tracking-widest text-gray-500">Guests at your table</label>
                                        <select x-model="occupiedChairs" class="sg-input w-full">
                                            <template x-for="n in guestOptions()" :key="'guest-' + n">
                                                <option x-bind:value="n" x-text="n + (n === 1 ? ' guest' : ' guests')"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </template>

                            <button
                                type="button"
                                class="w-full rounded-xl bg-orange-500 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50"
                                x-on:click="submitManual()"
                                x-bind:disabled="loading || !selectedTable() || !isTableSelectable(selectedTable())"
                            >
                                <span x-show="!loading">Confirm table</span>
                                <span x-show="loading" x-cloak>Saving...</span>
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</div>
