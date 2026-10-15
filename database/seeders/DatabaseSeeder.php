<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\CreateCompany;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $company = new CreateCompany(
            name: 'Dunder Mifflin',
            firstName: 'Michael',
            lastName: 'Scott',
            email: (string) config('jacques.seed_email'),
            password: (string) config('jacques.seed_password'),
        )->execute();

        User::factory()->count(5)->create([
            'company_id' => $company->id,
        ])->each(fn (User $user): Staff => Staff::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]));

        Staff::factory()->count(5)->create([
            'company_id' => $company->id,
        ]);
    }
}
