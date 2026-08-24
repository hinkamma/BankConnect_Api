<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Http\Requests\Api\OpenAccountRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompteController extends Controller
{
    // cette fonction permet de generer un numero de compte unique
    private function generateAccountNumber(){
        do{
            $number='BNK'. str_pad(random_int(0,99999999),8,'0',STR_PAD_LEFT);
        }while(Account::where('account_number',$number)->exists());
        return $number;
    }



    //cette fonction permet a un utilisateur pournla poremiere fois de selectionner le type de compte
    public function select_type_compte(Request $request ){
        $request->validate([
            'type_compte' => 'required|in:courant,epagne,pro',
        ]);

        $user = $request->user();

        // Empêche de créer un compte du même type en double
        $existing = $user->accounts()->where('type', $request->type_compte)->first();
        if ($existing) {
            return response()->json(['message' => 'Vous avez déjà un compte de ce type.'], 422);
        }

        $account = $user->accounts()->create([
            'type' => $request->type_compte,
            'balance' => 0,
            'account_number' => $this->generateAccountNumber(),
            'status' => 'actif',
        ]);

        return response()->json([
            'message' => 'Compte créé avec succès.',
            'account' => $account,
        ]);
    }

    // Cette fonction permet à un client d'ouvrir un compte
    public function openAccount(OpenAccountRequest $request)
    {
        // VERIFICATION : Existe-t-il DÉJÀ un compte actif pour CE type précis ?
        $hasExistingType = $request->user()->Accounts()
            ->where('type', $request->type)
            ->where('status', 'actif')
            ->exists();

        if (!$hasExistingType) {
            $account = DB::transaction(function () use ($request) {
                return Account::create([
                    'user_id' => $request->user()->id,
                    'type' => $request->type,
                    'balance' => '0',
                    'status' => 'actif',
                    'account_number' => $this->generateAccountNumber()
                ]);
            });

            return response()->json($account, 201);
        }

        return response()->json([
            "message" => "Vous ne pouvez pas créer ce type de compte car il existe déjà !"
        ], 422); //  Code HTTP 422 pour signaler l'erreur de validation
    }


    //cette fonction permet a un client de voir tous ses comptes
    public function displayAllAccounts(Request $request){
        $query=$accountsUser=$request->user()->Accounts()->orderBy('created_at','desc')->get();
        return response()->json([
            'message'=>$query
        ]);
    }

    //cette fonction permet a un client de voir les details sur son compte

    public function displayMyInformationAccount(Request $request,$id)
    {
        $account_with_user = Account::with('user')->where('user_id', $id)->get();
        if (!$account_with_user) {
            return response()->json([
                'message' => 'Compte non trouvé'
            ], 404);
        }

        return response()->json([
            'data' => $account_with_user
        ]);
    }


    // cete fonction permet  la demande de fermeture dun compte par l'utilisateur
    public function closeAccount(Request $request, $id){
        $account=Account:: find($id);

        if($account->user_id !=$request->user()->id ){
            return response()->json([
                "message"=> "Ce compte est non autorisé"
            ]);
        }
        if($account->solde !=0){
            return response()->json([
                "message"=>"vider le compte avant de le fermer"
            ]);
        }
        if($account->status=="fermer"){
            return response()->json([
                "message"=>"compte fermer"
            ]);
        }
        $account->status="fermer";
        $account->save();
        return response()->json([
            "message"=>"compte fermer avec succèss!"
        ]);
    }


    //cette function permet a un admin de bloquer un compte
     public function toBlockAccount(Request $request, $id){
        $account=Account::find($id);

        if($request->role!='admin'){
            return response()->json(["message"=>"impossible de bloquer le compte"]);
        }

        if($account->status=="fermer"){
            return response()->json(["message"=>"impossible de bloquer le compte"]);
        }
        $account->status="bloquer";
        $account->save();
        return response()->json(["message"=>"compte bloqué"]);
    }


    //cette function permet a un admin de debloquer un compte
     public function unblockAccount(Request $request, $id){
        $account=Account::find($id);

        if($request->role!='admin'){
            return response()->json(["message"=>"impossible de debloquer le compte"]);
        }

        if($account->status=="fermer"){
            return response()->json(["message"=>"impossible de debloquer le compte"]);
        }
        $account->status="actif";
        $account->save();
        return response()->json(["message"=>"compte débloqué"]);
    }

}
