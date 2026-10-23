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
        addItemOpen: false,
        selectedCategoryId: '',
        searchQuery: '',
        addCategoryId: String(initial?.categories?.[0]?.id ?? ''),
        categories: initial?.categories ?? [],
        items: initial?.items ?? [],
        routes: initial?.routes ?? {},
        signatureCount: Number(initial?.signatureCount ?? 0),
        maxSignatureItems: Number(initial?.maxSignatureItems ?? 4),

        matchesItem(categoryId, itemName, description = '') {
            const matchesCategory = this.selectedCategoryId === '' || String(this.selectedCategoryId) === String(categoryId);
            const query = (this.searchQuery || '').trim().toLowerCase();
            if (!matchesCategory) {
                return false;
            }
            if (query === '') {
                return true;
            }
            const nameMatch = itemName ? String(itemName).toLowerCase().includes(query) : false;
            const descMatch = description ? String(description).toLowerCase().includes(query) : false;
            return Boolean(nameMatch || descMatch);
        },

        get visibleItemsCount() {
            if (!this.items || this.items.length === 0) {
                return 1;
            }
            return this.items.filter((item) => this.matchesItem(item.category_id, item.name, item.description)).length;
        },

        clearFilters() {
            this.selectedCategoryId = '';
            this.searchQuery = '';
        },

        isDrinksCategory(categoryId) {
            const category = this.categories.find((c) => String(c.id) === String(categoryId));
            return (category?.slug ?? '') === 'drinks';
        },

        openManage() {
            this.closeAdd();
            this.closeEdit();
            this.closeEditItem();
            this.closeAddItem();
            this.manageOpen = true;
        },

        closeManage() {
            this.manageOpen = false;
        },

        openAdd() {
            this.closeEdit();
            this.closeEditItem();
            this.closeAddItem();
            this.addOpen = true;
        },

        closeAdd() {
            this.addOpen = false;
        },

        openAddItem() {
            this.closeAdd();
            this.closeEdit();
            this.closeEditItem();
            this.addItemOpen = true;
        },

        closeAddItem() {
            this.addItemOpen = false;
        },

        openEdit(category) {
            this.closeAdd();
            this.closeEditItem();
            this.closeAddItem();
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
            this.closeAddItem();
            this.editItem = {
                id: item.id,
                category_id: String(item.category_id ?? ''),
                name: item.name ?? '',
                price: item.price ?? '',
                base_prep_time: item.base_prep_time ?? 0,
                description: item.description ?? '',
                is_available: Boolean(item.is_available),
                is_signature: Boolean(item.is_signature),
                is_signature_original: Boolean(item.is_signature),
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

        /**
         * Blocks checking the "Signature dish" checkbox once the limit is reached.
         */
        guardSignatureToggle(event, wasAlreadySignature) {
            if (!event.target.checked || wasAlreadySignature) {
                return;
            }

            if (this.signatureCount >= this.maxSignatureItems) {
                event.target.checked = false;
                if (this.editItem) {
                    this.editItem.is_signature = false;
                }
                window.toast?.error?.(
                    `You can only select up to ${this.maxSignatureItems} signature dishes. Please unselect one first.`
                );
            }
        },

        /**
         * Star-icon quick toggle in the table; submits the PATCH form unless the limit is hit.
         */
        toggleSignature(event, isCurrentlySignature) {
            if (!isCurrentlySignature && this.signatureCount >= this.maxSignatureItems) {
                window.toast?.error?.(
                    `You can only select up to ${this.maxSignatureItems} signature dishes. Please unselect one first.`
                );
                return;
            }

            event.target.submit();
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
