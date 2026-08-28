<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Mail\TwoFactorCodemail;
use App\Models\TwoFactorCode;
use App\Models\User;
use App\Notifications\LoginOtpNotification;
use BcMath\Number;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Authentification extends Controller
{
    function register(RegisterRequest $request){
        $validatedData=$request->validated();

        $user=User::create([
            'first_name'=>$validatedData['first_name'],
            'last_name'=>$validatedData['last_name'],
            'email'=>$validatedData['email'],
            'password'=>Hash::make($validatedData['password']),
            'phone'=>$validatedData['phone'],
            'status'=>'actif',
            'role'=>$validatedData['role'] ?? 'client',
        ]);

        // generation du token
        $token=$user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status'=>true,
            'message'=>'utilisateur inscrit avec succès',
            'access_token'=>$token,
            'token_type'=>'Bearer',
            'user'=>$user
        ],201);

    }

    //fonction qui permet de connecter un utilisateur
    function login(LoginRequest $request){
        $validatedData=$request->validated();
        $dataUser=User::where('email', $validatedData['email'])->first();

        if(!$dataUser || !(Hash::check($request["password"],$dataUser['password']))){
            return response()->json([
                'back_flash'=>'identifiants incorrects'
            ],401);
        }

        // génération du code puis stockage dans la table two-factor-codes
        // $code = random_int(100000, 999999);
        $code=(int)123456;

        TwoFactorCode::create([
            'user_id' => $dataUser->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(5) //on recupère la date actuelle et on ajoute 5 minutes pour la date d'expiration
        ]);

        // Met à jour la date sans toucher aux autres champs (pas besoin de save() en plus !)
        $dataUser->update([
            'last_login_at' => now()
        ]);

        try {
            // DÉCLENCHEMENT DE LA NOTIFICATION (Mail + BDD)
            $dataUser->notify(new LoginOtpNotification($code));

            $token = $dataUser->createToken('auth_token')->plainTextToken;
            return response()->json([
                'user_id' => $dataUser->id,
                'token' => $token,
                'first_name'=>$dataUser->first_name,
                'profile_photo'=>$dataUser->profile_photo,
                'message' => 'code envoyé par email'
            ]);
        }catch(Exception $e){
            Log::error(" ERREUR ENVOI MAIL OTP : " . $e->getMessage());
        }
    }

    //fonction qui permet de deconnecter un utilisateur
    function logoutUser(Request $request){
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message'=>'utilisateur deconnecté'
        ]);
    }

    // pour reenvoyer le code de verification a deux facteurs
    public function resendToken(Request $request)
    {
        $user = $request->user(); // grâce au token
        $dataUser=User::where('email',$user->email)->first();
        // génération du code puis stockage dans la table two-factor-codes
        $code = random_int(100000, 999999);

        TwoFactorCode::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(5) //on recupère la date actuelle et on ajoute 5 minutes pour la date d'expiration
        ]);

        $hasAccount = $user->accounts()->exists();
        $token = $dataUser->createToken('auth_token')->plainTextToken;
        // DÉCLENCHEMENT DE LA NOTIFICATION (Mail + BDD)
        $dataUser->notify(new LoginOtpNotification($code));


        return response()->json([
            'status'  => true,
            'token' => $token,
            'hasAccount'=> $hasAccount,
            'message' => 'Email vérifié avec succès',
            'user'    => $user
        ]);

    }

    // cette fonction permet de verifier le code de verification a deux facteurs
    public function twoFactorVerify(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string',
        ]);

        $record = TwoFactorCode::where('user_id', $request->user_id)
        ->where('code', $request->token)
        ->where('expires_at', '>', now())
        ->latest()
        ->first();


        if (!$record) {
            return response()->json([
                'status'  => false,
                'message' => 'Token invalide ou expiré'
            ], 400);
        }

        $user = User::find($request->user_id);
        $token = $user->createToken('auth_token')->plainTextToken;

         // Vérifie si l'utilisateur a au moins un compte bancaire
        $hasAccount = $user->accounts()->exists();

        return response()->json([
            'status'  => true,
            'token' => $token,
            'hasAccount'=> $hasAccount,
            'message' => 'Email vérifié.',
            'user'    => $user
        ]);
    }


    //Déconnexion de TOUS les appareils (révoque tous les tokens)
    public function logoutAllDevices(Request $request)
    {
        //Supprime TOUS les tokens enregistrés en base pour cet utilisateur
        $request->user()->tokens()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Vous avez été déconnecté de tous vos appareils avec succès.'
        ], 200);
    }


    //Liste de tous les jetons/appareils actifs (Optionnel)
    public function devices(Request $request)
    {
        $tokens = $request->user()->tokens->map(function ($token) {
            return [
                'id'         => $token->id,
                'name'       => $token->name,
                'last_used'  => $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Jamais',
                'created_at' => $token->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'status'  => true,
            'devices' => $tokens
        ], 200);
    }
}

