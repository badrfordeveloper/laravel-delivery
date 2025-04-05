<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UsersRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userAdmin = User::create([
            'lastName' =>"admin",
            'firstName' =>"prenomAdmin",
            'email' =>"admin@gmail.com",
            'active' =>true,
            'password' =>bcrypt("admin@gmail.com"),
            'phone' =>"0660606006",
            'store_name' =>"",
            'ville' =>"casablanca-anfa",
            'address' =>"adresse admin",
        ]);
        $userGestionnaire1 = User::create([
            'lastName' =>"gestionnaire1",
            'firstName' =>"gestionnaire1",
            'email' =>"gestionnaire1@gmail.com",
            'active' =>true,
            'password' =>bcrypt("gestionnaire1@gmail.com"),
            'phone' =>"0660606006",
            'store_name' =>"",
            'ville' =>"casablanca-anfa",
            'address' =>"adresse gestionnaire1",
        ]);
        $userGestionnaire2 = User::create([
            'lastName' =>"gestionnaire2",
            'firstName' =>"gestionnaire2",
            'email' =>"gestionnaire2@gmail.com",
            'active' =>true,
            'password' =>bcrypt("gestionnaire2@gmail.com"),
            'phone' =>"0660606006",
            'store_name' =>"",
            'ville' =>"casablanca-anfa",
            'address' =>"adresse gestionnaire2",
        ]);
        $userLivreur1 = User::create([
            'lastName' =>"livreur1",
            'firstName' =>"livreurPrenom1",
            'email' =>"livreur1@gmail.com",
            'active' =>true,
            'password' =>bcrypt("livreur1@gmail.com"),
            'phone' =>"0660606006",
            'store_name' =>"",
            'ville' =>"casablanca-anfa",
            'address' =>"adresse livreur1",
        ]);
        $userLivreur2 = User::create([
            'lastName' =>"livreur2",
            'firstName' =>"livreurPrenom2",
            'email' =>"livreur2@gmail.com",
            'active' =>true,
            'password' =>bcrypt("livreur2@gmail.com"),
            'phone' =>"0660606006",
            'store_name' =>"",
            'ville' =>"casablanca-zenata",
            'address' =>"adresse livreur2",
        ]);
        $userVendeur1 = User::create([
            'lastName' =>"vendeur1",
            'firstName' =>"vendeurPrenom1",
            'email' =>"vendeur1@gmail.com",
            'active' =>true,
            'password' =>bcrypt("vendeur1@gmail.com"),
            'phone' =>"0660606006",
            'store_name' =>"store 1",
            'ville' =>"casablanca-anfa",
            'address' =>"adresse vendeur1",
        ]);
        $userVendeur2 = User::create([
            'lastName' =>"vendeur2",
            'firstName' =>"vendeurPrenom2",
            'email' =>"vendeur2@gmail.com",
            'active' =>true,
            'password' =>bcrypt("vendeur2@gmail.com"),
            'phone' =>"0670707070",
            'store_name' =>"store 2",
            'ville' =>"casablanca-zenata",
            'address' =>"adresse vendeur2",
        ]);

        $roleAdmin = Role::create(['name' =>'admin']);
        $roleGestionnaire = Role::create(['name' =>'gestionnaire']);
        $roleLivreur = Role::create(['name' =>'livreur']);
        $roleVendeur = Role::create(['name' =>'vendeur']);
        $userAdmin->assignRole($roleAdmin);
        $userGestionnaire1->assignRole($roleGestionnaire);
        $userGestionnaire2->assignRole($roleGestionnaire);
        $userLivreur1->assignRole($roleLivreur);
        $userLivreur2->assignRole($roleLivreur);
        $userVendeur1->assignRole($roleVendeur);
        $userVendeur2->assignRole($roleVendeur);
    }
}
