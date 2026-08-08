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
        $account = $user->Accounts()->first();

        if (!$account) {
            return response()->json(['status' => false, 'message' => 'Compte introuvable.'], 440);
        }

        // Requête de base : toutes les transactions liées au compte (émetteur OU destinataire)
        $query = Transaction::with(['senderAccount.user', 'receiverAccount.user'])
            ->where(function ($q) use ($account) {
                $q->where('sender_account_id', $account->id)
                ->orWhere('receiver_account_id', $account->id);
            });

        //Filtre par date de début (ex: 2026-07-30)
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        // 🔍 Filtre par date de fin (ex: 2026-08-04)
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filtre par type (virement, depot, retrait)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Taper la plus récente d'abord
        $query->orderBy('created_at', 'desc');

        // Pagination (15 par page par défaut, personnalisable)
        $perPage = $request->get('page', 15);
        $transactions = $query->paginate($perPage);
    
        // Formater les données pour distinguer facilement les Crédits des Débits
        $transactions->getCollection()->transform(function ($txn) use ($account) {
            $isDebit = ($txn->sender_account_id === $account->id);
            return [
                'id'          => $txn->id,
                'reference'   => $txn->reference,
                'sens'        => $isDebit ? 'DEBIT' : 'CREDIT',
                'montant'     => ($isDebit ? '-' : '+') . $txn->amount,
                'type'        => $txn->type,
                'description' => $txn->description,
                'statut'      => $txn->status,
                'date'        => $txn->created_at->format('Y-m-d H:i:s'),
                'partenaire'  => $isDebit ? ($txn->receiverAccount->user->name ?? 'N/A') : ($txn->senderAccount->user->name ?? 'N/A'),
            ];
        });

        return response()->json([
            'status' => true,
            'data'   => $transactions
        ]);
    }

    /**
     * 2. Exporter l'historique en CSV
     */
    public function exportCsv(Request $request)
    {
        $user = $request->user();
        $account = $user->account;

        $query = Transaction::where(function ($q) use ($account) {
            $q->where('sender_account_id', $account->id)
              ->orWhere('receiver_account_id', $account->id);
        });

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')->get();

        $fileName = 'historique_transactions_' . date('Y-m-d') . '.csv';

        $headers = [
            "Content-type"        => "text/csv; charset=UTF-8",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function () use ($transactions, $account) {
            $file = fopen('php://output', 'w');
            // Bom UTF-8 pour le bon affichage des accents dans Excel
            fputs($file, "\xEF\xBB\xBF");

            // En-têtes du fichier CSV
            fputcsv($file, ['Référence', 'Date', 'Type', 'Sens', 'Montant (FCFA)', 'Description']);

            foreach ($transactions as $txn) {
                $isDebit = ($txn->sender_account_id === $account->id);
                fputcsv($file, [
                    $txn->reference,
                    $txn->created_at->format('Y-m-d H:i:s'),
                    $txn->type,
                    $isDebit ? 'Débit' : 'Crédit',
                    ($isDebit ? '-' : '+') . $txn->amount,
                    $txn->description ?? ''
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
