<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Userが指定されない場合は新しく作成する
            'user_id' => User::factory(), 
            'title'   => $this->faker->realText(20),
            'slug'    => $this->faker->unique()->slug(),
            'content' => $this->faker->realText(200),
            'status'  => 'published',
            'view_count' => 0,
        ];
    }
}
