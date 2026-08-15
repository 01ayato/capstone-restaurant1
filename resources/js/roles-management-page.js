/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerRolesManagementPage(Alpine) {
    Alpine.data('rolesManagementPage', (initial) => ({
        editOpen: Boolean(initial?.editOpen),
        editRole: initial?.editRole ?? null,
        addOpen: Boolean(initial?.addOpen),
        addRole: initial?.addRole ?? null,
        updateBase: initial?.updateBase ?? '',

        emptyAddRole() {
            return {
                slug: '',
                name: '',
            };
        },

        handleActionClick(event) {
            const editBtn = event.target.closest('[data-roles-management-edit]');
            if (!editBtn) {
                return;
            }

            const payload = editBtn.getAttribute('data-role-payload');
            if (!payload) {
                return;
            }

            try {
                this.openEdit(JSON.parse(payload));
            } catch {
                //
            }
        },

        openEdit(r) {
                this.closeAdd();
                this.editRole = {
                    id: r.id,
                    slug: r.slug ?? '',
                    name: r.name ?? '',
                    slug_readonly: Boolean(r.slug_readonly),
                };
                this.editOpen = true;
            },

            closeEdit() {
                this.editOpen = false;
                this.editRole = null;
            },

            editFormAction() {
                if (!this.editRole?.id || !this.updateBase) {
                    return '#';
                }
                return `${this.updateBase}/${this.editRole.id}`;
            },

            openAdd() {
                this.closeEdit();
                this.addRole = this.emptyAddRole();
                this.addOpen = true;
            },

            closeAdd() {
                this.addOpen = false;
                this.addRole = null;
            },
        }));
}
