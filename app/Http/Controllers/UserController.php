<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $selectsFilters = ["active" =>"active" ,"role" => "roles.id"];
        $textFilters = ['fullName'];
       // $query = User::query()->excludeAdmin();
        $query = User::query();
        $query->with(['roles:name']);
        foreach ($selectsFilters  as $filter => $filterValue) {
            if($filter == "role" && $request->has($filter) && !empty($request->{$filter})){
                $role =  $request->{$filter};
                $query->whereHas('roles', function($query) use ($role ) {
                    $query->where('name',$role);
                });
            }
            else if($request->has($filter) && !empty($request->{$filter})){
                $query->where($filterValue,$request->{$filter});
            }
        }
        foreach ($textFilters  as $filter) {
            if($filter =="fullName" && $request->has($filter) && !empty($request->{$filter})){
                $query->where('users.lastName','like',$request->{$filter}."%")
                        ->orWhere('users.firstName','like',$request->{$filter}."%");
            }
         }
        $query->orderBy('id','desc');
        $users = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $users->items(),
            'total' => $users->total(),
        ]);
    }

    public function store(Request $request)
    {
        Log::info('new user : '.json_encode($request->all()));

        $request->validate([
            'lastName' => ['required'],
            'firstName' => ['required'],
            'email' => 'required|unique:users',
            'password' => 'required|min:8',
            'active' => ['required'],
            'phone' => ['required'],
            'role' => [
                'required',
                Rule::notIn(['admin']),
            ],
        ]);

        $user = new User();
        $user->lastName = $request->lastName;
        $user->firstName = $request->firstName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->active = $request->boolean('active');
        $user->phone = $request->phone;
        $user->store_name = $request->store_name;
        $user->ville = $request->ville;
        $user->address = $request->address;
        $user->bank_name = $request->bank_name;
        $user->rib = $request->rib;
        $user->save();
        $user->assignRole( $request->role);
        return 'Utilisateur bien ajouté';
    }

    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);
        return [
            'lastName' => $user->lastName,
            'firstName' => $user->firstName,
            'email' => $user->email,
            'active' => $user->active,
            'phone' =>$user->phone,
            'store_name' => $user->store_name,
            'ville' => $user->ville,
            'address' => $user->address,
            'bank_name' => $user->bank_name,
            'rib' => $user->rib,
            'role' => $user->roles->pluck('name')[0] ?? "",
        ];
    }

    public function update(Request $request, string $id)
    {

        $request->validate([
            'lastName' => ['required'],
            'firstName' => ['required'],
            'email' => [
                'required',
                Rule::unique('users')->ignore($id), // Exclude current user ID
            ],
            'password' => 'nullable|min:8',
            'active' => ['required'],
            'phone' => ['required'],
            'role' => [
                'required',
                Rule::notIn(['admin']),
            ],
        ]);

        Log::info('update user : '.json_encode($request->all()));
        $user = User::findOrFail($id);
        $user->lastName = $request->lastName;
        $user->firstName = $request->firstName;
        $user->email = $request->email;
        if($request->has('password') && !empty($request->password) ){
            $user->password = bcrypt($request->password);
        }
        $user->active = $request->boolean('active');
        $user->phone = $request->phone;
        $user->store_name = $request->store_name;
        $user->ville = $request->ville;
        $user->address = $request->address;
        $user->bank_name = $request->bank_name;
        $user->rib = $request->rib;
        $user->save();
        $user->syncRoles([$request->role]);

        // logout user
        $user->tokens()->delete();

        return 'Utilisateur bien Modifié';
    }
}
