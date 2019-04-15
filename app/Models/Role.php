<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /**
     *
     * Связывает модель с таблицей Roles
     *
     * @var array
     */

    protected $hidden = [
        'RecordTimestamp'
    ];
    protected $table = 'Role';

    public function user(){
        return $this->belongsTo(User::class,'roleid','roleid');
    }

}
