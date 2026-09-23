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
        amountTendered: '',
        discountPresets: [
            { label: 'None', rate: 0 },
            { label: '10%', rate: 0.10 },
            { label: 'Senior/PWD 20%', rate: 0.20 },
        ],
        selectedDiscountRate: 0,
        selectedDiscountLabel: 'None',
        storeUrl: initial?.storeUrl ?? '',
        ordersUrl: initial?.ordersUrl ?? '',
        submitting: false,
        checkoutOpen: false,
        checkoutStep: 'overview',

        setDiscount(rate, label) {
            this.selectedDiscountRate = rate;
            this.selectedDiscountLabel = label;
        },

        setTendered(val) {
            this.amountTendered = Number(val).toFixed(2);
        },

        setExactTendered() {
            this.amountTendered = Number(this.total).toFixed(2);
        },

        get activeCategory() {
            return this.categories.find((category) => category.id === this.activeCategoryId) ?? null;
        },

        get subtotal() {
            return this.cart.reduce((sum, line) => sum + line.price * line.quantity, 0);
        },

        get discountAmount() {
            if (!this.selectedDiscountRate || this.selectedDiscountRate <= 0) {
                return 0;
            }
            return Math.round(this.subtotal * this.selectedDiscountRate * 100) / 100;
        },

        get total() {
            return Math.max(0, this.subtotal - this.discountAmount);
        },

        get cartCount() {
            return this.cart.reduce((sum, line) => sum + line.quantity, 0);
        },

        get numericAmountTendered() {
            return parseFloat(this.amountTendered) || 0;
        },

        get isCashPayment() {
            return this.paymentMethod === 'cash';
        },

        get isTenderedSufficient() {
            if (!this.isCashPayment) {
                return true;
            }
            return this.numericAmountTendered >= this.total;
        },

        get change() {
            if (!this.amountTendered || this.numericAmountTendered === 0) {
                return 0;
            }
            return Math.round((this.numericAmountTendered - this.total) * 100) / 100;
        },

        get shortage() {
            if (!this.amountTendered || this.isTenderedSufficient) {
                return 0;
            }
            return Math.round((this.total - this.numericAmountTendered) * 100) / 100;
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
            this.selectedDiscountRate = 0;
            this.selectedDiscountLabel = 'None';
            this.amountTendered = '';
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

            if (this.isCashPayment && !this.isTenderedSufficient) {
                window.toast?.warning?.('Amount tendered is less than the total amount.');
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
            const labels = { cash: 'Cash', gcash: 'GCash' };
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

            if (this.isCashPayment && !this.isTenderedSufficient) {
                window.toast?.warning?.('Amount tendered is less than the total amount.');
                return;
            }

            this.submitting = true;

            const instructionsSummary = [
                this.notes.trim(),
                this.selectedDiscountRate > 0 ? `[Discount: ${this.selectedDiscountLabel} -${this.formatMoney(this.discountAmount)}]` : '',
                this.isCashPayment && this.numericAmountTendered > 0 ? `[Cash: ${this.formatMoney(this.numericAmountTendered)} | Sukli: ${this.formatMoney(Math.max(this.change, 0))}]` : '',
            ].filter(Boolean).join(' ');

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
                        special_instructions: instructionsSummary || null,
                        discount_rate: this.selectedDiscountRate,
                        discount_label: this.selectedDiscountLabel,
                        discount_amount: this.discountAmount,
                        amount_tendered: this.isCashPayment ? this.numericAmountTendered : null,
                        change: this.isCashPayment ? Math.max(this.change, 0) : 0,
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
                this.amountTendered = '';
                this.selectedDiscountRate = 0;
                this.selectedDiscountLabel = 'None';
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
