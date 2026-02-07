<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'date' => $this->faker->date(),
            'time' => $this->faker->time(),
            'transaction_details' => $this->faker->sentence(),
            'other_transaction_details' => $this->faker->word(),
            'account' => $this->faker->word(),
            'amount' => $this->faker->randomFloat(2, 10, 1000),
            'ref_no' => $this->faker->uuid(),
            'order_id' => $this->faker->uuid(),
            'remarks' => $this->faker->sentence(),
            'tag' => 'test,tag',
            'comment' => $this->faker->sentence(),
            'user_id' => \App\Models\User::factory(),
        ];
    }
}
