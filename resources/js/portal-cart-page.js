/**
 * Formats a number the same way App\Support\Peso::format() does on the backend.
 */
function formatPeso(amount) {
    const value = Number(amount ?? 0);

    return '₱' + value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerPortalCartPage(Alpine) {
    let initial = {};

    try {
        const el = document.getElementById('portal-cart-initial');
        if (el?.textContent?.trim()) {
            initial = JSON.parse(el.textContent);
        }
    } catch {
        initial = {};
    }

    Alpine.store('cart', {
        subtotal: Number(initial?.subtotal ?? 0),
        deliveryFee: Number(initial?.deliveryFee ?? 0),
        total: Number(initial?.total ?? 0),

        formattedSubtotal() {
            return formatPeso(this.subtotal);
        },
        formattedDeliveryFee() {
            return formatPeso(this.deliveryFee);
        },
        formattedTotal() {
            return formatPeso(this.total);
        },

        applyTotals(payload) {
            this.subtotal = Number(payload.subtotal);
            this.deliveryFee = Number(payload.delivery_fee);
            this.total = Number(payload.total);
        },
    });

    Alpine.data('cartLineItem', (item) => ({
        quantity: item.quantity,
        instructions: item.instructions ?? '',
        lineTotal: item.line_total,
        removed: false,
        loading: false,
        updateUrl: item.updateUrl,

        formattedLineTotal() {
            return formatPeso(this.lineTotal);
        },

        async updateQuantity(rawValue) {
            const quantity = Math.max(0, Math.min(99, parseInt(rawValue, 10) || 0));
            this.quantity = quantity;
            await this.submit();
        },

        async updateInstructions(value) {
            this.instructions = value;
            await this.submit();
        },

        async submit() {
            this.loading = true;

            try {
                const response = await fetch(this.updateUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        menu_item_id: item.id,
                        quantity: this.quantity,
                        instructions: this.instructions,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }

                const data = await response.json();

                this.lineTotal = data.line_total;
                this.removed = data.removed;
                Alpine.store('cart').applyTotals(data);

                if (data.removed) {
                    window.toast?.success?.('Item removed from cart.');
                }
            } catch {
                window.toast?.error?.('Could not update the cart. Please try again.');
            } finally {
                this.loading = false;
            }
        },
    }));
}
