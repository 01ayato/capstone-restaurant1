<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    public function create(CartService $cart): View
    {
        $user = Auth::user();

        $completedOrders = Order::query()
            ->where('user_id', $user->id)
            ->where('status', Order::STATUS_COMPLETED)
            ->latest()
            ->get();

        $completedReservations = Reservation::query()
            ->where('user_id', $user->id)
            ->where('status', Reservation::STATUS_COMPLETED)
            ->latest('reserved_at')
            ->get();

        return view('portal.feedback', [
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
            'canLeaveFeedback' => $user->canLeaveFeedback(),
            'completedOrders' => $completedOrders,
            'completedReservations' => $completedReservations,
            'myFeedback' => Feedback::query()->where('user_id', $user->id)->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->canLeaveFeedback()) {
            abort(403, 'Complete an order or reservation before leaving a review.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'linked_visit' => ['nullable', 'string', 'regex:/^(order|reservation):\d+$/'],
        ]);

        $orderId = null;
        $reservationId = null;

        if (! empty($validated['linked_visit'])) {
            [$type, $id] = explode(':', $validated['linked_visit']);

            if ($type === 'order') {
                $orderId = Order::query()
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->where('status', Order::STATUS_COMPLETED)
                    ->value('id');
            } else {
                $reservationId = Reservation::query()
                    ->where('id', $id)
                    ->where('user_id', $user->id)
                    ->where('status', Reservation::STATUS_COMPLETED)
                    ->value('id');
            }
        }

        Feedback::query()->create([
            'user_id' => $user->id,
            'order_id' => $orderId,
            'reservation_id' => $reservationId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => Feedback::STATUS_PENDING,
        ]);

        return back()->with('success', 'Thanks! Your review has been submitted and is pending approval.');
    }
}
