/**
 * Modal icon grid for Font Awesome 6 (solid). Values stored as full class strings, e.g. "fa-solid fa-house".
 */

function lockBodyScroll(lock) {
    document.documentElement.classList.toggle('overflow-hidden', lock);
    document.body.classList.toggle('overflow-hidden', lock);
}

/** @type {readonly string[]} Icon name suffixes (fa-solid fa-{name}) */
const FA_SOLID_NAMES = [
    'house',
    'user',
    'users',
    'user-group',
    'user-gear',
    'gear',
    'sliders',
    'file',
    'file-lines',
    'folder',
    'folder-open',
    'calendar',
    'calendar-days',
    'clock',
    'bell',
    'envelope',
    'paper-plane',
    'chart-line',
    'chart-column',
    'chart-pie',
    'wallet',
    'money-bill',
    'credit-card',
    'list',
    'table',
    'table-cells',
    'shield-halved',
    'lock',
    'unlock',
    'key',
    'magnifying-glass',
    'pen',
    'pen-to-square',
    'trash-can',
    'plus',
    'minus',
    'xmark',
    'check',
    'arrow-left',
    'arrow-right',
    'arrow-up',
    'arrow-down',
    'link',
    'image',
    'camera',
    'phone',
    'mobile',
    'building',
    'briefcase',
    'id-card',
    'clipboard',
    'clipboard-list',
    'book',
    'book-open',
    'truck',
    'cart-shopping',
    'store',
    'tag',
    'tags',
    'star',
    'heart',
    'circle-info',
    'triangle-exclamation',
    'circle-question',
    'bars',
    'database',
    'server',
    'cloud',
    'download',
    'upload',
    'filter',
    'globe',
    'print',
    'wrench',
    'screwdriver-wrench',
    'gavel',
    'scale-balanced',
    'percent',
    'comments',
    'comment',
    'reply',
    'share-from-square',
    'right-to-bracket',
    'right-from-bracket',
    'language',
    'moon',
    'sun',
];

function toFaClass(name) {
    return `fa-solid fa-${name}`;
}

/**
 * @param {HTMLElement} el
 * @param {string} classStr
 */
export function updateFaIconPreview(el, classStr) {
    if (!el) {
        return;
    }
    el.textContent = '';
    const v = (classStr || '').trim();
    if (!v) {
        const s = document.createElement('span');
        s.className = 'text-xs text-gray-400';
        s.textContent = '—';
        el.appendChild(s);
        return;
    }
    const i = document.createElement('i');
    i.setAttribute('aria-hidden', 'true');
    for (const c of v.split(/\s+/)) {
        if (/^fa[a-z0-9_-]+$/i.test(c)) {
            i.classList.add(c);
        }
    }
    el.appendChild(i);
}

function defaultI18n() {
    return {
        title: 'Choose icon',
        search: 'Search icons',
        clear: 'Clear icon',
        cancel: 'Cancel',
        apply: 'Use icon',
        current: 'Current',
        notInList: 'Not in list — pick one below or clear.',
    };
}

function i18n() {
    const w = typeof window !== 'undefined' ? window.__faIconPickerI18n : null;
    return { ...defaultI18n(), ...(w && typeof w === 'object' ? w : {}) };
}

/**
 * @param {{ initial?: string }} opts
 * @returns {Promise<{ confirmed: boolean; iconClass: string }>}
 */
