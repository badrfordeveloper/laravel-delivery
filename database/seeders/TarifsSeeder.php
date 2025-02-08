<?php

namespace Database\Seeders;

use App\Models\Tarif;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TarifsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       Tarif::create([
            'destination' =>"casablanca-anfa",
            'prefix' =>"CAAF",
            'tarif' =>20
        ]);
       Tarif::create([
            'destination' =>"casablanca-zenata",
            'prefix' =>"CAZE",
            'tarif' =>15
        ]);
    }
}
