<?php

namespace Database\Seeders;

use App\Models\Tarif;
use App\Models\Ville;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TarifsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ville::create([
            'name' =>"casablanca",
            'prefix' =>"CA",
        ]);
       /* Tarif::create([
            'destination' =>"casablanca-anfa",
            'delai_livraison' =>"24h",
            'prefix' =>"CAAF",
            'tarif' =>20
        ]);
       Tarif::create([
            'destination' =>"casablanca-zenata",
            'delai_livraison' =>"48h",
            'prefix' =>"CAZE",
            'tarif' =>15
        ]); */
    }
}
