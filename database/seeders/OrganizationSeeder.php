<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $organizations = [
            [
                'organization_name' => 'Global Solutions Inc',
                'short_name' => 'GSI',
                'address' => '123 Business Street, Tech Park',
                'city' => 'Mumbai',
                'town' => 'Fort',
                'village' => '',
                'pincode' => '400001',
                'logo' => null,
                'status' => 'active',
                'max_location_allowed' => 5,
                'max_user_per_location' => 20,
                'gstin' => '18AABCT1234H1Z0',
            ],
            [
                'organization_name' => 'Tech Innovations Ltd',
                'short_name' => 'TIL',
                'address' => '456 Innovation Drive, Tech Hub',
                'city' => 'Bangalore',
                'town' => 'Whitefield',
                'village' => '',
                'pincode' => '560066',
                'logo' => null,
                'status' => 'active',
                'max_location_allowed' => 10,
                'max_user_per_location' => 50,
                'gstin' => '29AABCT5678H1Z0',
            ],
            [
                'organization_name' => 'Digital Services Corp',
                'short_name' => 'DSC',
                'address' => '789 Digital Lane, Business District',
                'city' => 'Delhi',
                'town' => 'Gurgaon',
                'village' => '',
                'pincode' => '122001',
                'logo' => null,
                'status' => 'active',
                'max_location_allowed' => 3,
                'max_user_per_location' => 15,
                'gstin' => '07AABCT9012H1Z0',
            ],
            [
                'organization_name' => 'Enterprise Systems Group',
                'short_name' => 'ESG',
                'address' => '321 Enterprise Road, Corporate Park',
                'city' => 'Pune',
                'town' => 'Hinjewadi',
                'village' => '',
                'pincode' => '411057',
                'logo' => null,
                'status' => 'inactive',
                'max_location_allowed' => 2,
                'max_user_per_location' => 10,
                'gstin' => '27AABCT3456H1Z0',
            ],
            [
                'organization_name' => 'Business Solutions International',
                'short_name' => 'BSI',
                'address' => '654 Solution Avenue, Commerce Hub',
                'city' => 'Chennai',
                'town' => 'OMR',
                'village' => '',
                'pincode' => '600096',
                'logo' => null,
                'status' => 'active',
                'max_location_allowed' => 7,
                'max_user_per_location' => 30,
                'gstin' => '33AABCT7890H1Z0',
            ],
        ];

        foreach ($organizations as $organization) {
            Organization::create($organization);
        }
    }
}
