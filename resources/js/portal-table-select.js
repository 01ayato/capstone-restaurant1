/**
 * @param {import('alpinejs').Alpine} Alpine
 */
import { Html5Qrcode } from 'html5-qrcode';

export function registerPortalTableSelect(Alpine) {
    Alpine.data('portalTableSelect', (initial = {}) => {
        let scanner = null;
        let refreshTimer = null;

        return {
            open: false,
            mode: 'manual',
            groups: initial?.groups ?? [],
            current: initial?.current ?? null,
            routes: initial?.routes ?? {},
            loading: false,
            scanning: false,
            scanError: '',

            selectedGroupId: initial?.groups?.[0]?.id ?? null,
            selectedTableId: null,
            occupiedChairs: 1,

            init() {
                if (this.groups.length === 0) {
                    this.loadGroups();
                }
            },

            async loadGroups() {
                try {
                    const response = await fetch(this.routes.lookup ?? '/sit/lookup', {
                        headers: { Accept: 'application/json' },
                    });
                    const data = await response.json();
                    this.groups = data.groups ?? [];
                    this.current = data.current ?? this.current;
                    if (!this.selectedGroupId && this.groups[0]) {
                        this.selectedGroupId = this.groups[0].id;
                    }

                    const selected = this.selectedTable();
                    if (selected && !this.isTableSelectable(selected)) {
                        this.selectedTableId = null;
                    }
                } catch {
                    //
                }
            },

            startLiveRefresh() {
                this.stopLiveRefresh();
                refreshTimer = setInterval(() => this.loadGroups(), 8000);
            },

            stopLiveRefresh() {
                if (refreshTimer) {
                    clearInterval(refreshTimer);
                    refreshTimer = null;
                }
            },

            openModal(mode = 'manual') {
                this.mode = mode;
                this.open = true;
                this.scanError = '';
                this.selectedTableId = null;
                this.loadGroups();
                this.startLiveRefresh();

                if (mode === 'scan') {
                    this.$nextTick(() => this.startScanner());
                }
            },

            closeModal() {
                this.stopScanner();
                this.stopLiveRefresh();
                this.open = false;
                this.selectedTableId = null;
                this.scanError = '';
            },

            setMode(mode) {
                if (this.mode === mode) {
                    return;
                }

                this.stopScanner();
                this.mode = mode;
                this.scanError = '';

                if (mode === 'scan') {
                    this.$nextTick(() => this.startScanner());
                }
            },

            onGroupChange() {
                this.selectedTableId = null;
                this.occupiedChairs = 1;
            },

            async startScanner() {
                if (this.scanning || !document.getElementById('portal-qr-reader')) {
                    return;
                }

                this.scanError = '';

                try {
                    scanner = new Html5Qrcode('portal-qr-reader');
                    await scanner.start(
                        { facingMode: 'environment' },
                        { fps: 8, qrbox: { width: 220, height: 220 } },
                        (decoded) => this.handleScan(decoded),
                        () => {},
                    );
                    this.scanning = true;
                } catch {
                    this.scanError = 'Could not access camera. Pick your table below instead.';
                    this.mode = 'manual';
                }
            },

            async stopScanner() {
                if (!scanner || !this.scanning) {
                    return;
                }

                try {
                    await scanner.stop();
                    scanner.clear();
                } catch {
                    //
                }

                scanner = null;
                this.scanning = false;
            },

            handleScan(decodedText) {
                const text = decodedText.trim();
                let sitCode = null;

                try {
                    const url = new URL(text, window.location.origin);
                    const match = url.pathname.match(/\/sit\/([^/]+)\/?$/);
                    if (match) {
                        sitCode = match[1];
                    }
                } catch {
                    const match = text.match(/\/sit\/([^/\s?#]+)/);
                    if (match) {
                        sitCode = match[1];
                    } else if (/^[a-z0-9-]+$/i.test(text)) {
                        sitCode = text;
                    }
                }

                if (!sitCode) {
                    this.scanError = 'That QR code is not a valid table link.';
                    return;
                }

                this.stopScanner();
                window.location.href = `${window.location.origin}/sit/${encodeURIComponent(sitCode)}`;
            },

            selectedGroup() {
                return this.groups.find((g) => g.id === Number(this.selectedGroupId)) ?? null;
            },

            selectedTable() {
                const group = this.selectedGroup();
                if (!group || !this.selectedTableId) {
                    return null;
                }

                return group.tables?.find((t) => t.id === Number(this.selectedTableId)) ?? null;
            },

            isTableSelectable(table) {
                return !table.is_full;
            },

            selectTable(table) {
                if (!this.isTableSelectable(table)) {
                    window.toast?.warning?.('This table is full. Please choose another table.');
                    return;
                }

                this.selectedTableId = table.id;
                this.occupiedChairs = Math.min(this.occupiedChairs, table.chair_count) || 1;
            },

            isTableSelected(tableId) {
                return Number(this.selectedTableId) === Number(tableId);
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

            tableStatusClass(table) {
                if (this.isTableSelected(table.id)) {
                    return 'border-orange-400 bg-orange-50 ring-2 ring-orange-200';
                }

                if (table.is_full) {
                    return 'cursor-not-allowed border-red-500/40 bg-red-500/10 opacity-60';
                }

                if (table.is_occupied) {
                    return 'border-amber-300 bg-amber-50 hover:border-amber-400';
                }

                return 'border-emerald-500/40 bg-emerald-500/10 hover:border-emerald-400';
            },

            mapMarkerClass(table) {
                if (this.isTableSelected(table.id)) {
                    return 'bg-orange-500 ring-2 ring-white ring-offset-2 ring-offset-orange-500';
                }

                if (table.is_full) {
                    return 'bg-red-500';
                }

                if (table.is_occupied) {
                    return 'bg-amber-500';
                }

                return 'bg-emerald-600';
            },

            tableStatusLabel(table) {
                if (table.is_full) {
                    return 'Full';
                }

                if (table.is_occupied) {
                    return `${table.occupied_chairs}/${table.chair_count} seats`;
                }

                return 'Available';
            },

            guestOptions() {
                const table = this.selectedTable();
                if (!table) {
                    return [];
                }

                const options = [];
                for (let i = 1; i <= table.chair_count; i++) {
                    options.push(i);
                }

                return options;
            },

            async submitManual() {
                const table = this.selectedTable();

                if (!this.selectedGroupId || !table) {
                    window.toast?.warning?.('Choose your area and tap your table number.');
                    return;
                }

                if (!this.isTableSelectable(table)) {
                    window.toast?.warning?.('This table is full. Please choose another table.');
                    return;
                }

                this.loading = true;

                try {
                    const response = await fetch(this.routes.select ?? '/sit/select', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify({
                            table_group_id: Number(this.selectedGroupId),
                            table_number: table.table_number,
                            occupied_chairs: Number(this.occupiedChairs) || 1,
                        }),
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not select that table.');
                        return;
                    }

                    this.current = data.table;
                    this.closeModal();
                    window.toast?.success?.(data.message ?? 'Table selected.');
                    window.location.reload();
                } catch {
                    window.toast?.error?.('Could not save table selection.');
                } finally {
                    this.loading = false;
                }
            },

            async clearTable() {
                try {
                    const response = await fetch(this.routes.clear ?? '/sit/clear', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                    });

                    const data = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        window.toast?.error?.(data.message ?? 'Could not clear table.');
                        return;
                    }

                    this.current = null;
                    window.toast?.success?.(data.message ?? 'Table cleared.');
                    window.location.reload();
                } catch {
                    window.toast?.error?.('Could not clear table.');
                }
            },
        };
    });
}
