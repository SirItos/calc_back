<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Client extends Model
{
    /**
     * Получаем информацию о приложении
     *
     * и определяем клиент
     *
     * @return \Illuminate\Http\Response
     */

    public static function getInfo($appName){
        $result = DB::table('oauth_clients (NOLOCK)')->select('id', 'secret')->where('name', '=', $appName)->get();
        return $result;
    }
}
