<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class StoryController extends Controller
{
   public function historyOperations(Request $request)
{
    $user = $request->user();

    // 1. Récupérer spécifiquement le COMPTE COURANT de l'utilisateur
    $account = $user->accounts()->where('type', 'courant')->first();

    if (!$account) {
        return response()->json([
            'status'  => false,
            'message' => 'Aucun compte courant trouvé pour cet utilisateur.'
        ], 404);
    }

    // 2. Requête sur les transactions liées UNIQUEMENT à ce compte courant
    $query = Transaction::with(['senderAccount.user', 'receiverAccount.user'])
        ->where(function ($q) use ($account) {
            $q->where('sender_account_id', $account->id)   // Virement sortant
              ->orWhere('receiver_account_id', $account->id) // Virement entrant ou Dépôt
              ->orWhere('account_id', $account->id);          // Opérations directes sur le compte (Dépôt/Retrait)
        })
        // On restreint aux types d'opérations souhaités (dépôts et virements)
        ->whereIn('type', ['retrait', 'depot', 'transfert']);

    // Filtre par date de début
    if ($request->filled('start_date')) {
        $query->whereDate('created_at', '>=', $request->start_date);
    }

    // Filtre par date de fin
    if ($request->filled('end_date')) {
        $query->whereDate('created_at', '<=', $request->end_date);
    }

    // Tri de la plus récente à la plus ancienne
    $query->orderBy('created_at', 'desc');

    // Pagination
    $page = $request->get('page', 3);
    $transactions = $query->paginate($page);

    // 3. Formater les données pour le Frontend Angular
    $transactions->getCollection()->transform(function ($txn) use ($account) {

        // C'est un DÉBIT (-) uniquement si le compte courant est l'émetteur (sender)
        $isDebit = ($txn->sender_account_id === $account->id);

        // Détermination du partenaire selon le sens de l'opération
        $partenaire = 'Banque / Dépôt';

        if ($isDebit) {
            // Virement envoyé à quelqu'un d'autre
            $partenaire = $txn->receiverAccount?->user?->name ?? 'Destinataire externe';
        } else {
            // Virement reçu d'un autre utilisateur
            if ($txn->senderAccount && $txn->senderAccount->user_id !== $account->user_id) {
                $partenaire = $txn->senderAccount->user->name ?? 'Expéditeur inconnu';
            }
        }

        return [
            'id'          => $txn->id,
            'reference'   => $txn->reference,
            'sens'        => $isDebit ? 'DEBIT' : 'CREDIT',
            'montant'     => ($isDebit ? '-' : '+') . $txn->amount,
            'type'        => $txn->type,
            'description' => $txn->description,
            'statut'      => $txn->status,
            'date'        => $txn->created_at->format('Y-m-d H:i:s'),
            'partenaire'  => $partenaire,
        ];
    });

    return response()->json([
        'status' => true,
        'data'   => $transactions
    ]);
}
}
