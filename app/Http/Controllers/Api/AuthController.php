<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;


class AuthController extends Controller
{
    // fonction qui permet d'inscrire un utilisateur
    // function register(RegisterRequest $request){
    //     $user=User::create([
    //         'name'=>$request->name,
    //         'email'=>$request->email,
    //         'phone'=>$request->phone,
    //         'password'=>Hash::make($request->password),
    //         'role'=>'client',
    //         'status'=>'actif'
    //     ]);
        
    //     // generont lui alors un token qu'il utilisera
    //     $token=$user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         $user=$user,
    //         $token=$token
    //     ],201);
    // }

    //fonction qui permet de connecter un utilisateur
    // function login(Request $request){
    //     $user=$request->validate([
    //         'email'=>'email|string|required',
    //         'password'=>'string|required'
    //     ]);

    //     $dataUser=User::where('email',$user['email'])->first();
        
    //     if(!$dataUser || !(Hash::check($request["password"],$dataUser['password']))){
    //         return response()->json([
    //             'back_flash'=>'identifiants incorrects'
    //         ],401);
    //     }

    //     $token=$dataUser->createToken('auth_token')->plainTextToken;
    //     return response()->json([
    //         'user'=>$user,
    //         'token'=>$token
    //     ]);
    // }

    //fonction qui permet de deconnecter un utilisateur
    // function logout(Request $request){
    //     $request->user()->currentAccessToken()->delete();
    //     return response()->json([
    //         'message'=>'utilisateur deconnecté'
    //     ]);
    // }
}
