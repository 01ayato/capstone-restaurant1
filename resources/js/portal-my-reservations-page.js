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
                confirmed: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
                cancelled: 'border-red-200 bg-red-50 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
                completed: 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-500/30 dark:bg-zinc-500/10 dark:text-zinc-300',
                pending: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
            };

            return classes[status] ?? 'border-zinc-200 bg-zinc-100 text-zinc-700 dark:border-zinc-500/30 dark:bg-zinc-500/10 dark:text-zinc-300';
        },

        formatMoney(amount) {
            return `₱${Number(amount || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            })}`;
        },
    }));
}
