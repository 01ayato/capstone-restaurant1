<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\RestaurantSetting;
use App\Services\CartService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReviewsController extends Controller
{
    /**
     * Full reviews list — guest-only, same visibility rule as the Home page section.
     */
    public function index(CartService $cart): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('landing');
        }

        $reviews = Feedback::query()
            ->where('status', Feedback::STATUS_APPROVED)
            ->with('user')
            ->latest()
            ->paginate(12);

        return view('portal.reviews', [
            'reviews' => $reviews,
            'settings' => RestaurantSetting::current(),
            'cartCount' => $cart->count(),
        ]);
    }
}
