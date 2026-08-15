<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\RestaurantSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        RestaurantSetting::query()->updateOrCreate(
            ['id' => 1],
            [
                'name' => "Smoke n' Grill",
                'description' => 'Enjoy premium grilled dishes and unforgettable dining experiences.',
                'address' => '123 Grill Street, Manila, Philippines',
                'phone' => '+63 912 345 6789',
                'email' => 'hello@smokengrill.ph',
                'delivery_fee' => 50,
                'opening_hours' => 'Mon–Sun: 2:00 PM – 12:00 AM',
            ],
        );

        $menu = [
            'Grilled Favorites' => [
                ['name' => 'Pork BBQ', 'description' => 'Tender pork skewers glazed with our signature sweet-smoky marinade.', 'price' => 199],
                ['name' => 'Chicken BBQ', 'description' => 'Juicy grilled chicken marinated in herbs and spices.', 'price' => 179],
                ['name' => 'Beef Steak', 'description' => 'Premium beef steak grilled to perfection with garlic butter.', 'price' => 349],
            ],
            'Burgers' => [
                ['name' => 'Classic Burger', 'description' => 'Angus beef patty with lettuce, tomato, and house sauce.', 'price' => 199],
                ['name' => 'Cheese Burger', 'description' => 'Classic burger topped with melted cheddar cheese.', 'price' => 249],
            ],
            'Seafood' => [
                ['name' => 'Grilled Squid', 'description' => 'Fresh squid grilled with lemon butter and chili flakes.', 'price' => 299],
                ['name' => 'Buttered Shrimp', 'description' => 'Plump shrimp sautéed in garlic butter sauce.', 'price' => 329],
            ],
            'Drinks' => [
                ['name' => 'Soft Drinks', 'description' => 'Coke, Sprite, or Royal — ice cold.', 'price' => 45, 'stock_level' => 50, 'reorder_level' => 10],
                ['name' => 'Iced Tea', 'description' => 'House-brewed iced tea with lemon.', 'price' => 55, 'stock_level' => 40, 'reorder_level' => 10],
                ['name' => 'Milk Tea', 'description' => 'Creamy brown sugar milk tea with pearls.', 'price' => 89, 'stock_level' => 30, 'reorder_level' => 8],
            ],
        ];

        $sortOrder = 0;
        foreach ($menu as $categoryName => $items) {
            $category = Category::query()->updateOrCreate(
                ['slug' => Str::slug($categoryName)],
                [
                    'name' => $categoryName,
                    'sort_order' => $sortOrder++,
                    'is_active' => true,
                ],
            );

            foreach ($items as $index => $item) {
                MenuItem::query()->updateOrCreate(
                    ['slug' => Str::slug($item['name'])],
                    [
                        'category_id' => $category->id,
                        'name' => $item['name'],
                        'description' => $item['description'],
                        'price' => $item['price'],
                        'is_available' => true,
                        'stock_level' => $item['stock_level'] ?? null,
                        'reorder_level' => $item['reorder_level'] ?? null,
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }
}
