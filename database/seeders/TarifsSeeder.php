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
            'tarif' =>20
        ]);
       Tarif::create([
            'destination' =>"casablanca-zenata",
            'tarif' =>15.5
        ]);
    }
}
