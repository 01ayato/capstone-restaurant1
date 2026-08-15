/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerAdminReservationsPage(Alpine) {
    Alpine.data('adminReservationsPage', (initial = {}) => ({
        reservations: initial?.reservations ?? [],
        groups: initial?.groups ?? [],
        statuses: initial?.statuses ?? {},
        createOpen: Boolean(initial?.openCreate),
        viewOpen: false,
        editOpen: false,
        selected: null,
        editDraft: {},

        openCreate() {
            this.createOpen = true;
        },

        closeCreate() {
            this.createOpen = false;
        },

        openView(reservation) {
            this.selected = reservation;
            this.viewOpen = true;
        },

        closeView() {
            this.viewOpen = false;
            this.selected = null;
        },

        openEdit(reservation) {
            this.selected = reservation;
            this.editDraft = {
                customer_name: reservation.customer_name ?? '',
                contact_number: reservation.contact_number ?? '',
                email: reservation.email ?? '',
                party_size: reservation.party_size ?? 1,
                additional_chairs: reservation.additional_chairs ?? 0,
                reserved_at: reservation.reserved_at ?? '',
                dining_table_id: reservation.dining_table_id ?? '',
                notes: reservation.notes ?? '',
                status: reservation.status ?? 'pending',
                update_url: reservation.update_url ?? '',
            };
            this.editOpen = true;
        },

        closeEdit() {
            this.editOpen = false;
            this.selected = null;
            this.editDraft = {};
        },

        statusBadgeClass(status) {
            const classes = {
                confirmed: 'bg-emerald-100 text-emerald-700',
                cancelled: 'bg-red-100 text-red-700',
                completed: 'bg-zinc-100 text-zinc-600',
                pending: 'bg-amber-100 text-amber-800',
            };

            return classes[status] ?? 'bg-zinc-100 text-zinc-600';
        },
    }));
}
