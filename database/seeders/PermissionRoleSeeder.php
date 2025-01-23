<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = ['role','permission','user'];
       // $permissions = ['user'];
        $actions = ['list','create','update','show','delete'];

        //generate permissions
        $fullPermissions = [];
        foreach ($actions as $action) {
            foreach ($permissions as $permission) {
                $fullPermissions[] = $permission.'.'.$action;
            }
        }

        // Permission::create(['name' => 'role.create']);
        foreach ($fullPermissions as $mypermission) {
            Permission::create(['name' =>$mypermission]);
        }

        // create roles and assign existing permissions
        $role = Role::where('name','admin')->first();
        $role->givePermissionTo($fullPermissions);
        //  $role->syncPermissions($fullPermissions);
        // $user = User::find(1);
        // $user->assignRole($role);
    }
}
