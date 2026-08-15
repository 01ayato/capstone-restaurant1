/**
 * @param {import('alpinejs').Alpine} Alpine
 */
import QRCode from 'qrcode';

export function registerTablesConfigurationsPage(Alpine) {
    Alpine.data('tablesConfigurationsPage', () => {
        let initial = {};

        try {
            const el = document.getElementById('tables-page-initial');
            if (el?.textContent?.trim()) {
                initial = JSON.parse(el.textContent);
            }
        } catch {
            initial = {};
        }

        return {
            activeTab: 'list',
            groups: initial?.groups ?? [],
            routes: initial?.routes ?? {},
            loading: false,
            refreshing: false,

            groupModalOpen: false,
            groupModalMode: 'create',
            groupDraft: { id: null, name: '', sort_order: 0 },

            tableModalOpen: false,
            tableModalMode: 'create',
            tableDraft: { id: null, group_id: null, table_number: '', chair_count: 4, sort_order: 0 },

            qrModalOpen: false,
            qrDataUrl: '',
            qrTable: null,

            mapUploadGroupId: null,

            mapEditorOpen: false,
            mapEditorGroup: null,
            selectedPlacementTableId: null,
            savingPosition: false,

            monitorSelectedTableId: null,

            openMapEditor(group) {
                if (!group.map_image_url) {
                    window.toast?.warning?.('Upload a floor map first.');
                    return;
                }

                if (group.tables.length === 0) {
                    window.toast?.warning?.('Add at least one table to this group.');
                    return;
                }

                this.mapEditorGroup = group;
                this.selectedPlacementTableId = group.tables[0]?.id ?? null;
                this.mapEditorOpen = true;
            },

            closeMapEditor() {
                this.mapEditorOpen = false;
                this.mapEditorGroup = null;
                this.selectedPlacementTableId = null;
            },

            selectPlacementTable(tableId) {
                this.selectedPlacementTableId = tableId;
            },

            isPlacementSelected(tableId) {
                return this.selectedPlacementTableId === tableId;
            },

            mapMarkerStyle(table) {
                if (!table.is_placed_on_map) {
                    return {};
                }

                return {
                    left: `${table.map_x}%`,
                    top: `${table.map_y}%`,
                };
            },

            mapMarkerClass(table) {
                if (this.mapEditorOpen && this.isPlacementSelected(table.id)) {
                    return 'bg-indigo-600 ring-2 ring-white ring-offset-2 ring-offset-indigo-600';
                }

                if (this.activeTab === 'monitor' && this.monitorSelectedTableId === table.id) {
                    return table.is_occupied
                        ? 'bg-amber-500 ring-2 ring-white ring-offset-2 ring-offset-amber-500'
                        : 'bg-emerald-600 ring-2 ring-white ring-offset-2 ring-offset-emerald-600';
                }

                if (table.is_occupied) {
                    return 'bg-amber-500';
                }

                return this.activeTab === 'monitor' ? 'bg-emerald-600' : 'bg-zinc-900';
            },

            selectMonitorTable(tableId) {
                this.monitorSelectedTableId = tableId;
            },

            monitorSelectedTable() {
                if (!this.monitorSelectedTableId) {
                    return null;
                }

                for (const group of this.groups) {
                    const table = group.tables?.find((t) => t.id === this.monitorSelectedTableId);
                    if (table) {
                        return table;
                    }
                }

                return null;
            },

            monitorSelectedGroup() {
                if (!this.monitorSelectedTableId) {
                    return null;
                }

                return this.groups.find((g) => g.tables?.some((t) => t.id === this.monitorSelectedTableId)) ?? null;
            },

            unplacedTables(group) {
                return group.tables?.filter((t) => !t.is_placed_on_map) ?? [];
            },

            async handleMapClick(event) {
                if (!this.mapEditorGroup || !this.selectedPlacementTableId) {
                    window.toast?.warning?.('Select a table first, then click the map.');
                    return;
                }

                const target = event.currentTarget;
                const rect = target.getBoundingClientRect();
                const mapX = Math.min(100, Math.max(0, ((event.clientX - rect.left) / rect.width) * 100));
                const mapY = Math.min(100, Math.max(0, ((event.clientY - rect.top) / rect.height) * 100));

                await this.saveTablePosition(this.selectedPlacementTableId, mapX, mapY);
            },

            async saveTablePosition(tableId, mapX, mapY) {
                this.savingPosition = true;

                try {
                    const response = await fetch(`${this.routes.updateTablePositionBase}/${tableId}/position`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            map_x: mapX,
                            map_y: mapY,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not save table position.');
                        return;
                    }

                    this.applyTableUpdate(data.table);
                    window.toast?.success?.(data.message ?? 'Position saved.');
                } catch {
                    window.toast?.error?.('Could not save table position.');
                } finally {
                    this.savingPosition = false;
                }
            },

            async clearTablePosition(table) {
                if (!table) {
                    return;
                }

                this.savingPosition = true;

                try {
                    const response = await fetch(`${this.routes.updateTablePositionBase}/${table.id}/position`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            map_x: null,
                            map_y: null,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not clear position.');
                        return;
                    }

                    this.applyTableUpdate(data.table);
                    window.toast?.success?.(data.message ?? 'Position cleared.');
                } catch {
                    window.toast?.error?.('Could not clear position.');
                } finally {
                    this.savingPosition = false;
                }
            },

            applyTableUpdate(updatedTable) {
                const group = this.groups.find((g) => g.id === updatedTable.table_group_id);
                if (!group) {
                    return;
                }

                const idx = group.tables.findIndex((t) => t.id === updatedTable.id);
                if (idx !== -1) {
                    group.tables[idx] = updatedTable;
                }

                if (this.mapEditorGroup?.id === group.id) {
                    this.mapEditorGroup = group;
                }
            },

            placedTablesCount(group) {
                return group.tables?.filter((t) => t.is_placed_on_map)?.length ?? 0;
            },

            selectedPlacementTable() {
                if (!this.mapEditorGroup || !this.selectedPlacementTableId) {
                    return null;
                }

                return this.mapEditorGroup.tables?.find((t) => t.id === this.selectedPlacementTableId) ?? null;
            },

            openGroupCreate() {
                this.groupModalMode = 'create';
                this.groupDraft = { id: null, name: '', sort_order: 0 };
                this.groupModalOpen = true;
            },

            openGroupEdit(group) {
                this.groupModalMode = 'edit';
                this.groupDraft = {
                    id: group.id,
                    name: group.name,
                    sort_order: group.sort_order ?? 0,
                };
                this.groupModalOpen = true;
            },

            closeGroupModal() {
                this.groupModalOpen = false;
            },

            openTableCreate(groupId) {
                this.tableModalMode = 'create';
                this.tableDraft = {
                    id: null,
                    group_id: groupId,
                    table_number: '',
                    chair_count: 4,
                    sort_order: 0,
                };
                this.tableModalOpen = true;
            },

            openTableEdit(table) {
                this.tableModalMode = 'edit';
                this.tableDraft = {
                    id: table.id,
                    group_id: table.table_group_id,
                    table_number: table.table_number,
                    chair_count: table.chair_count,
                    sort_order: table.sort_order ?? 0,
                };
                this.tableModalOpen = true;
            },

            closeTableModal() {
                this.tableModalOpen = false;
            },

            async showQr(table) {
                try {
                    this.qrTable = table;
                    this.qrDataUrl = await QRCode.toDataURL(table.sit_url, {
                        width: 280,
                        margin: 2,
                        color: { dark: '#18181b', light: '#ffffff' },
                    });
                    this.qrModalOpen = true;
                } catch {
                    window.toast?.error?.('Could not generate QR code.');
                }
            },

            closeQrModal() {
                this.qrModalOpen = false;
                this.qrTable = null;
                this.qrDataUrl = '';
            },

            downloadQr() {
                if (!this.qrDataUrl || !this.qrTable) {
                    return;
                }

                const link = document.createElement('a');
                link.href = this.qrDataUrl;
                link.download = `qr-${this.qrTable.sit_code}.png`;
                link.click();
            },

            async saveGroup() {
                if (!this.groupDraft.name.trim()) {
                    window.toast?.warning?.('Group name is required.');
                    return;
                }

                this.loading = true;
                const isEdit = this.groupModalMode === 'edit';
                const url = isEdit
                    ? `${this.routes.updateGroupBase}/${this.groupDraft.id}`
                    : this.routes.storeGroup;

                try {
                    const response = await fetch(url, {
                        method: isEdit ? 'PATCH' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            name: this.groupDraft.name,
                            sort_order: Number(this.groupDraft.sort_order) || 0,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not save group.');
                        return;
                    }

                    if (isEdit) {
                        const idx = this.groups.findIndex((g) => g.id === data.group.id);
                        if (idx !== -1) {
                            this.groups[idx] = data.group;
                        }
                    } else {
                        this.groups.push(data.group);
                    }

                    this.closeGroupModal();
                    window.toast?.success?.(data.message ?? 'Group saved.');
                } catch {
                    window.toast?.error?.('Could not save group.');
                } finally {
                    this.loading = false;
                }
            },

            async deleteGroup(group) {
                const ok = await window.confirmDialog({
                    title: 'Delete this group?',
                    text: `This will remove "${group.name}" and all its tables.`,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    icon: 'warning',
                });

                if (!ok) {
                    return;
                }

                try {
                    const response = await fetch(`${this.routes.destroyGroupBase}/${group.id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not delete group.');
                        return;
                    }

                    this.groups = this.groups.filter((g) => g.id !== group.id);
                    window.toast?.success?.(data.message ?? 'Group deleted.');
                } catch {
                    window.toast?.error?.('Could not delete group.');
                }
            },

            async saveTable() {
                if (!this.tableDraft.table_number.trim()) {
                    window.toast?.warning?.('Table number is required.');
                    return;
                }

                this.loading = true;
                const isEdit = this.tableModalMode === 'edit';
                const url = isEdit
                    ? `${this.routes.updateTableBase}/${this.tableDraft.id}`
                    : `${this.routes.storeTableBase}/${this.tableDraft.group_id}/tables`;

                try {
                    const response = await fetch(url, {
                        method: isEdit ? 'PATCH' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            table_number: this.tableDraft.table_number,
                            chair_count: Number(this.tableDraft.chair_count) || 4,
                            sort_order: Number(this.tableDraft.sort_order) || 0,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not save table.');
                        return;
                    }

                    const group = this.groups.find((g) => g.id === (isEdit ? this.tableDraft.group_id : data.group_id));
                    if (group) {
                        if (isEdit) {
                            const idx = group.tables.findIndex((t) => t.id === data.table.id);
                            if (idx !== -1) {
                                group.tables[idx] = data.table;
                            }
                        } else {
                            group.tables.push(data.table);
                        }
                    }

                    if (this.mapEditorGroup?.id === (isEdit ? this.tableDraft.group_id : data.group_id)) {
                        this.mapEditorGroup = this.groups.find((g) => g.id === this.mapEditorGroup.id) ?? null;
                    }

                    this.closeTableModal();
                    window.toast?.success?.(data.message ?? 'Table saved.');
                } catch {
                    window.toast?.error?.('Could not save table.');
                } finally {
                    this.loading = false;
                }
            },

            async deleteTable(table, groupId) {
                const ok = await window.confirmDialog({
                    title: 'Delete this table?',
                    text: `Remove Table ${table.table_number}?`,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    icon: 'warning',
                });

                if (!ok) {
                    return;
                }

                try {
                    const response = await fetch(`${this.routes.destroyTableBase}/${table.id}`, {
                        method: 'DELETE',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not delete table.');
                        return;
                    }

                    const group = this.groups.find((g) => g.id === groupId);
                    if (group) {
                        group.tables = group.tables.filter((t) => t.id !== table.id);
                    }

                    window.toast?.success?.(data.message ?? 'Table deleted.');
                } catch {
                    window.toast?.error?.('Could not delete table.');
                }
            },

            triggerMapUpload(groupId) {
                this.mapUploadGroupId = groupId;
                this.$refs.mapFileInput?.click();
            },

            async uploadMap(event) {
                const file = event.target.files?.[0];
                const groupId = this.mapUploadGroupId;

                event.target.value = '';

                if (!file || !groupId) {
                    return;
                }

                const formData = new FormData();
                formData.append('map_image', file);

                try {
                    const response = await fetch(`${this.routes.uploadMapBase}/${groupId}/map`, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: formData,
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not upload map.');
                        return;
                    }

                    const idx = this.groups.findIndex((g) => g.id === data.group.id);
                    if (idx !== -1) {
                        this.groups[idx] = data.group;
                    }

                    window.toast?.success?.(data.message ?? 'Map uploaded.');
                } catch {
                    window.toast?.error?.('Could not upload map.');
                }
            },

            setTableStatus(table, status) {
                if (status === 'available') {
                    return this.updateOccupancy(table, 0);
                }

                const chairs = table.occupied_chairs > 0 ? table.occupied_chairs : table.chair_count;

                return this.updateOccupancy(table, chairs);
            },

            async updateOccupancy(table, chairs) {
                try {
                    const response = await fetch(`${this.routes.updateOccupancyBase}/${table.id}/occupancy`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({ occupied_chairs: Number(chairs) }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not update occupancy.');
                        return;
                    }

                    const group = this.groups.find((g) => g.id === table.table_group_id);
                    if (group) {
                        const idx = group.tables.findIndex((t) => t.id === data.table.id);
                        if (idx !== -1) {
                            group.tables[idx] = data.table;
                        }
                    }

                    if (this.monitorSelectedTableId === data.table.id) {
                        this.monitorSelectedTableId = data.table.id;
                    }

                    window.toast?.success?.(data.message ?? 'Occupancy updated.');
                } catch {
                    window.toast?.error?.('Could not update occupancy.');
                }
            },

            async refreshMonitor() {
                this.refreshing = true;

                try {
                    const response = await fetch(this.routes.monitor, {
                        headers: { Accept: 'application/json' },
                    });

                    const data = await response.json().catch(() => ({}));

                    if (response.ok && data.groups) {
                        this.groups = data.groups;
                        if (this.monitorSelectedTableId && !this.monitorSelectedTable()) {
                            this.monitorSelectedTableId = null;
                        }
                    }
                } catch {
                    window.toast?.error?.('Could not refresh monitor.');
                } finally {
                    this.refreshing = false;
                }
            },

            totalTables() {
                return this.groups.reduce((sum, g) => sum + (g.tables?.length ?? 0), 0);
            },

            occupiedTablesCount() {
                return this.groups.reduce(
                    (sum, g) => sum + (g.tables?.filter((t) => t.is_occupied)?.length ?? 0),
                    0,
                );
            },

            totalChairs() {
                return this.groups.reduce(
                    (sum, g) => sum + (g.tables?.reduce((s, t) => s + t.chair_count, 0) ?? 0),
                    0,
                );
            },

            occupiedChairsCount() {
                return this.groups.reduce(
                    (sum, g) => sum + (g.tables?.reduce((s, t) => s + t.occupied_chairs, 0) ?? 0),
                    0,
                );
            },

            chairOptions(chairCount) {
                const options = [];
                for (let i = 0; i <= chairCount; i++) {
                    options.push(i);
                }
                return options;
            },
        };
    });
}
