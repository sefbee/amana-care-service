<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\Admin\AmanahRolesSeeder;
use Database\Seeders\Admin\MenuSeeder;
use Database\Seeders\Admin\ServiceCatalogSeeder;
use Database\Seeders\Admin\SuperAdminSeeder;

class DatabaseSeeder extends Seeder
{
    /** Seed the application's initial administrator, roles, and navigation. */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
        $this->call(AmanahRolesSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(ServiceCatalogSeeder::class);
    }
}
