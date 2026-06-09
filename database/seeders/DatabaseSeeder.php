<?php

namespace Database\Seeders;

use App\Modules\Access\Database\Seeders\AccessSeeder;
use App\Modules\Administrative\Database\Seeders\AdministrativeSeeder;
use App\Modules\Academics\Database\Seeders\AcademicsSeeder;
use App\Modules\Exams\Database\Seeders\ExamsSeeder;
use App\Modules\Registration\Database\Seeders\RegistrationSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccessSeeder::class,
            AdministrativeSeeder::class,
            AcademicsSeeder::class,
            ExamsSeeder::class,
            RegistrationSeeder::class,
        ]);
    }
}
