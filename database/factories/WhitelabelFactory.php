<?php

namespace Database\Factories;

use App\Enums\WhitelabelStatus;
use App\Models\Whitelabel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Whitelabel>
 */
class WhitelabelFactory extends Factory
{
    protected $model = Whitelabel::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'status' => WhitelabelStatus::Active,
            'wallet_balance' => 0,
            'brand_name' => $name,
            'primary_color' => '#0F766E',
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => WhitelabelStatus::Suspended,
        ]);
    }

    public function withFloat(float $amount): static
    {
        return $this->state(fn (): array => [
            'wallet_balance' => $amount,
        ]);
    }
}
