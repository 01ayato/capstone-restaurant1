/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerMenuItemsPage(Alpine) {
    Alpine.data('menuItemsPage', () => {
        let initial = {};

        try {
            const el = document.getElementById('menu-page-initial');
            if (el?.textContent?.trim()) {
                initial = JSON.parse(el.textContent);
            }
        } catch {
            initial = {};
        }

        return {
        manageOpen: false,
        addOpen: false,
        editOpen: false,
        editCategory: null,
        editItemOpen: false,
        editItem: null,
        addCategoryId: String(initial?.categories?.[0]?.id ?? ''),
        categories: initial?.categories ?? [],
        routes: initial?.routes ?? {},

        isDrinksCategory(categoryId) {
            const category = this.categories.find((c) => String(c.id) === String(categoryId));
            return (category?.slug ?? '') === 'drinks';
        },

        openManage() {
            this.closeAdd();
            this.closeEdit();
            this.closeEditItem();
            this.manageOpen = true;
        },

        closeManage() {
            this.manageOpen = false;
        },

        openAdd() {
            this.closeEdit();
            this.closeEditItem();
            this.addOpen = true;
        },

        closeAdd() {
            this.addOpen = false;
        },

        openEdit(category) {
            this.closeAdd();
            this.closeEditItem();
            this.editCategory = {
                id: category.id,
                name: category.name ?? '',
                description: category.description ?? '',
                sort_order: category.sort_order ?? 0,
                is_active: Boolean(category.is_active),
                menu_items_count: category.menu_items_count ?? 0,
            };
            this.editOpen = true;
        },

        closeEdit() {
            this.editOpen = false;
            this.editCategory = null;
        },

        openEditItem(item) {
            this.closeAdd();
            this.closeEdit();
            this.editItem = {
                id: item.id,
                category_id: String(item.category_id ?? ''),
                name: item.name ?? '',
                price: item.price ?? '',
                description: item.description ?? '',
                is_available: Boolean(item.is_available),
                stock_level: item.stock_level ?? 0,
                reorder_level: item.reorder_level ?? 5,
                image_url: item.image_url ?? '',
            };
            this.editItemOpen = true;
        },

        closeEditItem() {
            this.editItemOpen = false;
            this.editItem = null;
        },

        editFormAction() {
            if (!this.editCategory?.id || !this.routes.updateBase) {
                return '#';
            }

            return `${this.routes.updateBase}/${this.editCategory.id}`;
        },

        editItemFormAction() {
            if (!this.editItem?.id || !this.routes.itemUpdateBase) {
                return '#';
            }

            return `${this.routes.itemUpdateBase}/${this.editItem.id}`;
        },

        destroyFormAction(categoryId) {
            if (!categoryId || !this.routes.destroyBase) {
                return '#';
            }

            return `${this.routes.destroyBase}/${categoryId}`;
        },

        async confirmDelete(category) {
            if (category.menu_items_count > 0) {
                if (typeof window.toast?.error === 'function') {
                    window.toast.error('Cannot delete a category that still has menu items.');
                }
                return;
            }

            const ok = await window.confirmDialog({
                title: 'Delete this category?',
                text: 'This action cannot be undone.',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel',
                icon: 'warning',
            });

            if (!ok) {
                return;
            }

            const form = document.getElementById(`delete-category-${category.id}`);
            form?.requestSubmit();
        },
    };
    });
}
