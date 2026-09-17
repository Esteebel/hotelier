<?php

namespace Database\Seeders;

use App\Models\HotelBranch;
use App\Models\HotelOrganization;
use App\Models\Room;
use App\Models\RoomType;
use App\Enums\RoomStatus;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Seed room types and rooms for all active branches.
     */
    public function run(): void
    {
        $branches = HotelBranch::where('status', 'active')->get();

        foreach ($branches as $branch) {
            $roomTypes = [
                ['name' => 'Standard Single', 'description' => 'Comfortable single occupancy room.', 'base_price' => 35000, 'capacity' => 1, 'number_of_beds' => 1, 'bed_configuration' => '1 Single Bed'],
                ['name' => 'Standard Double', 'description' => 'Spacious double occupancy room.', 'base_price' => 50000, 'capacity' => 2, 'number_of_beds' => 1, 'bed_configuration' => '1 Queen Bed'],
                ['name' => 'Deluxe Double', 'description' => 'Premium double room with city view.', 'base_price' => 75000, 'capacity' => 2, 'number_of_beds' => 1, 'bed_configuration' => '1 King Bed'],
                ['name' => 'Executive Suite', 'description' => 'Luxury suite with separate living area.', 'base_price' => 150000, 'capacity' => 3, 'number_of_beds' => 2, 'bed_configuration' => '1 King Bed + 1 Twin Bed'],
            ];

            foreach ($roomTypes as $typeData) {
                $roomType = $branch->roomTypes()->create($typeData);

                // Create 4 rooms per type
                $capacity = $branch->rooms()->count() + 1;
                $floorBase = (int) ceil($capacity / 10);

                for ($i = 0; $i < 4; $i++) {
                    $floor = $floorBase + intdiv($i, 2);
                    $roomNumber = sprintf('%d%02d', $floor, $capacity % 100 + 1 + $i);

                    Room::create([
                        'branch_id' => $branch->id,
                        'room_type_id' => $roomType->id,
                        'room_number' => $roomNumber,
                        'floor' => (string) $floor,
                        'capacity' => $typeData['capacity'],
                        'status' => RoomStatus::Available,
                    ]);
                }
            }
        }
    }
}