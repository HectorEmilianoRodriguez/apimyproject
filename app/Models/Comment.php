<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;
    protected $table = 'cat_comments';
    protected $primaryKey = 'idComment';

    protected $fillable = [

        'idCard',
        'idJoinUserWork',
        'text',
        'seen',
        'logicdeleted'

    ];
}
