<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use Illuminate\Contracts\View\View;

class LandingController extends Controller
{
    /** Reviews shown directly on the Home page before the "View All" button appears. */
    private const HOME_REVIEWS_LIMIT = 4;

    public function index(CartService $cart): View
    {
        $signatureDishes = MenuItem::query()
            ->where('is_available', true)
            ->where('is_signature', true)
            ->orderBy('sort_order')
            ->limit(MenuItem::MAX_SIGNATURE_ITEMS)
            ->get();

        $totalApprovedReviews = Feedback::query()
            ->where('status', Feedback::STATUS_APPROVED)
            ->count();

        $reviews = Feedback::query()
            ->where('status', Feedback::STATUS_APPROVED)
            ->with('user')
            ->latest()
            ->limit(self::HOME_REVIEWS_LIMIT)
            ->get();

        return view('portal.landing', [
            'signatureDishes' => $signatureDishes,
            'reviews' => $reviews,
            'totalApprovedReviews' => $totalApprovedReviews,
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
        ]);
    }
}
