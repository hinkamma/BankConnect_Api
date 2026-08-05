<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Api\DepositeRequest;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Http\Requests\Api\ModeyWithdrawalRequest;
use App\Http\Requests\Api\TransfertRequest;
use App\Http\Requests\Api\VirementRequest;
use App\Models\Account;
use App\Http\Requests\VerifyAmountRequest;
use App\Models\ScheduledTransfer;
use App\Http\Requests\Api\ProgrammerVirementRequest;

class OperationsController extends Controller

{
    //cette fonction permet a un client de faire un depot d'argent dans son autre compte
    public function depositeInMyAccount(DepositeRequest $request){
        $compte = $request->user()->Accounts()->where("status","actif")->first();
        if(!$compte){
            return response()->json(["message"=>"aucun compte actif trouvé"]);
        }

        $compte->solde = $compte->solde + $request->montant;
        $compte->save();

        DB::transaction(function () use ($compte, $request) {
            
            Transaction::create([
                "account_id"   => $compte->id,
                "type"         => "depot",
                "amount"       => $request->montant,
                "solde_avant"  => $compte->solde - $request->montant,
                "solde_apres"  => $compte->solde,
                "description"  => $request->description,
                "status"       => "validee"
            ]);
            
        });

        return response()->json([
            "message"     => "depot effectué avec success !",
            "account_id"  => $compte->id,
            "new_balance" => $compte->solde,
        ]);
    }



    //cette fonction permet a un client de retirer de l'agent dans son compte
    public function MoneyWithdrawal(ModeyWithdrawalRequest $request){
        $compte=$request->user()->Accounts()->where("status","actif")->first();
        if(!$compte){
            return response()->json(["message"=>"aucun compte actif trouvé"]);
        }

        if($compte->solde<=0){
            return response()->json(["message"=>"votre solde est épuisé"]);
        }

        if($compte->solde<$request->montant){
            return response()->json(["message"=>"le compte est insuffisant"]);
        }
       
        $compte->solde=$compte->solde-$request->montant;
        $compte->save();

        DB::transaction(function () use ($compte, $request) {
            
            Transaction::create([
                "account_id"   => $compte->id,
                "type"         => "retrait",           // ← Correction importante
                "amount"       => $request->montant,
                "solde_avant"  => $compte->solde + $request->montant,
                "solde_apres"  => $compte->solde,
                "description"  => $request->description,
                "status"       => "validee"
            ]);
            
        });

        return response()->json([
            "message"=>"depot effectué avec success !",   // ← Tu peux aussi corriger ce message
            "account_id"=>$compte->id,
            "new_balance"=>$compte->solde,
        ]);
    }



    public function effectuerVirement(VirementRequest $request)
    {
        $user = $request->user();
        $senderAccount = $user->Accounts()->where('status','actif')->first();

        if(!$senderAccount){
            return response()->json([
                'status'=>false,
                'message'=>"votre compte n'est pas actif ou est introuvable"
            ]);
        }

        $receiverAccount = Account::where('account_number',$request->account_number_dest)->first();
        if(!$receiverAccount || $receiverAccount->status !== "actif"){
            return response()->json([
                'status'=>false,
                'message'=>"le compte destinataire n'est pas actif ou est introuvable"
            ]);
        }

        if($senderAccount->account_number === $receiverAccount->account_number){
            return response()->json([
                'status'=>false,
                'message'=>"vous ne pouvez pas effectuer un virement vers votre propre compte"
            ]);
        }

        if($senderAccount->solde < $request->amount){
            return response()->json([
                'status'=>false,
                'message'=>"solde insuffisant pour effectuer le virement"
            ]);
        }

        // plafond journalier
        $totalSentToday = Transaction::where('sender_account_id', $senderAccount->id)
            ->where('type', 'transfert')
            ->whereDate('created_at', today())
            ->sum('amount');

        $plafond = 500000; // à adapter selon votre logique métier

        if($totalSentToday + $request->amount > $plafond){
            return response()->json([
                'status'=>false,
                'message'=>"plafond journalier de virement dépassé"
            ]);
        }

        // exécution de la transaction sécurisée
        return DB::transaction(function () use ($senderAccount, $receiverAccount, $request) {

            $soldeAvantSender = $senderAccount->solde;
            $senderAccount->decrement('solde', $request->amount);

            $soldeAvantReceiver = $receiverAccount->solde;
            $receiverAccount->increment('solde', $request->amount);

            $transaction = Transaction::create([
                'sender_account_id' => $senderAccount->id,
                'receiver_account_id' => $receiverAccount->id,
                'account_id' => $senderAccount->id,
                'type' => 'transfert',
                'amount' => $request->amount,
                'solde_avant' => $soldeAvantSender,
                'solde_apres' => $senderAccount->fresh()->solde,
                'description' => $request->description ?? 'virement',
                'status' => 'validee',
            ]);

            return response()->json([
                'status'=>true,
                'message'=>"virement effectué avec succès",
                'transaction'=>$transaction
            ]);
        });
    }
    
