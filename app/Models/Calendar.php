<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    use HasFactory;
    protected $table = "cat_calendarevents";
    protected $primaryKey = "idCalendarEvent";
    protected $fillabe = [
        'title',
        'description',
        'color',
        'start',
        'end',
        'logicdeleted',
        'idJoinUserWork'
    ];

}
