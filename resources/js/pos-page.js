/**
 * @param {import('alpinejs').Alpine} Alpine
 */
export function registerPosPage(Alpine) {
    Alpine.data('posPage', () => {
        let initial = {};

        try {
            const el = document.getElementById('pos-page-initial');
            if (el?.textContent?.trim()) {
                initial = JSON.parse(el.textContent);
            }
        } catch {
            initial = {};
        }

        return {
        categories: initial?.categories ?? [],
        activeCategoryId: initial?.categories?.[0]?.id ?? null,
        cart: [],
        customerName: 'Walk-in Customer',
        contactNumber: 'N/A',
        orderType: 'dine_in',
        paymentMethod: 'cash',
        notes: '',
        storeUrl: initial?.storeUrl ?? '',
        ordersUrl: initial?.ordersUrl ?? '',
        submitting: false,
        checkoutOpen: false,
        checkoutStep: 'overview',

        get activeCategory() {
            return this.categories.find((category) => category.id === this.activeCategoryId) ?? null;
        },

        get subtotal() {
            return this.cart.reduce((sum, line) => sum + line.price * line.quantity, 0);
        },

        get total() {
            return this.subtotal;
        },

        get cartCount() {
            return this.cart.reduce((sum, line) => sum + line.quantity, 0);
        },

        formatMoney(amount) {
            return `₱${Number(amount).toFixed(2)}`;
        },

        addItem(item) {
            const existing = this.cart.find((line) => line.menu_item_id === item.id);
            if (existing) {
                existing.quantity += 1;
                return;
            }

            this.cart.push({
                menu_item_id: item.id,
                name: item.name,
                price: Number(item.price),
                price_label: item.price_label,
                image: item.image,
                quantity: 1,
                instructions: '',
            });
        },

        increment(line) {
            if (line.quantity < 99) {
                line.quantity += 1;
            }
        },

        decrement(line) {
            if (line.quantity > 1) {
                line.quantity -= 1;
            } else {
                this.remove(line);
            }
        },

        remove(line) {
            this.cart = this.cart.filter((entry) => entry.menu_item_id !== line.menu_item_id);
        },

        clearCart() {
            this.cart = [];
            this.closeCheckout();
        },

        openCheckout() {
            if (this.cart.length === 0) {
                window.toast?.warning?.('Add at least one item to the cart.');
                return;
            }

            this.checkoutStep = 'overview';
            this.checkoutOpen = true;
        },

        closeCheckout() {
            this.checkoutOpen = false;
            this.checkoutStep = 'overview';
        },

        goToConfirm() {
            if (this.cart.length === 0) {
                window.toast?.warning?.('Add at least one item to the cart.');
                return;
            }

            this.checkoutStep = 'confirm';
        },

        backToOverview() {
            this.checkoutStep = 'overview';
        },

        orderTypeLabel() {
            return this.orderType === 'dine_in' ? 'Dine-in' : 'Takeaway';
        },

        paymentMethodLabel() {
            const labels = { cash: 'Cash', gcash: 'GCash', maya: 'Maya' };
            return labels[this.paymentMethod] ?? this.paymentMethod;
        },

        onOrderTypeChange() {
            //
        },

        async completeSale() {
            if (this.cart.length === 0) {
                window.toast?.warning?.('Add at least one item to the cart.');
                return;
            }

            this.submitting = true;

            try {
                const response = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    body: JSON.stringify({
                        customer_name: this.customerName,
                        contact_number: this.contactNumber,
                        order_type: this.orderType,
                        payment_method: this.paymentMethod,
                        special_instructions: this.notes,
                        items: this.cart.map((line) => ({
                            menu_item_id: line.menu_item_id,
                            quantity: line.quantity,
                            instructions: line.instructions,
                        })),
                    }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.message
                        ?? Object.values(data.errors ?? {}).flat()?.[0]
                        ?? 'Could not complete the sale.';
                    window.toast?.error?.(message);
                    return;
                }

                window.toast?.success?.(data.message ?? 'Order created.');
                this.clearCart();
                this.notes = '';
                this.closeCheckout();
            } catch {
                window.toast?.error?.('Could not complete the sale. Please try again.');
            } finally {
                this.submitting = false;
            }
        },
    };
    });
}
