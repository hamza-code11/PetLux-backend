<?php

namespace Database\Seeders;

use App\Models\CmsSection;
use Illuminate\Database\Seeder;

class CmsSectionSeeder extends Seeder
{
    public function run(): void
    {
        CmsSection::updateOrCreate(
            ['key' => 'category_section'],
            [
                'content' => [
                    'title'    => 'SHOP BY CATEGORY',
                    'subtitle' => 'Find the perfect products for your pet',
                    'categories' => [
                        ['name' => 'Dogs',   'slug' => 'dogs',   'image' => '', 'count' => 0],
                        ['name' => 'Cats',   'slug' => 'cats',   'image' => '', 'count' => 0],
                        ['name' => 'Birds',  'slug' => 'birds',  'image' => '', 'count' => 0],
                        ['name' => 'Fish',   'slug' => 'fish',   'image' => '', 'count' => 0],
                    ],
                ],
            ]
        );

        CmsSection::updateOrCreate(
            ['key' => 'category_section'],
            [
                'content' => [
                    'title'    => 'Shop by Category',
                    'subtitle' => 'Everything your pet needs, neatly organised.',
                    'categories' => [
                        [
                            'name'  => 'Foods',
                            'slug'  => 'foods',
                            'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600&q=80',
                        ],
                        [
                            'name'  => 'Toys',
                            'slug'  => 'toys',
                            'image' => 'https://images.unsplash.com/photo-1576201836106-db1758fd1c97?w=600&q=80',
                        ],
                        [
                            'name'  => 'Treats',
                            'slug'  => 'treats',
                            'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600&q=80',
                        ],
                        [
                            'name'  => 'Accessories',
                            'slug'  => 'accessories',
                            'image' => 'https://images.unsplash.com/photo-1558929996-da64ba858215?w=600&q=80',
                        ],
                        [
                            'name'  => 'Grooming',
                            'slug'  => 'grooming',
                            'image' => 'https://images.unsplash.com/photo-1516734212186-a967f81ad0d7?w=600&q=80',
                        ],
                    ],
                ],
            ]
        );

        // Food Section
        CmsSection::updateOrCreate(
            ['key' => 'food_section'],
            [
                'content' => [
                    'eyebrow' => 'NUTRITION MEETS FLAVOR',

                    'title' => 'REAL FOOD FOR HAPPY TAILS',

                    'description' => 'Give your pet the nutrition they deserve. Our vet-formulated recipes are made with real, wholesome ingredients—no fillers, no artificial preservatives, just pure goodness in every bite.',

                    'features' => [
                        'Vet-formulated & grain-free options',
                        '100% real meat & fresh produce',
                        'Rich in Omega-3 for a healthy coat',
                    ],

                    'button_text' => 'SHOP PREMIUM FOOD',

                    'button_link' => '/shop/food',

                    'image' => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=800&q=80',
                ],
            ]
        );


        CmsSection::updateOrCreate(
            ['key' => 'why_choose_section'],
            [
                'content' => [
                    'eyebrow' => 'Why Choose PetLux',
                    'title' => 'The best for your best friend.',
                    'subtitle' => 'We go the extra mile to ensure your pet gets nothing but the best. Here is why thousands of pet parents trust us.',

                    'features' => [
                        [
                            'title' => 'Vet-Approved Quality',
                            'description' => 'Every product is carefully selected and recommended by veterinarians to ensure the highest safety and health standards for your pet.',
                        ],
                        [
                            'title' => 'Fast & Free Delivery',
                            'description' => 'Get your pet essentials delivered to your doorstep quickly. Enjoy free shipping on all orders over Rs. 5,000.',
                        ],
                        [
                            'title' => 'Premium Ingredients',
                            'description' => 'We source only the finest, natural ingredients. No artificial fillers, no preservatives—just pure, wholesome nutrition.',
                        ],
                        [
                            'title' => '24/7 Customer Support',
                            'description' => 'Have a question? Our dedicated pet care experts are always here to help you with personalized advice and support.',
                        ],
                    ],
                ],
            ]
        );


        CmsSection::updateOrCreate(
            ['key' => 'banner_section'],
            [
                'content' => [
                    'image' => 'banner/01.png',
                    'link' => '/shop/food',
                ],
            ]
        );


        CmsSection::updateOrCreate(
            ['key' => 'about_section'],
            [
                'content' => [
                    'hero' => [
                        'eyebrow' => 'Our Story',
                        'title' => 'We believe every pet deserves the best.',
                        'description' => "At PetLux, we're on a mission to make premium pet care accessible to everyone. From vet-formulated food to thoughtfully designed essentials, everything we do is driven by our love for animals.",
                    ],

                    'stats' => [
                        ['value' => '10K+', 'label' => 'Happy Pets'],
                        ['value' => '500+', 'label' => 'Premium Products'],
                        ['value' => '50+', 'label' => 'Trusted Brands'],
                        ['value' => '4.9', 'label' => 'Average Rating'],
                    ],

                    'mission' => [
                        'eyebrow' => 'Our Mission',
                        'title' => 'More than just a pet store.',
                        'description_one' => 'We started PetLux because we were frustrated with the lack of transparency in the pet products industry. Too many brands hide behind vague ingredient lists, use cheap fillers, and prioritize profit over the health of our furry friends. We knew there had to be a better way.',
                        'description_two' => "That's why every single product on our shelves is carefully vetted. We dig deep into sourcing, manufacturing processes, and nutritional value. We only stock what we would confidently feed our own pets — nothing less. Our promise is simple: honest ingredients, transparent pricing, and exceptional quality that you can trust.",
                        'button_text' => 'Shop Our Collection',
                        'button_link' => '/shop',
                    ],

                    'team' => [
                        'eyebrow' => 'Meet the Team',
                        'title' => 'The people behind PetLux',
                        'members' => [
                            [
                                'name' => 'Sarah Johnson',
                                'role' => 'Founder & CEO',
                                'image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=600&q=80',
                            ],
                            [
                                'name' => 'Michael Chen',
                                'role' => 'Head of Operations',
                                'image' => 'https://images.unsplash.com/photo-1560250097-0b93528c311a?w=600&q=80',
                            ],
                            [
                                'name' => 'Emily Davis',
                                'role' => 'Veterinary Advisor',
                                'image' => 'https://images.unsplash.com/photo-1559839734-2b71ea197ec2?w=600&q=80',
                            ],
                        ],
                    ],

                    'images' => [
                        [
                            'key' => 'hero',
                            'src' => 'https://images.unsplash.com/photo-1548199973-03cce0bbc87b?w=1200&q=80',
                        ],
                        [
                            'key' => 'mission',
                            'src' => 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?w=800&q=80',
                        ],
                    ],
                ],
            ]
        );


        CmsSection::updateOrCreate(
            ['key' => 'hero_section'],
            [
                'content' => [
                    'badge_text' => 'Healthy Food • Happy Pets',

                    'title_line_one' => 'PetLux',
                    'title_line_two' => 'Pet',
                    'title_highlight' => 'Foods',

                    'tagline' => 'Nutritious, Delicious & Trusted Pet Food for a Healthier, Happier Life.',

                    'button_text' => 'Shop Now',
                    'button_link' => '/shop',

                    'image' => '/images/hero-pets.jpg',

                    'features' => [
                        [
                            'line_one' => 'Premium Quality',
                            'line_two' => 'Ingredients',
                            'icon' => 'shield',
                        ],
                        [
                            'line_one' => 'Supports Overall',
                            'line_two' => 'Health',
                            'icon' => 'heart',
                        ],
                        [
                            'line_one' => 'Loved by',
                            'line_two' => 'Pets',
                            'icon' => 'paw',
                        ],
                        [
                            'line_one' => 'Fast & Reliable',
                            'line_two' => 'Delivery',
                            'icon' => 'truck',
                        ],
                    ],
                ],
            ]
        );


    }
}


