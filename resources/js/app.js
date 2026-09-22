import './bootstrap';
import Alpine from 'alpinejs';
import { registerUserAccountsPage } from './user-accounts-page';
import { registerRolesManagementPage } from './roles-management-page';
import { registerPermissionsPage } from './permissions-page';
import { registerMenuItemsPage } from './menu-items-page';
import { registerPosPage } from './pos-page';
import { registerTablesConfigurationsPage } from './tables-configurations-page';
import { registerAdminReservationsPage } from './admin-reservations-page';
import { registerPortalTableSelect } from './portal-table-select';
import { registerPortalReservationsPage } from './portal-reservations-page';
import { registerPortalMyReservationsPage } from './portal-my-reservations-page';
import { registerPortalPaymentOcr } from './portal-payment-ocr';
import { registerPortalMenu } from './portal-menu';
import { registerPortalCartPage } from './portal-cart-page';

function registerAlpineComponents() {
    registerUserAccountsPage(Alpine);
    registerRolesManagementPage(Alpine);
    registerPermissionsPage(Alpine);
    registerMenuItemsPage(Alpine);
    registerPosPage(Alpine);
    registerTablesConfigurationsPage(Alpine);
    registerAdminReservationsPage(Alpine);
    registerPortalTableSelect(Alpine);
    registerPortalReservationsPage(Alpine);
    registerPortalMyReservationsPage(Alpine);
    registerPortalPaymentOcr(Alpine);
    registerPortalMenu(Alpine);
    registerPortalCartPage(Alpine);
}

registerAlpineComponents();
document.addEventListener('alpine:init', registerAlpineComponents);

import './datatables';
import './notify';
import './Dialog';
import './fa-icon-picker';
import './portal-theme';

window.Alpine = Alpine;
Alpine.start();
