/**
 * Role-focused module/page visibility permissions.
 *
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerPermissionsPage(Alpine) {
    Alpine.data('permissionsPage', (initial = {}) => ({
        selectedRoleId: Number(initial?.selectedRoleId || 0),
        matrix: structuredClone(initial?.matrix ?? {}),
        roles: initial?.roles ?? [],
        modules: initial?.modules ?? [],
        superadminSlug: initial?.superadminSlug ?? 'superadmin',

        get selectedRole() {
            return this.roles.find((role) => Number(role.id) === Number(this.selectedRoleId)) ?? null;
        },

        get isSuperadminSelected() {
            return Boolean(this.selectedRole?.is_superadmin);
        },

        get current() {
            const key = String(this.selectedRoleId);
            if (!this.matrix[key] && !this.matrix[this.selectedRoleId]) {
                this.matrix[key] = { modules: [], pages: [] };
            }
            return this.matrix[key] ?? this.matrix[this.selectedRoleId];
        },

        selectRole(roleId) {
            this.selectedRoleId = Number(roleId);
            const url = new URL(window.location.href);
            url.searchParams.set('role', String(this.selectedRoleId));
            window.history.replaceState({}, '', url);
        },

        hasModule(moduleId) {
            if (this.isSuperadminSelected) {
                return true;
            }
            return this.current.modules.map(Number).includes(Number(moduleId));
        },

        hasPage(pageId) {
            if (this.isSuperadminSelected) {
                return true;
            }
            return this.current.pages.map(Number).includes(Number(pageId));
        },

        toggleModule(moduleId, checked) {
            if (this.isSuperadminSelected) {
                return;
            }

            const id = Number(moduleId);
            const modules = new Set(this.current.modules.map(Number));
            const pages = new Set(this.current.pages.map(Number));
            const module = this.modules.find((item) => Number(item.id) === id);

            if (checked) {
                modules.add(id);
            } else {
                modules.delete(id);
                if (module?.pages) {
                    for (const page of module.pages) {
                        pages.delete(Number(page.id));
                    }
                }
            }

            this.current.modules = Array.from(modules);
            this.current.pages = Array.from(pages);
        },

        togglePage(moduleId, pageId, checked) {
            if (this.isSuperadminSelected) {
                return;
            }

            const mid = Number(moduleId);
            const pid = Number(pageId);
            const modules = new Set(this.current.modules.map(Number));
            const pages = new Set(this.current.pages.map(Number));

            if (checked) {
                pages.add(pid);
                modules.add(mid);
            } else {
                pages.delete(pid);
            }

            this.current.modules = Array.from(modules);
            this.current.pages = Array.from(pages);
        },

        selectAllForRole() {
            if (this.isSuperadminSelected) {
                return;
            }
            this.current.modules = this.modules.map((module) => Number(module.id));
            this.current.pages = this.modules.flatMap((module) =>
                (module.pages ?? []).map((page) => Number(page.id)),
            );
        },

        clearAllForRole() {
            if (this.isSuperadminSelected) {
                return;
            }
            this.current.modules = [];
            this.current.pages = [];
        },
    }));
}
