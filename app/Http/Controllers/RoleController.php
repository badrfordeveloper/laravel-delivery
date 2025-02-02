<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $selectsFilters = [];
        $textFilters = ['name'];
        $query = Role::query()->excludeAdmin();
       /*  $query->with(['permissions' => function ($query) {
            $query->select('name'); // Define specific columns
        }]); */
        $query->with(['permissions:name']);
        foreach ($selectsFilters  as $filter) {
           if($request->has($filter) && !empty($request->{$filter})){
            $query->where($filter,$request->{$filter});
           }
        }
        foreach ($textFilters  as $filter) {
            if($request->has($filter) && !empty($request->{$filter})){
             $query->where($filter,'like',$request->{$filter}."%");
            }
         }



        $query->orderBy('id','desc');
        $roles = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $roles->items(),
            'total' => $roles->total(),
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
    public function rolesList(){
       // return Role::excludeAdmin()->get(['id as value','name as title']);
        return Role::get(['name as value','name as title']);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'unique:roles'],
        ]);
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
        $request->validate([
            'name' => [
                'required',
                Rule::unique('roles')->ignore($id), // Exclude current role ID
            ]
        ]);
        Log::info('update role : '.json_encode($request->all()));
        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();
        $role->syncPermissions($request->permissions);



        // logout all users with this role
        $users = User::role($request->name)->get();
        foreach ($users as $user) {
            $user->tokens()->delete(); // Works for Sanctum and Passport
        }
        return 'Role bien Modifié';
    }
}
