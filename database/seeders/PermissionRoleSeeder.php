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


        $otherPermissions = ['action.vendeur','action.livreur','action.gestionnaire'];

        //generate full permissions
        $fullPermissions = [];
        foreach ($actions as $action) {
            foreach ($permissions as $permission) {
                $fullPermissions[] = $permission.'.'.$action;
            }
        }
        $fullPermissions = array_merge($fullPermissions,$otherPermissions);
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
        $GestionnairePermissions[] ='action.gestionnaire';
        $GestionnairePermissions[] ='action.livreur';
        foreach ($actions as $action) {
            foreach ($permissions as $permission) {
                if(in_array($permission,['role','permission','tarif']))
                    continue;
                $GestionnairePermissions[] = $permission.'.'.$action;
            }
        }
        //generate livreur permissions
        $livreurPermissions = [];
        $livreurPermissions[] ='action.livreur';
        foreach ($actions as $action) {

            foreach ($permissions as $permission) {
                if(in_array($permission,['role','permission','tarif','user']))
                    continue;
                $livreurPermissions[] = $permission.'.'.$action;
            }
        }

        //generate vendeur permissions
        $vendeurPermissions = [];
        $vendeurPermissions[] ='action.vendeur';
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
