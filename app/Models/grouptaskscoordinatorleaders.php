<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class grouptaskscoordinatorleaders extends Model
{
    use HasFactory;
    protected $table = 'cat_grouptasks_coordinatorleaders';
    protected $primaryKey = 'idgrouptaskcl';

    protected $fillabe = [
        'idJoinUserWork',
        'name',
        'startdate',
        'enddate',
        'logicdeleted'

    ];

}
