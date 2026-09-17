/**
 * Tabbed category navigation for the public menu page.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerPortalMenu(Alpine) {
    Alpine.data('portalMenu', () => ({
        activeCategory: '',

        init() {
            // Default to the first category so the grid is never empty on load.
            this.activeCategory = String(this.$el.dataset.initialCategoryId ?? '');
        },

        setCategory(categoryId) {
            this.activeCategory = String(categoryId);
        },
    }));
}
