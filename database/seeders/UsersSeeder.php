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
            'name' =>"admin",
            'email' =>"admin@gmail.com",
            'password' =>bcrypt("admin"),
        ]);
        $role = Role::create(['name' =>'admin']);

        $user->assignRole($role);
    }
}
