<?php

namespace App\Http\Controllers;


use App\Models\Client;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp;
use Illuminate\Support\Facades\Hash;

class ApiLoginController extends Controller
{
    public $successStatus = 200;
    /**
     *  api login Controller
     *
     * @return \Illuminate\Http\Response
     */

    public function login(Request $request){
       if(Auth::attempt(['name' => request('login'), 'password' => request('password')])){
       $user=Auth::user();
       $http = new GuzzleHttp\Client;
       $client = Client::getInfo(request('app_name'));
       $response = $http->post($_SERVER['HTTP_HOST'].'/oauth/token',[
           'form_params' => [
               'grant_type' => 'password',
               'client_id' => $client[0]->id,
               'client_secret' => $client[0]->secret,
               'username' => $request->login,
               'password' => $request->password,
               'scope' => '*'
           ],
       ]);


       $status = $response->getStatusCode();
       $body = $response->getBody();
       switch($status)
       {
           case 200:case 201:
           case 202:
               Log::info('User successful login.', [
                   'id' => $user->id,'login' => request('login')

               ]);
               $output = json_decode((string) $body, $this->successStatus);
               break;
           default:
               $output = ["access_token" => '', 'status_code' => $status];
               break;

       }
       return $output;
     } else {
         Log::info('User failed to login.', ['login' => request('login')]);
         return response()->json(['error'=>'Unauthorised'], 401);
     }



      }

}
