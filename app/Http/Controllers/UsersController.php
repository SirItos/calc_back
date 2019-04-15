<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use App\User;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
{
    public function getData(){
      $user = Auth::user();
      $user->role;
        return $user;
    }
    public  function getAll(Request $request){
        $result = User::all();
        foreach ($result as $res){
            $res->role;
        }
        return $result;
    }
}
