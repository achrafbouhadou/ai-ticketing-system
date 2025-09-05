<?php

namespace Database\Factories;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        $categories = array_map(fn($c) => $c->value, TicketCategory::cases());
        $statuses   = array_map(fn($s) => $s->value, TicketStatus::cases());

        $maybeCategory = fake()->boolean(70) ? fake()->randomElement($categories) : null;

        return [
            'subject'          => fake()->sentence(6),
            'body'             => fake()->paragraphs(2, true),
            'status'           => fake()->randomElement($statuses),
            'category'         => $maybeCategory,
            'category_source'  => 'ai',
            'explanation'      => $maybeCategory ? fake()->sentence(12) : null,
            'confidence'       => $maybeCategory ? fake()->randomFloat(2, 0.50, 0.98) : null,
            'note'             => fake()->boolean(30) ? fake()->sentence(10) : null,
        ];
    }
}
