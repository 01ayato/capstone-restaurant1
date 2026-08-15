/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerPortalReservationsPage(Alpine) {
    Alpine.data('portalReservationsPage', (initial = {}) => ({
        groups: initial?.groups ?? [],
        selectedGroupId: initial?.selectedGroupId ?? initial?.groups?.[0]?.id ?? null,
        selectedTableId: initial?.selectedTableId ?? null,
        partySize: Number(initial?.partySize ?? 2) || 2,
        additionalChairs: Number(initial?.additionalChairs ?? 0) || 0,
        reservedAt: initial?.reservedAt ?? '',
        step: Number(initial?.step ?? 1) || 1,
        customerName: initial?.customerName ?? '',
        contactNumber: initial?.contactNumber ?? '',
        email: initial?.email ?? '',
        specialInstructions: initial?.specialInstructions ?? '',
        menuCategories: initial?.menu ?? [],
        orderItems: initial?.orderItems ?? {},
        activeMenuCategory: 'all',
        menuSearch: '',
        tableDeposit: Number(initial?.tableDeposit ?? 500) || 500,
        preorderDeposit: Number(initial?.preorderDeposit ?? 500) || 500,
        depositScanUrl: initial?.depositScanUrl ?? '',
        availabilityUrl: initial?.availabilityUrl ?? '',
        openingHoursLabel: initial?.openingHoursLabel ?? 'Mon–Sun · 2:00 PM – 12:00 AM',
        openHour: Number(initial?.openHour ?? 14) || 14,
        csrfToken: initial?.csrfToken ?? '',
        paymentMethod: initial?.paymentMethod ?? 'gcash',
        scanning: false,
        scanError: null,
        scanMessage: null,
        referenceNumber: null,
        amountPaidLabel: null,
        bookedTableIds: [],
        occupiedTableIds: [],
        withinOpeningHours: true,
        availabilityLoading: false,
        availabilityTimer: null,

        init() {
            if (!this.selectedGroupId && this.groups[0]) {
                this.selectedGroupId = this.groups[0].id;
            }

            if (this.selectedTableId || initial?.hasErrors) {
                this.step = initial?.hasErrors ? Math.max(4, Number(initial?.step ?? 4)) : 2;
                const table = this.findTable(this.selectedTableId);
                if (table) {
                    this.selectedGroupId = table.group_id;
                }
            }

            // Seed occupied from initial payload.
            this.occupiedTableIds = this.groups.flatMap((g) =>
                (g.tables ?? []).filter((t) => t.is_occupied).map((t) => Number(t.id)),
            );

            this.$watch('reservedAt', () => this.scheduleAvailabilityRefresh());
            if (this.reservedAt) {
                this.refreshAvailability();
            }
        },

        steps() {
            return [
                { num: 1, label: 'Your info', icon: 'fa-user' },
                { num: 2, label: 'Table & time', icon: 'fa-chair' },
                { num: 3, label: 'Pre-order', icon: 'fa-utensils' },
                { num: 4, label: 'Deposit', icon: 'fa-wallet' },
            ];
        },

        goToStep(target) {
            if (target < this.step) {
                this.step = target;
                this.scrollToFormTop();
                return;
            }

            if (target === 2 && this.canProceedStep1()) {
                this.step = 2;
                this.scrollToFormTop();
                return;
            }

            if (target === 3 && this.canProceedStep2()) {
                this.step = 3;
                this.scrollToFormTop();
                return;
            }

            if (target === 4 && this.canProceedStep2()) {
                this.step = 4;
                this.scrollToFormTop();
                return;
            }

            if (target > this.step) {
                this.showStepValidation();
            }
        },

        nextStep() {
            if (this.step === 1) {
                if (!this.canProceedStep1()) {
                    window.toast?.warning?.('Please enter your name and contact number.');
                    return;
                }
                this.step = 2;
            } else if (this.step === 2) {
                if (!this.canProceedStep2()) {
                    this.showStepValidation();
                    return;
                }
                this.step = 3;
            } else if (this.step === 3) {
                this.step = 4;
            }

            this.scrollToFormTop();
        },

        prevStep() {
            if (this.step > 1) {
                this.step -= 1;
                this.scrollToFormTop();
            }
        },

        scrollToFormTop() {
            this.$nextTick(() => {
                document.getElementById('reservation-form-top')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },

        showStepValidation() {
            const hint = this.stepValidationHint();
            if (hint) {
                window.toast?.warning?.(hint);
            }
        },

        stepValidationHint() {
            if (this.step === 1 && !this.canProceedStep1()) {
                return 'Please enter your name and contact number.';
            }

            if (this.step === 2) {
                if (!this.reservedAt) {
                    return 'Please choose a date and time.';
                }
                if (!this.withinOpeningHours) {
                    return `We're open ${this.openingHoursLabel}. Please pick a time from 2:00 PM (dining window must end by midnight).`;
                }
                if (!this.selectedTableId) {
                    return 'Please tap an available table.';
                }
                if (this.isTableUnavailable(this.selectedTableId)) {
                    return 'That table is booked or occupied. Please choose another.';
                }
            }

            return null;
        },

        canProceedStep1() {
            return this.customerName.trim().length > 0 && this.contactNumber.trim().length > 0;
        },

        canProceedStep2() {
            return Boolean(this.reservedAt)
                && this.withinOpeningHours
                && Boolean(this.selectedTableId)
                && !this.isTableUnavailable(this.selectedTableId);
        },

        scheduleAvailabilityRefresh() {
            if (this.availabilityTimer) {
                clearTimeout(this.availabilityTimer);
            }

            this.availabilityTimer = setTimeout(() => this.refreshAvailability(), 350);
        },

        async refreshAvailability() {
            if (!this.reservedAt || !this.availabilityUrl) {
                this.bookedTableIds = [];
                this.withinOpeningHours = true;
                return;
            }

            this.availabilityLoading = true;

            try {
                const url = new URL(this.availabilityUrl, window.location.origin);
                url.searchParams.set('reserved_at', this.reservedAt);

                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    return;
                }

                this.withinOpeningHours = data.within_opening_hours !== false;
                this.bookedTableIds = (data.booked_table_ids ?? []).map(Number);
                this.occupiedTableIds = (data.occupied_table_ids ?? []).map(Number);

                if (this.selectedTableId && this.isTableUnavailable(this.selectedTableId)) {
                    this.selectedTableId = null;
                    window.toast?.warning?.('Your selected table is no longer available for that time.');
                }
            } catch {
                //
            } finally {
                this.availabilityLoading = false;
            }
        },

        isTableBooked(tableId) {
            return this.bookedTableIds.includes(Number(tableId));
        },

        isTableOccupied(tableId) {
            return this.occupiedTableIds.includes(Number(tableId));
        },

        isTableUnavailable(tableId) {
            return this.isTableBooked(tableId) || this.isTableOccupied(tableId);
        },

        tableStatusLabel(table) {
            if (this.isTableOccupied(table.id)) {
                return 'Occupied';
            }

            if (this.isTableBooked(table.id)) {
                return 'Booked';
            }

            if (this.partyFitsTable(table)) {
                return 'Fits your party';
            }

            const extras = Math.max(0, this.seatsNeeded() - table.chair_count);

            return extras === 1
                ? 'OK · add 1 chair'
                : `OK · add ${extras} chairs`;
        },

        selectTable(table) {
            if (this.isTableUnavailable(table.id)) {
                const reason = this.isTableOccupied(table.id)
                    ? 'This table is currently occupied.'
                    : 'This table is already reserved at that time.';
                window.toast?.warning?.(reason);
                return;
            }

            if (!this.reservedAt) {
                window.toast?.warning?.('Choose a date and time first.');
                return;
            }

            if (!this.withinOpeningHours) {
                window.toast?.warning?.(`We're open ${this.openingHoursLabel}.`);
                return;
            }

            this.selectedTableId = table.id;
            this.onPartySizeChange();
        },

        isTableSelected(tableId) {
            return Number(this.selectedTableId) === Number(tableId);
        },

        tableCardClass(table) {
            if (this.isTableUnavailable(table.id)) {
                return 'cursor-not-allowed border-red-500/40 bg-red-500/10 opacity-60';
            }

            if (this.isTableSelected(table.id)) {
                return 'border-orange-400 bg-orange-50 ring-2 ring-orange-200';
            }

            if (this.partyFitsTable(table)) {
                return 'border-emerald-500/40 bg-emerald-500/10 hover:border-emerald-400';
            }

            return 'border-amber-500/40 bg-amber-500/10 hover:border-amber-400';
        },

        mapMarkerClass(table) {
            if (this.isTableUnavailable(table.id)) {
                return 'cursor-not-allowed bg-red-500 opacity-70';
            }

            if (this.isTableSelected(table.id)) {
                return 'bg-orange-500 ring-2 ring-white ring-offset-2 ring-offset-orange-500';
            }

            if (this.partyFitsTable(table)) {
                return 'bg-emerald-600';
            }

            return 'bg-amber-500';
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

        partyFitsTable(table) {
            const guests = Number(this.partySize) || 0;

            return guests > 0 && guests <= table.chair_count;
        },

        seatsNeeded() {
            return Number(this.partySize) || 0;
        },

        needsAdditionalChairs() {
            const table = this.selectedTable();

            if (!table) {
                return false;
            }

            return this.seatsNeeded() > table.chair_count;
        },

        minAdditionalChairs() {
            const table = this.selectedTable();

            if (!table) {
                return 0;
            }

            return Math.max(0, this.seatsNeeded() - table.chair_count);
        },

        additionalChairOptions() {
            const min = this.minAdditionalChairs();
            const options = [];

            for (let i = min; i <= 20; i++) {
                options.push(i);
            }

            return options.length > 0 ? options : [0];
        },

        onPartySizeChange() {
            if (!this.needsAdditionalChairs()) {
                this.additionalChairs = 0;
            } else {
                this.additionalChairs = this.minAdditionalChairs();
            }

            if (this.selectedTableId && this.isTableUnavailable(this.selectedTableId)) {
                this.selectedTableId = null;
            }
        },

        findTable(tableId) {
            if (!tableId) {
                return null;
            }

            for (const group of this.groups) {
                const table = group.tables?.find((t) => t.id === Number(tableId));
                if (table) {
                    return { ...table, group_id: group.id, group_name: group.name };
                }
            }

            return null;
        },

        onGroupChange() {
            const group = this.selectedGroup();
            const stillInGroup = group?.tables?.some((t) => t.id === Number(this.selectedTableId));

            if (!stillInGroup) {
                this.selectedTableId = null;
            }
        },

        selectedGroup() {
            return this.groups.find((g) => g.id === Number(this.selectedGroupId)) ?? null;
        },

        selectedTable() {
            if (!this.selectedTableId) {
                return null;
            }

            return this.findTable(this.selectedTableId);
        },

        canSubmit() {
            if (!this.canProceedStep1() || !this.canProceedStep2()) {
                return false;
            }

            if (this.paymentMethod === 'gcash' || this.paymentMethod === 'maya') {
                const proof = document.getElementById('deposit_payment_proof');
                return Boolean(proof?.files?.length);
            }

            return true;
        },

        depositBreakdown() {
            const preorder = this.hasOrderItems() ? this.preorderDeposit : 0;

            return {
                table: this.tableDeposit,
                preorder,
                total: this.tableDeposit + preorder,
            };
        },

        async onDepositProofSelected(event) {
            const file = event.target.files?.[0];
            this.scanError = null;
            this.scanMessage = null;
            this.referenceNumber = null;
            this.amountPaidLabel = null;

            if (!file || !this.depositScanUrl) {
                return;
            }

            if (this.paymentMethod !== 'gcash' && this.paymentMethod !== 'maya') {
                return;
            }

            this.scanning = true;

            try {
                const body = new FormData();
                body.append('payment_proof', file);
                body.append('payment_method', this.paymentMethod);
                body.append('_token', this.csrfToken);

                const response = await fetch(this.depositScanUrl, {
                    method: 'POST',
                    body,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                const payload = await response.json();

                if (!response.ok) {
                    this.scanError = payload.message || 'Could not read the payment screenshot.';
                    return;
                }

                this.referenceNumber = payload.reference_number || null;
                this.amountPaidLabel = payload.amount_paid_label || null;
                this.scanMessage = payload.message || null;
            } catch {
                this.scanError = 'Could not upload payment proof. Please try again.';
            } finally {
                this.scanning = false;
            }
        },

        depositAmountMismatch() {
            if (!this.amountPaidLabel) {
                return false;
            }

            const paid = Number(String(this.amountPaidLabel).replace(/[^\d.]/g, ''));
            return paid > 0 && paid < this.depositBreakdown().total;
        },

        stepStatus(stepNum) {
            if (stepNum < this.step) {
                return 'done';
            }
            if (stepNum === this.step) {
                return 'active';
            }

            return 'upcoming';
        },

        formattedReservedAt() {
            if (!this.reservedAt) {
                return null;
            }

            try {
                return new Date(this.reservedAt).toLocaleString('en-PH', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                });
            } catch {
                return this.reservedAt;
            }
        },

        bookingSummary() {
            const table = this.selectedTable();
            const orderCount = this.orderLines().reduce((sum, line) => sum + line.qty, 0);

            return {
                name: this.customerName.trim() || null,
                contact: this.contactNumber.trim() || null,
                when: this.formattedReservedAt(),
                table: table ? `Table ${table.table_number}` : null,
                area: table?.group_name ?? null,
                guests: this.seatsNeeded(),
                extras: Number(this.additionalChairs) || 0,
                orderCount,
                orderTotal: this.orderSubtotal(),
                deposit: this.depositBreakdown(),
            };
        },

        filteredMenuCategories() {
            const query = this.menuSearch.trim().toLowerCase();

            return this.menuCategories
                .map((category) => {
                    let items = category.items ?? [];

                    if (this.activeMenuCategory !== 'all' && Number(this.activeMenuCategory) !== Number(category.id)) {
                        return { ...category, items: [] };
                    }

                    if (query) {
                        items = items.filter((item) => item.name.toLowerCase().includes(query));
                    }

                    return { ...category, items };
                })
                .filter((category) => category.items.length > 0);
        },

        menuCategoryTabs() {
            return [
                { id: 'all', name: 'All' },
                ...this.menuCategories.map((c) => ({ id: c.id, name: c.name })),
            ];
        },

        totalMenuItems() {
            return this.menuCategories.reduce((sum, cat) => sum + (cat.items?.length ?? 0), 0);
        },

        findMenuItem(itemId) {
            for (const category of this.menuCategories) {
                const item = category.items?.find((i) => i.id === Number(itemId));
                if (item) {
                    return { ...item, category_name: category.name };
                }
            }

            return null;
        },

        itemQty(itemId) {
            return Number(this.orderItems[itemId] ?? 0) || 0;
        },

        addMenuItem(item) {
            const id = item.id;
            this.orderItems = {
                ...this.orderItems,
                [id]: this.itemQty(id) + 1,
            };
        },

        removeMenuItem(itemId) {
            const qty = this.itemQty(itemId);
            if (qty <= 1) {
                const next = { ...this.orderItems };
                delete next[itemId];
                this.orderItems = next;
                return;
            }

            this.orderItems = {
                ...this.orderItems,
                [itemId]: qty - 1,
            };
        },

        orderLines() {
            return Object.entries(this.orderItems)
                .map(([itemId, qty]) => {
                    const item = this.findMenuItem(itemId);
                    if (!item || qty <= 0) {
                        return null;
                    }

                    return {
                        item,
                        qty: Number(qty),
                        lineTotal: Number(item.price) * Number(qty),
                    };
                })
                .filter(Boolean);
        },

        hasOrderItems() {
            return this.orderLines().length > 0;
        },

        orderSubtotal() {
            return this.orderLines().reduce((sum, line) => sum + line.lineTotal, 0);
        },

        formatMoney(amount) {
            return `₱${Number(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        },

        compiledOrderNotes() {
            const lines = this.orderLines();

            if (lines.length === 0 && !this.specialInstructions.trim()) {
                return '';
            }

            const parts = [];

            if (lines.length > 0) {
                parts.push(
                    lines
                        .map((line) => `${line.qty}× ${line.item.name} — ${this.formatMoney(line.item.price)} each (${this.formatMoney(line.lineTotal)})`)
                        .join('\n'),
                );
                parts.push(`Estimated total: ${this.formatMoney(this.orderSubtotal())}`);
            }

            if (this.specialInstructions.trim()) {
                parts.push(`Special instructions: ${this.specialInstructions.trim()}`);
            }

            return parts.join('\n\n');
        },
    }));
}
