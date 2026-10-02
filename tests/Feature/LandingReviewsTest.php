<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_reviews_show_on_landing_page_for_guests_and_logged_in_customers(): void
    {
        $reviewer = User::factory()->create();

        Feedback::query()->create([
            'user_id' => $reviewer->id,
            'rating' => 5,
            'comment' => 'Fantastic food and service!',
            'status' => Feedback::STATUS_APPROVED,
        ]);

        $this->get(route('landing'))
            ->assertOk()
            ->assertSee('Customer Reviews')
            ->assertSee('Fantastic food and service!');

        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('landing'))
            ->assertOk()
            ->assertSee('Customer Reviews')
            ->assertSee('Fantastic food and service!');
    }

    public function test_full_reviews_page_is_reachable_while_logged_in(): void
    {
        $reviewer = User::factory()->create();

        Feedback::query()->create([
            'user_id' => $reviewer->id,
            'rating' => 4,
            'comment' => 'Great ambiance.',
            'status' => Feedback::STATUS_APPROVED,
        ]);

        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('reviews.index'))
            ->assertOk()
            ->assertSee('Great ambiance.');
    }
}
