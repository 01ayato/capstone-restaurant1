export function registerPortalPaymentOcr(Alpine) {
    Alpine.data('portalPaymentOcr', (scanUrl, csrfToken, orderTotal) => ({
        paymentMethod: 'cash',
        scanning: false,
        scanError: null,
        scanMessage: null,
        referenceNumber: null,
        amountPaidLabel: null,
        orderTotal: Number(orderTotal) || 0,

        init() {
            const initial = this.$el.dataset.initialPaymentMethod;
            if (initial) {
                this.paymentMethod = initial;
            }
        },

        async onProofSelected(event) {
            const file = event.target.files?.[0];
            this.scanError = null;
            this.scanMessage = null;
            this.referenceNumber = null;
            this.amountPaidLabel = null;

            if (! file) {
                return;
            }

            if (this.paymentMethod !== 'gcash' && this.paymentMethod !== 'maya') {
                return;
            }

            this.scanning = true;

            try {
                const body = new FormData();
                body.append('payment_proof', file);
                body.append('payment_method', this.paymentMethod);
                body.append('_token', csrfToken);

                const response = await fetch(scanUrl, {
                    method: 'POST',
                    body,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();

                if (! response.ok) {
                    this.scanError = payload.message || 'Could not read the payment screenshot.';
                    return;
                }

                this.referenceNumber = payload.reference_number || null;
                this.amountPaidLabel = payload.amount_paid_label || null;
                this.scanMessage = payload.message || null;
            } catch {
                this.scanError = 'Could not scan the image. Check your connection and try again.';
            } finally {
                this.scanning = false;
            }
        },

        amountMismatch() {
            if (! this.amountPaidLabel || ! this.orderTotal) {
                return false;
            }

            const match = this.amountPaidLabel.replace(/[^\d.]/g, '');
            const paid = parseFloat(match);

            if (Number.isNaN(paid)) {
                return false;
            }

            return Math.abs(paid - this.orderTotal) > 0.02;
        },
    }));
}
