<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class label extends Model
{
    use HasFactory;
    protected $table = 'cat_labels';
    protected $primaryKey = 'idLabel';
    protected $fillabe = [

        'nameL',
        'colorL',
        'idWorkEnv',
        'logicdeleted'
    ];


   
}
