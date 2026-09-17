<?php

namespace Database\Seeders;

use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Seed the hotel organizations and their branches.
     */
    public function run(): void
    {
        $org = HotelOrganization::create([
            'name' => 'Heritage Hotels',
            'code' => 'HTL',
            'description' => 'A premium hospitality brand across Nigeria.',
            'address' => '14 Marina Road',
            'city' => 'Lagos',
            'state' => 'Lagos State',
            'country' => 'Nigeria',
            'phone' => '+234 801 000 0000',
            'email' => 'info@heritage.ng',
            'currency' => 'NGN',
            'star_rating' => 4,
            'status' => 'active',
        ]);

        // Branch 1
        $ijebu = HotelBranch::create([
            'organization_id' => $org->id,
            'name' => 'Ijebu Heritage Hotel',
            'code' => 'IHH',
            'description' => 'Heritage hotel located in the heart of Ijebu Ode.',
            'address' => '28 Folagbade Street',
            'city' => 'Ijebu Ode',
            'state' => 'Ogun State',
            'country' => 'Nigeria',
            'phone' => '+234 803 000 1111',
            'email' => 'ijebu@heritage.ng',
            'currency' => 'NGN',
            'star_rating' => 4,
            'check_in_time' => '14:00',
            'check_out_time' => '12:00',
            'status' => 'active',
            'is_default' => true,
        ]);

        // Branch 2
        $ibadan = HotelBranch::create([
            'organization_id' => $org->id,
            'name' => 'Heritage Suites, Ibadan',
            'code' => 'HSI',
            'description' => 'Contemporary suites for business and leisure.',
            'address' => '7A Bodija Ring Road',
            'city' => 'Ibadan',
            'state' => 'Oyo State',
            'country' => 'Nigeria',
            'phone' => '+234 803 000 2222',
            'email' => 'ibadan@heritage.ng',
            'currency' => 'NGN',
            'star_rating' => 3,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'status' => 'active',
            'is_default' => false,
        ]);

        // Branch 3 (Lagos flagship)
        HotelBranch::create([
            'organization_id' => $org->id,
            'name' => 'Heritage Marina, Lagos',
            'code' => 'HML',
            'description' => 'Flagship marina-front property in Lagos.',
            'address' => '14 Marina Road, Victoria Island',
            'city' => 'Lagos',
            'state' => 'Lagos State',
            'country' => 'Nigeria',
            'phone' => '+234 803 000 3333',
            'email' => 'lagos@heritage.ng',
            'currency' => 'NGN',
            'star_rating' => 5,
            'check_in_time' => '15:00',
            'check_out_time' => '12:00',
            'status' => 'active',
            'is_default' => false,
        ]);
    }
}