export function openFaIconPicker(opts = {}) {
    const { initial = '' } = opts;
    const t = i18n();

    let selected = (initial || '').trim();
    let settled = false;

    return new Promise((resolve) => {
        const finish = (result) => {
            if (settled) {
                return;
            }
            settled = true;
            document.removeEventListener('keydown', onKeydown, true);
            lockBodyScroll(false);
            if (root.parentNode) {
                root.remove();
            }
            resolve(result);
        };

        const root = document.createElement('div');
        root.id = 'fa-icon-picker-root';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.tabIndex = -1;
        root.className =
            'fixed inset-0 z-[10001] flex items-center justify-center bg-[#0D2232]/55 backdrop-blur-[2px] p-4 sm:p-6';

        const panel = document.createElement('div');
        panel.className =
            'relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl';

        const header = document.createElement('div');
        header.className = 'shrink-0 border-b border-gray-100 px-5 py-4';
        const h = document.createElement('h2');
        h.className = 'text-lg font-semibold text-gray-900';
        h.textContent = t.title;
        header.appendChild(h);

        const currentWrap = document.createElement('div');
        currentWrap.className = 'mt-3 flex flex-wrap items-center gap-2';
        const currentLabel = document.createElement('span');
        currentLabel.className = 'text-xs font-medium uppercase tracking-wide text-gray-500';
        currentLabel.textContent = t.current;
        const currentPreview = document.createElement('span');
        currentPreview.className =
            'inline-flex min-h-9 min-w-9 items-center justify-center rounded-lg border border-gray-200 bg-slate-50 px-2 text-lg text-gray-700';
        const currentMono = document.createElement('code');
        currentMono.className = 'ml-1 max-w-[12rem] truncate text-xs text-gray-600';
        currentWrap.appendChild(currentLabel);
        currentWrap.appendChild(currentPreview);
        currentWrap.appendChild(currentMono);
        header.appendChild(currentWrap);

        const searchRow = document.createElement('div');
        searchRow.className = 'shrink-0 border-b border-gray-100 px-5 py-3';
        const search = document.createElement('input');
        search.type = 'search';
        search.className =
            'block w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 outline-none focus:border-[#204865]';
        search.placeholder = t.search;
        search.setAttribute('autocomplete', 'off');
        searchRow.appendChild(search);

        const gridWrap = document.createElement('div');
        gridWrap.className = 'min-h-0 flex-1 overflow-y-auto px-5 py-4';

        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-6 gap-2 sm:grid-cols-8';
        grid.setAttribute('role', 'listbox');
        grid.setAttribute('aria-label', t.title);

        const hint = document.createElement('p');
        hint.className = 'mt-2 hidden text-xs text-amber-700';
        hint.textContent = t.notInList;

        const footer = document.createElement('div');
        footer.className =
            'flex shrink-0 flex-wrap items-center justify-end gap-2 border-t border-gray-100 bg-slate-50/80 px-5 py-4';

        const btnClear = document.createElement('button');
        btnClear.type = 'button';
        btnClear.className =
            'mr-auto inline-flex min-h-9 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-slate-50';
        btnClear.textContent = t.clear;

        const btnCancel = document.createElement('button');
        btnCancel.type = 'button';
        btnCancel.className =
            'inline-flex min-h-9 min-w-[5rem] items-center justify-center rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-slate-50';
        btnCancel.textContent = t.cancel;

        const btnUse = document.createElement('button');
        btnUse.type = 'button';
        btnUse.className =
            'inline-flex min-h-9 min-w-[5rem] items-center justify-center rounded-lg bg-[#0D2232] px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-[#16364D]';
        btnUse.textContent = t.apply;

        footer.appendChild(btnClear);
        footer.appendChild(btnCancel);
        footer.appendChild(btnUse);

        panel.appendChild(header);
        panel.appendChild(searchRow);
        gridWrap.appendChild(grid);
        gridWrap.appendChild(hint);
        panel.appendChild(gridWrap);
        panel.appendChild(footer);
        root.appendChild(panel);

        const curatedSet = new Set(FA_SOLID_NAMES.map(toFaClass));

        function syncCurrentPreview() {
            updateFaIconPreview(currentPreview, selected);
            currentMono.textContent = selected || '—';
            const unknown = selected && !curatedSet.has(selected);
            hint.classList.toggle('hidden', !unknown);
        }

        function renderGrid(filter) {
            grid.textContent = '';
            const q = (filter || '').trim().toLowerCase();
            const names = FA_SOLID_NAMES.filter((n) => n.toLowerCase().includes(q));
            for (const name of names) {
                const full = toFaClass(name);
                const b = document.createElement('button');
                b.type = 'button';
                b.className =
                    'flex aspect-square items-center justify-center rounded-lg border border-gray-200 text-lg text-gray-700 transition hover:border-[#204865] hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#204865]';
                b.setAttribute('aria-label', full);
                b.setAttribute('role', 'option');
                const icon = document.createElement('i');
                icon.setAttribute('aria-hidden', 'true');
                icon.className = full;
                b.appendChild(icon);
                b.addEventListener('click', () => {
                    selected = full;
                    syncCurrentPreview();
                    for (const c of grid.querySelectorAll('button')) {
                        c.classList.remove('border-[#0D2232]', 'bg-slate-100', 'ring-2', 'ring-[#0D2232]');
                        c.classList.add('border-gray-200');
                    }
                    b.classList.remove('border-gray-200');
                    b.classList.add('border-[#0D2232]', 'bg-slate-100', 'ring-2', 'ring-[#0D2232]');
                });
                if (selected === full) {
                    b.classList.add('border-[#0D2232]', 'bg-slate-100', 'ring-2', 'ring-[#0D2232]');
                    b.classList.remove('border-gray-200');
                }
                grid.appendChild(b);
            }
        }

        search.addEventListener('input', () => renderGrid(search.value));

        btnClear.addEventListener('click', () => {
            selected = '';
            syncCurrentPreview();
            renderGrid(search.value);
        });

        btnCancel.addEventListener('click', () => {
            finish({ confirmed: false, iconClass: (initial || '').trim() });
        });

        btnUse.addEventListener('click', () => {
            finish({ confirmed: true, iconClass: selected });
        });

        const onKeydown = (e) => {
            if (e.key === 'Escape') {
                e.preventDefault();
                finish({ confirmed: false, iconClass: (initial || '').trim() });
            }
        };

        root.addEventListener('click', (e) => {
            if (e.target === root) {
                finish({ confirmed: false, iconClass: (initial || '').trim() });
            }
        });

        document.addEventListener('keydown', onKeydown, true);
        document.body.appendChild(root);
        lockBodyScroll(true);
        renderGrid('');
        syncCurrentPreview();

        requestAnimationFrame(() => {
            search.focus();
        });
    });
}

/**
 * @param {string} hiddenInputId
 * @param {string} previewContainerId
 */
export async function chooseIconForField(hiddenInputId, previewContainerId) {
    const hidden = document.getElementById(hiddenInputId);
    const preview = document.getElementById(previewContainerId);
    if (!hidden || !preview) {
        return;
    }
    const r = await openFaIconPicker({ initial: hidden.value || '' });
    if (!r.confirmed) {
        return;
    }
    hidden.value = r.iconClass;
    updateFaIconPreview(preview, r.iconClass);
}

window.openFaIconPicker = openFaIconPicker;
window.updateFaIconPreview = updateFaIconPreview;
window.chooseIconForField = chooseIconForField;
