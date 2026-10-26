<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Seed the application's initial administrator, roles, and navigation. */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
        $this->call(AmanahRolesSeeder::class);
        $this->call(CrmMenuSeeder::class);
        $this->call(ServiceCatalogSeeder::class);
    }
}
