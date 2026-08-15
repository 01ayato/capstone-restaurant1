/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerUserAccountsPage(Alpine) {
    Alpine.data('userAccountsPage', (initial) => ({
        editOpen: Boolean(initial?.editOpen),
        editUser: initial?.editUser ?? null,
        addOpen: Boolean(initial?.addOpen),
        addUser: initial?.addUser ?? null,
        updateBase: initial?.updateBase ?? '',

        emptyAddUser() {
            return {
                first_name: '',
                middle_name: '',
                last_name: '',
                suffix: '',
                email: '',
                phone: '',
                date_of_birth: '',
                gender: '',
                role_id: '',
                password: '',
                password_confirmation: '',
            };
        },

        handleActionClick(event) {
            const editBtn = event.target.closest('[data-user-accounts-edit]');
            if (!editBtn) {
                return;
            }

            const payload = editBtn.getAttribute('data-user-payload');
            if (!payload) {
                return;
            }

            try {
                this.openEdit(JSON.parse(payload));
            } catch {
                //
            }
        },

        openEdit(u) {
                this.closeAdd();
                this.editUser = {
                    id: u.id,
                    first_name: u.first_name ?? '',
                    middle_name: u.middle_name ?? '',
                    last_name: u.last_name ?? '',
                    suffix: u.suffix ?? '',
                    email: u.email ?? '',
                    phone: u.phone ?? '',
                    date_of_birth: u.date_of_birth ?? '',
                    gender: u.gender ?? '',
                    role_id: String(u.role_id ?? ''),
                    password: '',
                    password_confirmation: '',
                };
                this.editOpen = true;
            },

            closeEdit() {
                this.editOpen = false;
                this.editUser = null;
            },

            editFormAction() {
                if (!this.editUser?.id || !this.updateBase) {
                    return '#';
                }
                return `${this.updateBase}/${this.editUser.id}`;
            },

            openAdd() {
                this.closeEdit();
                this.addUser = this.emptyAddUser();
                this.addOpen = true;
            },

            closeAdd() {
                this.addOpen = false;
                this.addUser = null;
            },
        }));
}
