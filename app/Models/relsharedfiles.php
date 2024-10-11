<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class relsharedfiles extends Model
{
    use HasFactory;
    protected $primaryKey = 'idShareFile';
    protected $table = 'rel_sharedfolder_user';
    protected $fillabe = [
        'idFolder',
        'idJoinUserWork'
    ];
}
