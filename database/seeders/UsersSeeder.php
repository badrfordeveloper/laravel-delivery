<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'lastName' =>"admin",
            'firstName' =>"admiiin",
            'email' =>"admin@gmail.com",
            'active' =>true,
            'password' =>bcrypt("said@123"),
        ]);
        $role = Role::create(['name' =>'admin']);
        $user2 = User::create([
            'lastName' =>"manager",
            'firstName' =>"manaager",
            'email' =>"manager@gmail.com",
            'active' =>false,
            'password' =>bcrypt("said@123"),
        ]);
        $role2 = Role::create(['name' =>'manager']);

        $user->assignRole($role);
        $user2->assignRole($role2);
    }
}
