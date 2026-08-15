/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerPortalMyReservationsPage(Alpine) {
    Alpine.data('portalMyReservationsPage', (initial = {}) => ({
        reservations: initial?.reservations ?? [],
        viewOpen: false,
        selected: null,

        openView(reservation) {
            this.selected = reservation;
            this.viewOpen = true;
        },

        closeView() {
            this.viewOpen = false;
            this.selected = null;
        },

        statusBadgeClass(status) {
            const classes = {
                confirmed: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                cancelled: 'border-red-500/30 bg-red-500/10 text-red-300',
                completed: 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300',
                pending: 'border-amber-500/30 bg-amber-500/10 text-amber-300',
            };

            return classes[status] ?? 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300';
        },

        formatMoney(amount) {
            return `₱${Number(amount || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        },
    }));
}
