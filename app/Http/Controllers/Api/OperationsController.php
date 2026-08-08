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
use App\Models\Beneficiary;
use App\Http\Requests\Api\AddbeneficiaireRequest;
use App\Http\Requests\Api\UpdateBeneficiaireRequest;
use App\Http\Requests\Api\DeleteBeneficiaireRequest;
use App\Http\Requests\Api\ListerBeneficiaireRequest;
use App\Notifications\TransactionNotification;

class OperationsController extends Controller

{
    //cette fonction permet a un client de faire un depot d'argent dans son autre compte
    public function depositeInMyAccount(DepositeRequest $request){
        $compte = $request->user()->Accounts()->where("status","actif")->first();
        if(!$compte){
            return response()->json(["message"=>"aucun compte actif trouvé"]);
        }

        // 1. Assigne la transaction à la variable $transaction
        $transaction = DB::transaction(function () use ($compte, $request) {
            
            // On met à jour le solde du compte
            $compte->solde += $request->montant;
            $compte->save();

            return Transaction::create([
                "account_id"   => $compte->id,
                "type"         => "depot",
                "amount"       => $request->montant,
                "solde_avant"  => $compte->solde - $request->montant,
                "solde_apres"  => $compte->solde,
                "description"  => $request->description,
                "status"       => "validee"
            ]);
        });

        // 3. $transaction n'est plus NULL, la notification fonctionne !
        $compte->user->notify(new TransactionNotification($transaction, 'CREDIT'));


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
       
        

        $transaction = DB::transaction(function () use ($compte, $request) {
        
            $compte->solde=$compte->solde-$request->montant;
            $compte->save();        

            return Transaction::create([
                "account_id"   => $compte->id,
                "type"         => "retrait",           // ← Correction importante
                "amount"       => $request->montant,
                "solde_avant"  => $compte->solde + $request->montant,
                "solde_apres"  => $compte->solde,
                "description"  => $request->description,
                "status"       => "validee"
            ]);
            
        });
        $compte->user->notify(new TransactionNotification($transaction, 'DEBIT'));

        return response()->json([
            "message"=>"retrait effectué avec succès !",   // ← Tu peux aussi corriger ce message
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

            $emetteur = auth()->user()->first_name ? auth()->user()->first_name: 'Utilisateur inconnu'; // La personne connectée qui fait le virement
            
            // 3. $transaction n'est plus NULL, la notification fonctionne !
            $receiverAccount->user->notify(new TransactionNotification($transaction,$emetteur, 'CREDIT'));

            return response()->json([
                'status'=>true,
                'message'=>"virement effectué avec succès",
                'transaction'=>$transaction
            ]);
        });
    }
    
    // cette fonction permet a un utilisateur de verifier son compte
    public function verifyAmount(Request $request){
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

    // CREATE : ajouter un bénéficiaire
    public function addbeneficiaire(AddbeneficiaireRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        // empêcher les doublons pour cet utilisateur
        $exists = Beneficiary::where('user_id', $user->id)
            ->where('account_number', $validated['account_number'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => "ce bénéficiaire est déjà enregistré"
            ]);
        }

        $beneficiary = Beneficiary::create([
            'user_id' => $user->id,
            'account_number' => $validated['account_number'],
            'nickname' => $validated['nickname'],
        ]);

        return response()->json([
            'status' => true,
            'message' => "bénéficiaire ajouté avec succès",
            'beneficiary' => $beneficiary
        ]);
    }

    // READ : lister tous les bénéficiaires de l'utilisateur connecté
    public function ListerAllBeneficiaireToUser(Request $request)
    {
        $beneficiaries = Beneficiary::where('user_id', $request->user()->id)
            ->orderBy('nickname')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $beneficiaries
        ]);
    }


    // UPDATE : modifier un bénéficiaire (typiquement le surnom)
    public function updatebeneficiaire(Request $request, Beneficiary $beneficiary)
    {
        // sécurité : vérifier que ce bénéficiaire appartient bien à l'utilisateur connecté
        if ($beneficiary->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => "non autorisé"], 403);
        }

        $validated = $request->validate([
            'nickname' => 'required|string|max:255',
        ]);

        $beneficiary->update($validated);

        return response()->json([
            'status' => true,
            'message' => "bénéficiaire mis à jour",
            'beneficiary' => $beneficiary
        ]);
    }

    // DELETE : supprimer un bénéficiaire
    public function deletebeneficiaire(Request $request, Beneficiary $beneficiary)
    {
        if ($beneficiary->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => "non autorisé"], 403);
        }

        $beneficiary->delete();

        return response()->json([
            'status' => true,
            'message' => "bénéficiaire supprimé"
        ]);
    }
}
