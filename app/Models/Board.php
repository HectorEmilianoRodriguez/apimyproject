<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    use HasFactory;
    protected $table = 'cat_boards';
    protected $primaryKey = 'idBoard';
    protected $fillable = [

        'nameB',
        'descriptionB',
        'logicdeleted',
        'idWorkEnv'
    ];
}
