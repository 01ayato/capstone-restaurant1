import Swal from 'sweetalert2';
import { Notyf } from 'notyf';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'notyf/notyf.min.css';

const notyf = new Notyf({
    duration: 4500,
    ripple: true,
    dismissible: true,
    position: { x: 'right', y: 'top' },
    types: [
        {
            type: 'success',
            background: '#22c55e',
            icon: false,
        },
        {
            type: 'error',
            background: '#dc2626',
            icon: false,
        },
        {
            type: 'warning',
            background: '#f59e0b',
            icon: false,
        },
        {
            type: 'info',
            background: '#2563eb',
            icon: false,
        },
    ],
});

/**
 * Toast API (Notyf — dependency-free; similar role to Toastr).
 */
export const toast = {
    success(message) {
        notyf.success(message);
    },
    error(message) {
        notyf.error(message);
    },
    warning(message) {
        notyf.open({ type: 'warning', message });
    },
    info(message) {
        notyf.open({ type: 'info', message });
    },
};

/**
 * Read session flash from <script type="application/json" id="flash-data"> and show toasts.
 */
export function initFlashMessages() {
    const el = document.getElementById('flash-data');
    if (!el?.textContent?.trim()) {
        return;
    }

    let data = {};
    try {
        data = JSON.parse(el.textContent);
    } catch {
        return;
    }

    el.remove();

    if (data.success) {
        toast.success(data.success);
    }
    if (data.error) {
        toast.error(data.error);
    }
    if (data.warning) {
        toast.warning(data.warning);
    }
    if (data.info) {
        toast.info(data.info);
    }

    if (typeof data.print_slip_url === 'string' && data.print_slip_url.trim() !== '') {
        window.open(data.print_slip_url, '_blank', 'noopener');
    }
}

/**
 * SweetAlert2 confirm dialog. Resolves true if user confirms.
 */
export function confirmDialog({
    title = 'Are you sure?',
    text = '',
    confirmButtonText = 'Yes',
    cancelButtonText = 'Cancel',
    icon = 'warning',
} = {}) {
    const isDark = document.documentElement.classList.contains('dark');

    return Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonColor: isDark ? '#f97316' : '#0d2232',
        cancelButtonColor: isDark ? '#3f4f5f' : '#6b7280',
        confirmButtonText,
        cancelButtonText,
        reverseButtons: true,
        focusCancel: true,
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-[10px]',
            cancelButton: 'rounded-[10px]',
        },
    }).then((result) => result.isConfirmed === true);
}

document.addEventListener('DOMContentLoaded', () => {
    initFlashMessages();
});

window.Swal = Swal;
window.toast = toast;
window.confirmDialog = confirmDialog;
