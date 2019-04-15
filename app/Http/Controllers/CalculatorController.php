<?php

namespace App\Http\Controllers;

use App\Models;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Connection;
use App\Http\Controllers\StoredPorcedurController;

class CalculatorController extends Controller
{

    public function __construct(StoredPorcedurController $StoredPorcedurController)
    {
        $this->storageController = $StoredPorcedurController;
    }

    protected function getFieldScheme(Request $request){
        $user_id = Auth::user()->id;
        $result = DB::select('EXEC p_CalculationParamGetList ?,?',array($user_id,1));
        return $result;
    }

    protected function getCalculations (Request $request){
        $user_id =Auth::user()->id;
        $result = DB::select('EXEC p_CalculationGetList ?,?', array($user_id, 1));
        $result = $this->storageController->pagination($request->pagination,$result,$request->search);
        return $result;
    }

    protected function singleCalculation(Request $request){
        $user_id =Auth::user()->id;
        $result = DB::select('EXEC p_CalculationDataGetList ?,?', array($user_id, $request->id));
        return $result;
    }
}