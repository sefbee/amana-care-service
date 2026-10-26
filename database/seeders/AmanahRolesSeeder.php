<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class AmanahRolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['slug' => 'om',  'title' => 'Operations Manager'],
            ['slug' => 'co',  'title' => 'Placement Coordinator'],
            ['slug' => 'rs',  'title' => 'Recruitment & Screening Officer'],
            ['slug' => 'tr',  'title' => 'Trainer'],
            ['slug' => 'fn',  'title' => 'Finance Officer'],
            ['slug' => 'qs',  'title' => 'Quality & Support Officer'],
            ['slug' => 'sl',  'title' => 'Safeguarding Lead'],
            ['slug' => 'cm',  'title' => 'Content Manager'],
            ['slug' => 'ex',  'title' => 'Executive / Auditor'],
        ];

        foreach ($roles as $role) {
            $record = Role::withTrashed()->firstOrNew(['slug' => $role['slug']]);
            $record->fill(['title' => $role['title'], 'is_active' => true]);
            if ($record->trashed()) {
                $record->restore();
            }
            $record->save();
        }
    }
}
