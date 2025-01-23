<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

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
                $role_id =  $request->{$filter};
                $query->whereHas('roles', function($query) use ($role_id ) {
                    $query->where('id',$role_id);
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
            else if($request->has($filter) && !empty($request->{$filter})){
             $query->where($filter,'like',$request->{$filter}."%");
            }
         }



        $query->orderBy('id','desc');
        $users = $query->paginate($request->itemsPerPage);
        return response()->json([
            'items' => $users->items(),
            'total' => $users->total(),
        ]);
    }
}
