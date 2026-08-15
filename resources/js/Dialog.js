/**
 * @typedef {Object} SysDialogResult
 * @property {boolean} isConfirmed
 * @property {boolean} isDismissed
 * @property {*} [value]
 */

/**
 * @typedef {Object} SysDialogOptions
 * @property {string} [title]
 * @property {string} [text] Plain-text body (ignored if `html` is set).
 * @property {string} [html] HTML body; only use with trusted strings.
 * @property {string} [cancelButtonText]
 * @property {string} [confirmButtonText]
 * @property {boolean} [allowOutsideClick=true]
 * @property {boolean} [allowEscapeKey=true]
 * @property {string} [width] CSS value for panel max-width, e.g. `28rem` or `32rem`.
 * @property {boolean} [focusCancel=false] When both buttons exist, focus Cancel instead of Save.
 */

/** @type {null | { supersede: () => void }} */
let activeSession = null;

function lockBodyScroll(lock) {
    document.documentElement.classList.toggle('overflow-hidden', lock);
    document.body.classList.toggle('overflow-hidden', lock);
}

/**
 * @param {SysDialogOptions & { showSave: boolean }} opts
 * @returns {Promise<SysDialogResult>}
 */
function openSysDialog(opts) {
    const prev = activeSession;
    if (prev) {
        prev.supersede();
    }

    const {
        title = '',
        text = '',
        html = '',
        cancelButtonText = 'Cancel',
        confirmButtonText = 'Save',
        allowOutsideClick = true,
        allowEscapeKey = true,
        width = '28rem',
        focusCancel = false,
        showSave,
    } = opts;

    return new Promise((resolve) => {
        const session = {
            supersede() {
                finish({ isConfirmed: false, isDismissed: true, value: undefined });
            },
        };

        const root = document.createElement('div');
        root.id = 'sys-dialog-root';
        root.setAttribute('role', 'dialog');
        root.setAttribute('aria-modal', 'true');
        root.tabIndex = -1;
        root.className =
            'fixed inset-0 z-[10000] flex items-center justify-center p-4 sm:p-6';

        const backdrop = document.createElement('button');
        backdrop.type = 'button';
        backdrop.className =
            'absolute inset-0 cursor-default bg-[#0D2232]/55 backdrop-blur-[2px] transition-opacity';
        backdrop.setAttribute('aria-label', 'Close dialog');

        const panel = document.createElement('div');
        panel.className =
            'relative w-full max-h-[85vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-[0_20px_50px_rgba(15,23,42,.18)]';
        panel.style.maxWidth = width;

        const titleId = 'sys-dialog-title';
        if (title) {
            const h = document.createElement('h2');
            h.id = titleId;
            h.className = 'text-lg font-bold tracking-tight text-gray-900';
            h.textContent = title;
            panel.appendChild(h);
            root.setAttribute('aria-labelledby', titleId);
        }

        if (html) {
            const body = document.createElement('div');
            body.className = title ? 'mt-3 text-sm leading-6 text-gray-600' : 'text-sm leading-6 text-gray-600';
            body.innerHTML = html;
            panel.appendChild(body);
        } else if (text) {
            const p = document.createElement('p');
            p.className = title ? 'mt-3 text-sm leading-6 text-gray-600' : 'text-sm leading-6 text-gray-600';
            p.textContent = text;
            panel.appendChild(p);
        }

        const footer = document.createElement('div');
        footer.className = 'mt-6 flex flex-wrap items-center justify-end gap-2';

        const btnCancel = document.createElement('button');
        btnCancel.type = 'button';
        btnCancel.className =
            'inline-flex min-h-10 min-w-[5rem] items-center justify-center rounded-[10px] border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm transition duration-150 hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#204865] focus-visible:ring-offset-2';
        btnCancel.textContent = cancelButtonText;

        const btnSave = document.createElement('button');
        btnSave.type = 'button';
        btnSave.className =
            'inline-flex min-h-10 min-w-[5rem] items-center justify-center rounded-[10px] bg-[#0D2232] px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-150 hover:bg-[#16364D] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#204865] focus-visible:ring-offset-2';
        btnSave.textContent = confirmButtonText;

        if (showSave) {
            footer.appendChild(btnCancel);
            footer.appendChild(btnSave);
        } else {
            footer.appendChild(btnCancel);
        }

        panel.appendChild(footer);
        root.appendChild(backdrop);
        root.appendChild(panel);
        document.body.appendChild(root);
        lockBodyScroll(true);

        const previousFocus = document.activeElement;

        let settled = false;
        const finish = (result) => {
            if (settled) {
                return;
            }
            settled = true;
            if (activeSession === session) {
                activeSession = null;
            }
            teardown();
            resolve(result);
        };

        const onBackdrop = (e) => {
            if (e.target === backdrop && allowOutsideClick) {
                finish({ isConfirmed: false, isDismissed: true, value: undefined });
            }
        };

        const onKeydown = (e) => {
            if (e.key === 'Escape' && allowEscapeKey) {
                e.preventDefault();
                finish({ isConfirmed: false, isDismissed: true, value: undefined });
            }
        };

        const teardown = () => {
            backdrop.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKeydown, true);
            btnCancel.removeEventListener('click', onCancel);
            if (showSave) {
                btnSave.removeEventListener('click', onSave);
            }
            root.remove();
            lockBodyScroll(false);
            if (previousFocus && typeof previousFocus.focus === 'function') {
                previousFocus.focus({ preventScroll: true });
            }
        };

        const onCancel = () => {
            finish({ isConfirmed: false, isDismissed: true, value: undefined });
        };

        const onSave = () => {
            finish({ isConfirmed: true, isDismissed: false, value: true });
        };

        btnCancel.addEventListener('click', onCancel);
        if (showSave) {
            btnSave.addEventListener('click', onSave);
        }

        backdrop.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKeydown, true);

        activeSession = session;

        requestAnimationFrame(() => {
            if (showSave && !focusCancel) {
                btnSave.focus();
            } else {
                btnCancel.focus();
            }
        });
    });
}

/**
 * Modal with a single Cancel button (no Save).
 * @param {SysDialogOptions} [options]
 * @returns {Promise<SysDialogResult>}
 */
export function sys_dialog(options = {}) {
    return openSysDialog({ ...options, showSave: false });
}

/**
 * Modal with Save and Cancel.
 * @param {SysDialogOptions} [options]
 * @returns {Promise<SysDialogResult>}
 */
export function sys_dialog2(options = {}) {
    return openSysDialog({ ...options, showSave: true });
}

window.sys_dialog = sys_dialog;
window.sys_dialog2 = sys_dialog2;
