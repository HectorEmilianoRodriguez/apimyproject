<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class relcarduser extends Model
{
    use HasFactory;
    protected $table = 'rel_cards_users';
    protected $fillabe = [
        'idUser',
        'idCard'
    ];
}
