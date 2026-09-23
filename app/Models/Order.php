<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PREPARING = 'preparing';

    public const STATUS_READY = 'ready';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_PREPARING,
        self::STATUS_READY,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_PREPARING => 'Preparing',
        self::STATUS_READY => 'Ready',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'Cash',
        'gcash' => 'GCash',
    ];

    protected $fillable = [
        'order_number',
        'user_id',
        'reservation_id',
        'customer_name',
        'contact_number',
        'delivery_address',
        'payment_method',
        'payment_proof',
        'payment_reference',
        'payment_amount_paid',
        'subtotal',
        'delivery_fee',
        'deposit_credit',
        'total',
        'status',
        'special_instructions',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'deposit_credit' => 'decimal:2',
            'total' => 'decimal:2',
            'payment_amount_paid' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        $legacy = [
            'ready_for_pickup' => 'Ready',
            'out_for_delivery' => 'Ready',
        ];

        if (isset($legacy[$this->status])) {
            return $legacy[$this->status];
        }

        return self::STATUS_LABELS[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function paymentMethodLabel(): string
    {
        if ($this->payment_method === 'cash_on_delivery') {
            return 'Cash';
        }

        return self::PAYMENT_METHODS[$this->payment_method] ?? ucfirst(str_replace('_', ' ', $this->payment_method));
    }

    public function requiresPaymentProof(): bool
    {
        return $this->payment_method === 'gcash';
    }

    public function paymentProofUrl(): ?string
    {
        if (! $this->payment_proof) {
            return null;
        }

        return '/'.$this->payment_proof;
    }

    public function formattedTotal(): string
    {
        return '₱'.number_format((float) $this->total, 2);
    }

    public static function generateOrderNumber(): string
    {
        return 'SG-'.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));
    }
}