    // cette fonction permet a un utilisateur de verifier son compte
    public function verifyAmount(VerifyAmountRequest $request){
        $compte=$request->user()->Accounts()->first();
        if($compte->user_id !=$request->user()->id){
            return response()->json(["message","accès non autorisé"]);
        }
        return response()->json(["message"=>$compte->solde]);

    }


    // Créer un virement programmé
    public function faireVirementProgramme(ProgrammerVirementRequest $request)
    {
        $user = $request->user();
        $senderAccount = $user->Accounts()->where('status', 'actif')->first();

        if (!$senderAccount) {
            return response()->json([
                'status' => false,
                'message' => "votre compte n'est pas actif ou est introuvable"
            ]);
        }

        $receiverAccount = Account::where('account_number', $request->account_number_dest)->first();
        if (!$receiverAccount || $receiverAccount->status !== "actif") {
            return response()->json([
                'status' => false,
                'message' => "le compte destinataire n'est pas actif ou est introuvable"
            ]);
        }

        if ($senderAccount->account_number === $receiverAccount->account_number) {
            return response()->json([
                'status' => false,
                'message' => "vous ne pouvez pas programmer un virement vers votre propre compte"
            ]);
        }


        $scheduledTransfer = ScheduledTransfer::create([
            'sender_account_id' => $senderAccount->id,
            'receiver_account_id' => $receiverAccount->id,
            'amount' => $request->amount,
            'description' => $request->description,
            'scheduled_date' => $request->scheduled_date,
            'status' => 'en_attente',
        ]);

        return response()->json([
            'status' => true,
            'message' => "virement programmé avec succès",
            'scheduled_transfer' => $scheduledTransfer
        ]);
    }

    // Lister les virements programmés de l'utilisateur connecté
    public function ListerVirementsProgrammes(Request $request)
    {
        $user = $request->user();
        $senderAccount = $user->Accounts()->where('status', 'actif')->first();

        $scheduledTransfers = ScheduledTransfer::where('sender_account_id', $senderAccount->id)
            ->orderBy('scheduled_date')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $scheduledTransfers
        ]);
    }

    // Annuler un virement programmé (seulement s'il est encore en attente)
    public function anullerVirementProgramme(Request $request, ScheduledTransfer $scheduledTransfer)
    {
        $user = $request->user();
        $senderAccount = $user->Accounts()->where('status', 'actif')->first();

        if ($scheduledTransfer->sender_account_id !== $senderAccount->id) {
            return response()->json([
                'status' => false, 
                'message' => "non autorisé"]
            ,403);
        }

        if ($scheduledTransfer->status !== 'en_attente') {
            return response()->json([
                'status' => false,
                'message' => "seul un virement en attente peut être annulé"
            ]);
        }

        $scheduledTransfer->update(['status' => 'annulee']);

        return response()->json([
            'status' => true,
            'message' => "virement programmé annulé"
        ]);
    }
}
