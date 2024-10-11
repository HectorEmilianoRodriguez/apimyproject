<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class activitycoordinatorleader extends Model
{
    use HasFactory;
    protected $table = 'cat_activity_coordinatorleaders';
    protected $primaryKey = 'idactivitycl';
    protected $fillabe = [

        'nameT',
        'descriptionT',
        'startdate',
        'logicdeleted',
        'important',
        'done',
        'idgrouptaskcl',
        'idLabel'
    ];
}
