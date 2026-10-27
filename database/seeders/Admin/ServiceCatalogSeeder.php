<?php

namespace Database\Seeders\Admin;

use App\Admin\Models\PriceVersion;
use App\Admin\Models\Service;
use Illuminate\Database\Seeder;

class ServiceCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'code'        => 'MAID-LI',
                'name'        => 'Maid (Live-in)',
                'variant'     => 'live-in',
                'description' => 'Full-time live-in maid service for household management.',
                'amount'      => 20000.00,
            ],
            [
                'code'        => 'MAID-LO',
                'name'        => 'Maid (Live-out)',
                'variant'     => 'live-out',
                'description' => 'Part-time live-out maid service for household management.',
                'amount'      => 20000.00,
            ],
            [
                'code'        => 'NANNY',
                'name'        => 'Nanny / Childcare',
                'variant'     => 'n/a',
                'description' => 'Professional childcare including feeding, school preparation, play, and learning activities.',
                'amount'      => 25000.00,
            ],
            [
                'code'        => 'ELDERLY',
                'name'        => 'Elderly Care',
                'variant'     => 'n/a',
                'description' => 'Companionship, daily routines, mobility support, medication reminders, and wellbeing monitoring.',
                'amount'      => 24000.00,
            ],
            [
                'code'        => 'COOK',
                'name'        => 'Cook',
                'variant'     => 'n/a',
                'description' => 'Meal preparation, weekly menus, kitchen organisation, and dietary preferences.',
                'amount'      => 18000.00,
            ],
        ];

        foreach ($services as $data) {
            $amount = $data['amount'];
            unset($data['amount']);

            $service = Service::withTrashed()->firstOrNew(['code' => $data['code']]);
            $service->fill(array_merge($data, ['is_active' => true]));
            if ($service->trashed()) {
                $service->restore();
            }
            $service->save();

            // Seed indicative price version if none exists
            if ($service->priceVersions()->count() === 0) {
                PriceVersion::create([
                    'service_id'     => $service->id,
                    'amount'         => $amount,
                    'unit'           => 'per month',
                    'price_type'     => 'Indicative Starting Price',
                    'status'         => 'Draft',
                    'effective_from' => now()->toDateString(),
                    'source_note'    => 'W3 website value, Unverified — requires OM approval before use',
                ]);
            }
        }
    }
}
