<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CANCELLED,
        self::STATUS_COMPLETED,
    ];

    public const STATUS_LABELS = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_COMPLETED => 'Completed',
    ];

    /**
     * Statuses that still hold a table (used for double-booking checks).
     */
    public const BLOCKING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
    ];

    public const DEFAULT_DURATION_MINUTES = 120;

    /** Restaurant opens at 2:00 PM every day. */
    public const OPEN_HOUR = 14;

    /** Restaurant closes at midnight (12:00 AM). Last valid start minute of the day is 23:59. */
    public const CLOSE_HOUR = 24;

    public const OPENING_HOURS_LABEL = 'Mon–Sun · 2:00 PM – 12:00 AM';

    public const TABLE_DEPOSIT_AMOUNT = 500;

    public const PREORDER_DEPOSIT_AMOUNT = 500;

    public const DEPOSIT_STATUS_PENDING_AT_RESTAURANT = 'pending_at_restaurant';

    public const DEPOSIT_STATUS_PAID = 'paid';

    public const DEPOSIT_STATUS_APPLIED = 'applied';

    public const DEPOSIT_STATUS_REFUNDED = 'refunded';

    public const DEPOSIT_STATUS_LABELS = [
        self::DEPOSIT_STATUS_PENDING_AT_RESTAURANT => 'Pay at restaurant',
        self::DEPOSIT_STATUS_PAID => 'Paid',
        self::DEPOSIT_STATUS_APPLIED => 'Applied to bill',
        self::DEPOSIT_STATUS_REFUNDED => 'Refunded',
    ];

    protected $fillable = [
        'user_id',
        'dining_table_id',
        'customer_name',
        'contact_number',
        'email',
        'party_size',
        'additional_chairs',
        'table_deposit_amount',
        'preorder_deposit_amount',
        'deposit_total',
        'deposit_payment_method',
        'deposit_payment_proof',
        'deposit_payment_reference',
        'deposit_payment_amount_paid',
        'deposit_status',
        'deposit_paid_at',
        'applied_order_id',
        'reserved_at',
        'duration_minutes',
        'notes',
        'preorder_items',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'duration_minutes' => 'integer',
            'party_size' => 'integer',
            'additional_chairs' => 'integer',
            'table_deposit_amount' => 'decimal:2',
            'preorder_deposit_amount' => 'decimal:2',
            'deposit_total' => 'decimal:2',
            'deposit_payment_amount_paid' => 'decimal:2',
            'deposit_paid_at' => 'datetime',
            'preorder_items' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function appliedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'applied_order_id');
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Best email for guest notifications (reservation contact, else linked account).
     */
    public function notificationEmail(): ?string
    {
        $email = trim((string) ($this->email ?: $this->user?->email));

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function depositStatusLabel(): string
    {
        return self::DEPOSIT_STATUS_LABELS[$this->deposit_status] ?? ucfirst(str_replace('_', ' ', (string) $this->deposit_status));
    }

    /**
     * @return array{table_deposit: float, preorder_deposit: float, total: float}
     */
    public static function calculateDepositAmounts(bool $hasPreorder): array
    {
        $tableDeposit = (float) self::TABLE_DEPOSIT_AMOUNT;
        $preorderDeposit = $hasPreorder ? (float) self::PREORDER_DEPOSIT_AMOUNT : 0.0;

        return [
            'table_deposit' => $tableDeposit,
            'preorder_deposit' => $preorderDeposit,
            'total' => $tableDeposit + $preorderDeposit,
        ];
    }

    public function isDepositPaid(): bool
    {
        return in_array($this->deposit_status, [self::DEPOSIT_STATUS_PAID, self::DEPOSIT_STATUS_APPLIED], true);
    }

    public function canApplyDepositToBill(): bool
    {
        return $this->deposit_status === self::DEPOSIT_STATUS_PAID
            && $this->deposit_total > 0;
    }

    public function formattedDepositTotal(): string
    {
        return '₱'.number_format((float) $this->deposit_total, 2);
    }

    public function endsAt(): ?CarbonInterface
    {
        if ($this->reserved_at === null) {
            return null;
        }

        return $this->reserved_at->copy()->addMinutes($this->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
    }

    /**
     * Determine whether a table already has a blocking reservation overlapping
     * the requested time window. Used to prevent double booking.
     */
    public static function hasConflict(
        int $diningTableId,
        CarbonInterface $start,
        int $durationMinutes,
        ?int $ignoreId = null
    ): bool {
        $end = $start->copy()->addMinutes($durationMinutes);

        return self::query()
            ->where('dining_table_id', $diningTableId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('reserved_at', '<', $end)
            ->whereRaw('DATE_ADD(reserved_at, INTERVAL duration_minutes MINUTE) > ?', [$start])
            ->exists();
    }

    public static function isWithinOpeningHours(CarbonInterface $start): bool
    {
        $hour = (int) $start->format('G');

        // Open from 2:00 PM.
        if ($hour < self::OPEN_HOUR) {
            return false;
        }

        // Dining window must finish by midnight (12:00 AM).
        $end = $start->copy()->addMinutes(self::DEFAULT_DURATION_MINUTES);
        $midnight = $start->copy()->startOfDay()->addDay();

        return $end->lte($midnight);
    }

    /**
     * @return list<int>
     */
    public static function bookedTableIdsForWindow(CarbonInterface $start, int $durationMinutes = self::DEFAULT_DURATION_MINUTES): array
    {
        $end = $start->copy()->addMinutes($durationMinutes);

        return self::query()
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->whereNotNull('dining_table_id')
            ->where('reserved_at', '<', $end)
            ->whereRaw('DATE_ADD(reserved_at, INTERVAL duration_minutes MINUTE) > ?', [$start])
            ->pluck('dining_table_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public static function openingHoursValidationMessage(): string
    {
        return 'Reservations are only available during opening hours ('.self::OPENING_HOURS_LABEL.'). Please pick a time from 2:00 PM onward.';
    }
}
