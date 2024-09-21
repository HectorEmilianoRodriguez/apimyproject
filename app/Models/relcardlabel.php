<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class relcardlabel extends Model
{
    use HasFactory;
    protected $table = 'rel_card_labels';
    protected $fillabe = [
        'idLabel',
        'idCard'
    ];
}
