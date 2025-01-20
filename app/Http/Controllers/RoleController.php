<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = ['guard_name'];
        $query = Role::query();
       /*  $query->with(['permissions' => function ($query) {
            $query->select('name'); // Define specific columns
        }]); */
        $query->with(['permissions:name']);
        foreach ($filters  as $filter) {
           if($request->has($filter) && !empty($request->{$filter})){
            $query->where($filter,$request->{$filter});
           }
        }
        $permissions = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $permissions->items(),
            'total' => $permissions->total(),
        ]);
    }

    public function permissions(){
        $permissions = Permission::all()->pluck('name');

        $result = collect($permissions)
        ->groupBy(function ($item) {
            return explode('.', $item)[0]; // Group by the "subject" (e.g., 'permission' or 'role')
        })
        ->map(function ($group, $subject) {
            return [
                'subject' => $subject,
                'actions' => $group->map(function ($action) use ($subject) {
                    $actionName = explode('.', $action)[1];
                    return [
                        'value' => $action,
                        'label' => $actionName,
                    ];
                })->values(),
            ];
        })
        ->values()
        ->toArray();
        return $result;
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info('new role : '.json_encode($request->all()));
        $role = Role::create(['name' =>$request->name]);
        $role->syncPermissions($request->permissions);
        return 'Role bien ajouté';
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        Log::info('update role : '.json_encode($request->all()));
        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();
        $role->syncPermissions($request->permissions);
        return 'Role bien Modifié';
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
