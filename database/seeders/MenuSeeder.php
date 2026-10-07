<?php

namespace Database\Seeders;

use App\Models\HotelBranch;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Seed restaurant menu items for every active branch.
     */
    public function run(): void
    {
        $branches = HotelBranch::where('status', 'active')->get();

        $menu = [
            ['name' => 'English Breakfast Platter', 'category' => 'Breakfast', 'description' => 'Eggs, bacon, sausage, baked beans, toast and tea.', 'price' => 6500],
            ['name' => 'Pancake & Fruit Stack', 'category' => 'Breakfast', 'description' => 'Fluffy pancakes with honey and fresh seasonal fruit.', 'price' => 4500],
            ['name' => 'Yam & Egg Sauce', 'category' => 'Breakfast', 'description' => 'Boiled yam with peppered egg sauce.', 'price' => 4000],
            ['name' => 'Jollof Rice & Chicken', 'category' => 'Lunch', 'description' => 'Classic Nigerian jollof rice with grilled chicken.', 'price' => 7500],
            ['name' => 'Fried Rice & Beef', 'category' => 'Lunch', 'description' => 'Stir-fried rice with tender beef chunks and vegetables.', 'price' => 8000],
            ['name' => 'Caesar Salad', 'category' => 'Lunch', 'description' => 'Romaine lettuce, croutons, parmesan and Caesar dressing.', 'price' => 5500],
            ['name' => 'Ofada Rice & Ayamase', 'category' => 'Dinner', 'description' => 'Local ofada rice with spicy green pepper sauce and meat.', 'price' => 9000],
            ['name' => 'Grilled Tilapia', 'category' => 'Dinner', 'description' => 'Whole grilled tilapia with pepper sauce and sides.', 'price' => 12000],
            ['name' => 'Spaghetti Bolognese', 'category' => 'Dinner', 'description' => 'Spaghetti with rich minced beef bolognese sauce.', 'price' => 7000],
            ['name' => 'Meat Pie', 'category' => 'Snacks', 'description' => 'Flaky pastry filled with seasoned minced meat and vegetables.', 'price' => 2500],
            ['name' => 'Puff Puff (6 pieces)', 'category' => 'Snacks', 'description' => 'Warm, fluffy Nigerian doughnuts.', 'price' => 2000],
            ['name' => 'Chips & Chicken Wings', 'category' => 'Snacks', 'description' => 'Crispy fries with six glazed chicken wings.', 'price' => 6000],
            ['name' => 'Fresh Orange Juice', 'category' => 'Drinks', 'description' => 'Freshly squeezed orange juice, 500ml.', 'price' => 3000],
            ['name' => 'Zobo Drink', 'category' => 'Drinks', 'description' => 'Chilled hibiscus infusion with spices, 500ml.', 'price' => 2500],
            ['name' => 'Bottled Water', 'category' => 'Drinks', 'description' => 'Still mineral water, 75cl.', 'price' => 1000],
            ['name' => 'Cappuccino', 'category' => 'Drinks', 'description' => 'Espresso with steamed milk foam.', 'price' => 3500],
            ['name' => 'Chocolate Cake Slice', 'category' => 'Dessert', 'description' => 'Rich layered chocolate cake with ganache.', 'price' => 4000],
            ['name' => 'Ice Cream Sundae', 'category' => 'Dessert', 'description' => 'Three scoops with chocolate sauce and sprinkles.', 'price' => 3500],
        ];

        foreach ($branches as $branch) {
            foreach ($menu as $index => $item) {
                MenuItem::firstOrCreate(
                    ['branch_id' => $branch->id, 'name' => $item['name']],
                    [
                        'category' => $item['category'],
                        'description' => $item['description'],
                        'price' => $item['price'],
                        'is_available' => true,
                        'sort_order' => $index,
                    ],
                );
            }
        }
    }
}
