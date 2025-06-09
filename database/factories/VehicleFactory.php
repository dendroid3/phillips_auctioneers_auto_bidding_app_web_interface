<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    public $vehicles = [
          { id: "KCA 742D - Toyota Noah" },
  { id: "KCB 118L - Nissan Note" },
  { id: "KCC 335F - Honda Vezel" },
  { id: "KBA 009T - Mitsubishi Pajero" },
  { id: "KCA 876M - Subaru Forester" },
  { id: "KCB 224R - Mazda Demio" },
  { id: "KCC 551S - Suzuki Swift" },
  { id: "KBA 667J - Mercedes C200" },
  { id: "KCA 293K - BMW X5" },
  { id: "KCB 480N - Audi A4" },
  { id: "KCC 157P - Volkswagen Tiguan" },
  { id: "KBA 824H - Ford Ranger" },
  { id: "KCA 361G - Chevrolet Cruze" },
  { id: "KCB 698V - Hyundai Tucson" },
  { id: "KCC 425W - Kia Sportage" },
  { id: "KBA 772E - Lexus RX350" },
  { id: "KCA 039F - Isuzu D-Max" },
  { id: "KCB 506D - Peugeot 308" },
  { id: "KCC 243L - Renault Duster" },
  { id: "KBA 914M - Volvo XC60" },
  { id: "KCA 681N - Toyota Harrier" },
  { id: "KCB 357P - Nissan X-Trail" },
  { id: "KCC 124Q - Honda CR-V" },
  { id: "KBA 892R - Mitsubishi Outlander" },
  { id: "KCA 469S - Subaru XV" },
  { id: "KCB 736T - Mazda CX-5" },
  { id: "KCC 203U - Suzuki Vitara" },
  { id: "KBA 570V - Mercedes GLE" },
  { id: "KCA 947W - BMW 3 Series" },
  { id: "KCB 614X - Audi Q7" }

    ];
    public function definition(): array
    {
        return [
            'phillips_vehicle_id' => 
            'description' => 'Some description',
            'current_bid' => floor(rand(50, 1000)) * 1000,
            'start_amount' => floor(rand(50, 200)) * 1000,
            'maximum_amount' => floor(rand(200, 1000)) * 1000,
        ];
    }
}
