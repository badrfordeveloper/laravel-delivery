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
        $permissions = ['role','permission','tarif','user','colis','ramassage'];
       // $permissions = ['user'];
        $actions = ['list','create','update','show','delete'];

        //generate full permissions
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
        $roleAdmin = Role::where('name','admin')->first();
        $roleGestionnaire = Role::where('name','gestionnaire')->first();
        $roleLivreur = Role::where('name','livreur')->first();
        $roleVendeur = Role::where('name','vendeur')->first();


        //generate gestionnaire permissions
        $GestionnairePermissions = [];
        foreach ($actions as $action) {
            foreach ($permissions as $permission) {
                if(in_array($permission,['role','permission','tarif']))
                    continue;
                $GestionnairePermissions[] = $permission.'.'.$action;
            }
        }
        //generate livreur permissions
        $livreurPermissions = [];
        foreach ($actions as $action) {
            foreach ($permissions as $permission) {
                if(in_array($permission,['role','permission','tarif','user']))
                    continue;
                $livreurPermissions[] = $permission.'.'.$action;
            }
        }

        //generate vendeur permissions
        $vendeurPermissions = [];
        foreach ($actions as $action) {
            foreach ($permissions as $permission) {
                if(in_array($permission,['role','permission','tarif','user']))
                    continue;
                $vendeurPermissions[] = $permission.'.'.$action;
            }
        }
        $roleAdmin->givePermissionTo($fullPermissions);
        $roleGestionnaire->givePermissionTo($GestionnairePermissions);
        $roleLivreur->givePermissionTo($livreurPermissions);
        $roleVendeur->givePermissionTo($vendeurPermissions);


        //  $role->syncPermissions($fullPermissions);
        // $user = User::find(1);
        // $user->assignRole($role);
    }
}
