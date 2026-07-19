<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\HistoryTransactionController;
use App\Http\Controllers\ManagerAccountController;
use App\Http\Controllers\OperationBankController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Route qui permet de d'inscrire un utilisateur
Route::post('/register',[AuthController::class,'register']);

//route qui permet de connecter un utilisateur
Route::post('login',[AuthController::class,'login']);


Route::middleware('auth:sanctum')->group(function(){
    //cette route permet de deconnecter un utilisateur de son compte
    Route::post('/logout',[AuthController::class,'logout']);

    //route qui permet d'ouvrir un compte soit courant , epagne ou pro
    Route::post('/open_account',[ManagerAccountController::class,'openAccount']);

    //cette route permet a un client de d'afficher ses compte specifique
    Route::get('/display_accounts',[ManagerAccountController::class,'displayAllAccounts' ]);

    //cette route permet aw un client de coir les informations relatives a son compte
    Route::get('/display_account/{id}',[ManagerAccountController::class, 'displayMyInformationAccount']);

    //cette route permet de femer le compte d'un utilisateur
    Route::post('/close_account/{id}',[ManagerAccountController::class, 'closeAccount']);

       //cette route permet de bloquer le compte d'un utilisateur
    Route::post('/to_block_account/{id}',[ManagerAccountController::class, 'toBlockAccount']);

    //cette route permet de debloquer le compte d'un utilisateur
    Route::post('/un_account/{id}',[ManagerAccountController::class, 'unblock']);

    //cette route permet a un utilisateur de deposer de la'agent dans un autre compte
    Route::post("/deposite",[OperationBankController::class,'depositeInMyAccount']);

    //cette route permet a un clienT de retirer del'agent dans son propre compte
    Route::post("/Withdrawal",[OperationBankController::class, 'MoneyWithdrawal']);

    //cette route permet a un client d'effectuer un virement depuis son compte
    Route::post("/bank_transfert",[OperationBankController::class,'bankTransfert']);

    //cette route permet a un client de verifier son compte
    Route::get('/verify_amount',[OperationBankController::class,'verifyAmount']);

    //cette route permet a un client de voir l'historique de ses operations
    Route::get('/history_operations',[HistoryTransactionController::class,'historyOperations']);
    
});

// 