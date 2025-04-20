<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
    * Create user
    *
    * @param  [string] name
    * @param  [string] email
    * @param  [string] password
    * @param  [string] password_confirmation
    * @return [string] message
    */
    public function register(Request $request)
    {
        Log::info('register new user : '.json_encode($request->all()));

        $request->validate([
            'lastName' => ['required'],
            'firstName' => ['required'],
            'email' => 'required|unique:users',
            'password' => 'required|min:6',
            'phone' => ['required'],
            'ville' => ['required'],
            'store' => ['nullable','unique:users'],
            'role' => [
                'required',
                Rule::in(['vendeur','livreur']),
            ],
        ]);

        $user = new User();
        $user->lastName = $request->lastName;
        $user->firstName = $request->firstName;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->active = false;
        $user->phone = $request->phone;
        $user->store = $request->store;
        $user->ville = $request->ville;
        $user->save();
        $user->assignRole( $request->role);

        return 'Utilisateur bien ajouté';
    }
    public function login(Request $request)
    {
        $request->validate([
        'email' => 'required|string|email',
        'password' => 'required|string',
        'remember_me' => 'boolean'
        ]);

        $credentials = request(['email','password']);
        if(!Auth::attempt($credentials))
        {
            return response()->json([
                'message' => 'Email ou mot de passe est incorrect'
            ],422);
        }

        $user = $request->user();

        if(!$user->active){
            return response()->json(['message' => 'Votre compte est inactif. Veuillez contacter le support.'], Response::HTTP_FORBIDDEN);
        }



        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->plainTextToken;

        $permissions =  $user->getPermissionsViaRoles()->pluck('name');

        return response()->json([
        'accessToken' =>$token,
        'userData' => $user->only('name'),
        'userAbilityRules' => $this->renderPermissions( $permissions ),
        ]);
    }

    public function renderPermissions($permissions){
        $result = [];
        foreach ($permissions as $permission) {
            [$subject, $action] = explode('.', $permission);
            $result[] = [
                'subject' => $subject,
                'action' => $action,
            ];
        }
        return $result;
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
        'message' => 'Successfully logged out'
        ]);

    }
}
