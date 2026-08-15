import jQuery from 'jquery';
import DataTable from 'datatables.net-dt';

import '../css/datatables.css';

window.jQuery = window.$ = jQuery;
window.DataTable = DataTable;

/**
 * Initialize DataTables on a table (selector string or element).
 * If the table is already a DataTable, it is destroyed first, then re-initialized.
 *
 * @param {string | HTMLElement} target CSS selector or `<table>` element.
 * @param {Record<string, unknown>} [options] DataTables constructor options.
 * @returns {import('datatables.net').Api | null} API instance, or null if no element matched.
 */
export function initDataTable(target, options = {}) {
    const el = typeof target === 'string' ? document.querySelector(target) : target;
    if (!el) {
        return null;
    }
    if (DataTable.isDataTable(el)) {
        new DataTable.Api(el).destroy();
    }
    return new DataTable(el, options);
}

window.initDataTable = initDataTable;
