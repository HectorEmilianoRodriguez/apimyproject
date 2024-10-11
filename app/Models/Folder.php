<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    use HasFactory;

    protected $table = "cat_folders";
    protected $primaryKey = "idFolder";
    protected $fillable = [

        'nameF',
        'idJoinUserWork',
        'logicdeleted'

    ];
}
