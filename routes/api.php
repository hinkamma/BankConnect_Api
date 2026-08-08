<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Authentification;
use App\Http\Controllers\HistoryTransactionController;
use App\Http\Controllers\ManagerAccountController;
use App\Http\Controllers\OperationBankController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CompteController;
use App\Http\Controllers\Api\OperationsController;
use App\Http\Controllers\Api\StoryController;
use App\Http\Controllers\Api\ProfilController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Route qui permet de d'inscrire un utilisateur
Route::post('/register',[Authentification::class,'register']);

//route qui permet de connecter un utilisateur
Route::post('/login',[Authentification::class,'login'])->middleware('throttle:login');


Route::middleware('auth:sanctum')->group(function(){

    // cette route permet de deconnecter tous les utilisateurs connectés de leur compte
    Route::post('/logout_all',[Authentification::class,'logoutAllDevices']);

    
    // route qui permet de verifier le code a deux facteurs
    Route::post('/verify_code',[Authentification::class,'twoFactorVerify']);

    Route::post('/resend_code',[Authentification::class,'resendToken']);

    // cette route permet de voir tous les utilisateurs connectés a leur compte
    Route::get('/display_all_users',[Authentification::class,'devices']);


    //cette route permet de deconnecter un utilisateur de son compte
    Route::post('/logout',[Authentification::class,'logoutUser']);

    //route qui permet d'ouvrir un compte soit courant , epagne ou pro
    Route::post('/open_account',[CompteController::class,'openAccount']);

    //cette route permet a un client de d'afficher ses compte specifique
    Route::get('/display_accounts',[CompteController::class,'displayAllAccounts' ]);

    //cette route permet aw un client de coir les informations relatives a son compte
    Route::get('/display_account/{id}',[CompteController::class, 'displayMyInformationAccount']);

    //cette route permet de femer le compte d'un utilisateur
    Route::post('/close_account/{id}',[CompteController::class, 'closeAccount']);

       //cette route permet de bloquer le compte d'un utilisateur
    Route::post('/to_block_account/{id}',[CompteController::class, 'toBlockAccount']);

    //cette route permet de debloquer le compte d'un utilisateur
    Route::post('/unblock_account/{id}',[CompteController::class, 'unblockAccount']);



    //cette route permet a un utilisateur de deposer de la'agent dans un autre compte
    Route::post("/deposite",[OperationsController::class,'depositeInMyAccount']);

    //cette route permet a un clienT de retirer del'agent dans son propre compte
    Route::post("/Withdrawal",[OperationsController::class, 'MoneyWithdrawal']);

    //cette route permet a un client d'effectuer un virement depuis son compte
    Route::post("/bank_transfert",[OperationsController::class,'effectuerVirement']);

    //cette route permet a un client de verifier son compte
    Route::get('/verify_amount',[OperationsController::class,'verifyAmount']);

    // Créer un virement programmé
    Route::post('/virements-programmes', [OperationsController::class, 'faireVirementProgramme']);

    // Lister les virements programmés de l'utilisateur connecté
    Route::get('/virements-programmes', [OperationsController::class, 'ListerVirementsProgrammes']);

    // Annuler un virement programmé
    Route::delete('/virements-programmes/{scheduledTransfer}', [OperationsController::class, 'anullerVirementProgramme']);

    // CRUD Bénéficiaires
    Route::post('/add_beneficiaires', [OperationsController::class, 'addbeneficiaire']);
    Route::get('/Lister_beneficiaires', [OperationsController::class, 'ListerAllBeneficiaireToUser']);
    Route::put('/Update_beneficiaires/{beneficiary}', [OperationsController::class, 'updatebeneficiaire']);
    Route::delete('/Delete_beneficiaires/{beneficiary}', [OperationsController::class, 'deletebeneficiaire']);


    Route::get('/history_operations',[StoryController::class,'historyOperations']);
    Route::get("exportCsvPdf",[StoryController::class,'exportHistoryOperations']);

    //cette route permet a un client de voir l'historique de ses operations
    Route::get('/history_operations',[StoryController::class,'historyOperations']);


    //operations sur le profil utilisateur 
    Route::get('/profil', [ProfilController::class, 'show']);
    Route::put('/profil', [ProfilController::class, 'update']);
    Route::post('/profil/photo', [ProfilController::class, 'updatePhoto']); // POST pour l'envoi de fichier FormData
    Route::put('/profil/password', [ProfilController::class, 'updatePassword']);

    // Route de vérification d'identité (OCR)
    Route::post('/profil/verify-identity', [ProfilController::class, 'verifyIdentity']);
});

// 