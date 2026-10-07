<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Intentionally empty: courses come from `content:import` and learners
     * register themselves. A seeded account with a known password would be a
     * way into any install where someone runs `db:seed`.
     */
    public function run(): void {}
}
