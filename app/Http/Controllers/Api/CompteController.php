<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Http\Requests\Api\OpenAccountRequest;
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

    //cette fonction permet a un client d'ouvrir un compte
    public function openAccount(OpenAccountRequest $request){
        $compte=$request->user()->Accounts()->where("status","actif")->first();
        if($compte==null || !($compte['type']==$request->type) ){
            $account=DB::transaction(function () use ($request){
                return Account::create([
                    'user_id'=>$request->user()->id,
                    'type'=>$request->type,
                    'balance'=>'0',
                    'status'=>'actif',
                    'account_number'=>$this->generateAccountNumber()
                ]);
            });
            return response()->json($account,201);
        }
        return response()->json([
            "message"=> 'vous ne pouvez pas creer ce type de compte car, il existe deja !'
        ]);
    }



    //cette fonction permet a un client de voir tous ses comptes
    public function displayAllAccounts(Request $request){
        $query=$accountsUser=$request->user()->Accounts()->orderBy('created_at','desc')->get();
        return response()->json([
            'message'=>$query
        ]);
    }

    //cette fonction permet a un client de voir les details sur son compte 
    public function displayMyInformationAccount(Request $request, $id){
        $account=Account::find($id);
        if(!$account){
            return response()->json([
                'message'=>"compte introuvé !"
            ]);
        }

        if($account->user_id != $request->user()->id){
            return response()->json([
                'message'=> ' Accès non autorisé !'
            ]);
        }
        return response()->json($account);
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
