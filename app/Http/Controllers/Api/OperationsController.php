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

    // Cette fonction permet à un client de faire un dépôt d'argent dans son propre compte
    public function depositeInMyAccount(DepositeRequest $request)
    {
        $user = $request->user();

        // Récupération du compte spécifique transmis dans la requête (ou le compte actif par défaut)
        $accountId = $request->input('account_id') ?? $request->input('source_account_id');

        $compteQuery = $user->accounts()->where("status", "actif");

        if ($accountId) {
            $compteQuery->where('id', $accountId);
        }

        $compte = $compteQuery->first();

        if (!$compte) {
            return response()->json([
                'status' => false,
                'message' => "Aucun compte actif correspondant n'a été trouvé."
            ], 404);
        }

        // Exécution de la transaction en base de données
        $transaction = DB::transaction(function () use ($compte, $request) {

            $soldeAvant = $compte->solde;

            // Mise à jour du solde du compte
            $compte->increment('solde', $request->montant);

            return Transaction::create([
                "account_id"  => $compte->id,
                "type"        => "depot",
                "amount"      => $request->montant,
                "solde_avant" => $soldeAvant,
                "solde_apres" => $compte->fresh()->solde,
                "description" => $request->description ?? 'Dépôt sur le compte',
                "status"      => "validee"
            ]);
        });

        // Notification corrigée (3 arguments : $transaction, $emetteur, $type)
        $emetteur = $user->first_name ? $user->first_name : 'Dépôt Personnel';

        if ($compte->user) {
            $compte->user->notify(new TransactionNotification($transaction, $emetteur, 'CREDIT'));
        }

        return response()->json([
            "status"      => true,
            "message"     => "Dépôt effectué avec succès !",
            "account_id"  => $compte->id,
            "new_balance" => $compte->fresh()->solde,
            "transaction" => $transaction
        ], 200);
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

        //  Récupération DU COMPTE PRÉCIS sélectionné par l'utilisateur
        $senderAccount = $user->accounts()
            ->where('id', $request->source_account_id)
            ->where('status', 'actif')
            ->first();

        if (!$senderAccount) {
            return response()->json([
                'status' => false,
                'message' => "Le compte source est introuvable ou n'est pas actif."
            ], 404);
        }

        //  Récupération du compte destinataire
        $receiverAccount = Account::where('account_number', $request->account_number_dest)->first();

        if (!$receiverAccount || $receiverAccount->status !== "actif") {
            return response()->json([
                'status' => false,
                'message' => "Le compte destinataire n'est pas actif ou est introuvable."
            ], 404);
        }

        //  Interdire le virement vers le MÊME compte exact
        if ($senderAccount->id === $receiverAccount->id) {
            return response()->json([
                'status' => false,
                'message' => "Vous ne pouvez pas effectuer un virement vers le même compte."
            ], 400);
        }

        // Vérification du solde
        if ($senderAccount->solde < $request->amount) {
            return response()->json([
                'status' => false,
                'message' => "Solde insuffisant pour effectuer le virement."
            ], 400);
        }

        //  Plafond journalier
        $totalSentToday = Transaction::where('sender_account_id', $senderAccount->id)
            ->where('type', 'transfert')
            ->whereDate('created_at', today())
            ->sum('amount');

        $plafond = 500000;

        if (($totalSentToday + $request->amount) > $plafond) {
            return response()->json([
                'status' => false,
                'message' => "Plafond journalier de virement dépassé (Limite: {$plafond} FCFA)."
            ], 400);
        }

        // Exécution sécurisée de la transaction
        return DB::transaction(function () use ($senderAccount, $receiverAccount, $request, $user) {

            $soldeAvantSender = $senderAccount->solde;
            $senderAccount->decrement('solde', $request->amount);

            $soldeAvantReceiver = $receiverAccount->solde;
            $receiverAccount->increment('solde', $request->amount);

            $transaction = Transaction::create([
                'sender_account_id'   => $senderAccount->id,
                'receiver_account_id' => $receiverAccount->id,
                'account_id'          => $senderAccount->id,
                'type'                => 'transfert',
                'amount'              => $request->amount,
                'solde_avant'         => $soldeAvantSender,
                'solde_apres'         => $senderAccount->fresh()->solde,
                'description'         => $request->description ?? 'Virement bancaire',
                'status'              => 'validee',
            ]);

            // Nom de l'émetteur pour la notification
            $emetteur = $user->first_name ? $user->first_name : 'Utilisateur inconnu';

            // Envoi de la notification au destinataire (si la relation `user` existe sur Account)
            if ($receiverAccount->user) {
                $receiverAccount->user->notify(new TransactionNotification($transaction, $emetteur, 'CREDIT'));
            }

            return response()->json([
                'status'      => true,
                'message'     => "Virement effectué avec succès.",
                'transaction' => $transaction
            ], 200);
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

    // 1. Vérifier si le compte existe en base de données
    $accountExists = Account::where('account_number', $validated['account_number'])->first();

    if (!$accountExists) {
        return response()->json([
            'status' => false,
            'message' => "Le numéro de compte saisi n'existe pas."
        ], 404);
    }

    // 2. (Optionnel) Empêcher l'utilisateur d'ajouter son propre compte
    if ($accountExists->user_id === $user->id) {
        return response()->json([
            'status' => false,
            'message' => "Vous ne pouvez pas vous ajouter vous-même en bénéficiaire."
        ], 422);
    }

    // 3. Empêcher les doublons dans les bénéficiaires de cet utilisateur
    $exists = Beneficiary::where('user_id', $user->id)
        ->where('account_number', $validated['account_number'])
        ->exists();

    if ($exists) {
        return response()->json([
            'status' => false,
            'message' => "Ce bénéficiaire est déjà enregistré."
        ], 409);
    }

    // 4. Création du bénéficiaire
    $beneficiary = Beneficiary::create([
        'user_id' => $user->id,
        'account_number' => $validated['account_number'],
        'nickname' => $validated['nickname'] ?? null,
    ]);

    return response()->json([
        'status' => true,
        'message' => "Bénéficiaire ajouté avec succès.",
        'beneficiary' => $beneficiary
    ], 201);
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
            'account_number'=>'required | string ',
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
