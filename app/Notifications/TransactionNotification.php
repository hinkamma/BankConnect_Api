<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Transaction;
use App\Models\User;
class TransactionNotification extends Notification
{
   use Queueable;

    protected $transaction;
    protected $type; // 'CREDIT' ou 'DEBIT'
    protected $emetteur;

    public function __construct(Transaction $transaction,$emetteur, string $type)
    {
        $this->transaction = $transaction;
        $this->type = $type;
        $this->emetteur=$emetteur;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $amountFormatted = number_format($this->transaction->amount, 0, ',', ' ') . ' FCFA';
        $typeTxn = $this->transaction->type; // 'depot', 'retrait' ou 'virement'
        $isCredit = ($this->type === 'CREDIT');

        if ($typeTxn === 'depot') {
            $subject = "Dépôt effectué : {$amountFormatted}";
            $message = "Un dépôt de {$amountFormatted} a été crédité sur votre compte.";
            $nouveauSolde = number_format($this->transaction->account->solde, 0, ',', ' ') . ' FCFA';
        } elseif ($typeTxn === 'retrait') {
            $subject = "Retrait effectué : {$amountFormatted}";
            $message = "Un retrait de {$amountFormatted} a été effectué sur votre compte.";
            $nouveauSolde = number_format($this->transaction->account->solde, 0, ',', ' ') . ' FCFA';
        } else {
            $subject = $isCredit ? "Crédit reçu : {$amountFormatted}" : "Débit effectué : {$amountFormatted}";
            $message = $isCredit 
                ? "Vous avez reçu un virement de {$amountFormatted}." 
                : "Un virement de {$amountFormatted} a été effectué depuis votre compte.";
            $emetteur = $isCredit ? $this->transaction->senderAccount->user->name : $this->transaction->receiverAccount->user->name;
            $message .= $isCredit ? " De: {$this->emetteur}." : " Destinataire : {$emetteur}.";
            
            $nouveauSolde = number_format($this->transaction->account->solde, 0, ',', ' ') . ' FCFA';
        }

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Bonjour {$notifiable->name},")
            ->line($message)
            ->line("Référence : " . $this->transaction->reference)
            

            //  AJOUT DU NOUVEAU SOLDE ICI !
            ->line('Votre nouveau solde est de : ' . $nouveauSolde)
            ->action('Voir mes transactions', url('/dashboard'))
            ->line('Merci d’utiliser BankConnect !');

    
    }

    public function toArray($notifiable): array
    {
        $amountFormatted = number_format($this->transaction->amount, 0, ',', ' ') . ' FCFA';
        $typeTxn = $this->transaction->type;
        $isCredit = ($this->type === 'CREDIT');

        if ($typeTxn === 'depot') {
            $title = "Dépôt sur le compte";
            $msg = "Votre compte a été crédité de {$amountFormatted}.";
        } elseif ($typeTxn === 'retrait') {
            $title = "Retrait du compte";
            $msg = "Vous avez retiré {$amountFormatted}.";
        } else {
            $title = $isCredit ? 'Argent reçu' : 'Virement envoyé';
            $msg = $isCredit ? "Vous avez reçu {$amountFormatted}." : "Vous avez envoyé {$amountFormatted}.";

            
        }

        return [
            'transaction_id' => $this->transaction->id,
            'reference'      => $this->transaction->reference,
            'title'          => $title,
            'message'        => $msg,
            'amount'         => $this->transaction->amount,
            'type'           => $this->type,
        ];
    }
}
