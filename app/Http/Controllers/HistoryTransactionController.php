<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HistoryTransactionController extends Controller
{
    public function historyOperations(Request $request){
        $user = $request->user();
        $accounts = $user->transaction()->with('account')->get();

        return response()->json([
            'accounts' => $accounts
        ]);
    }
}
