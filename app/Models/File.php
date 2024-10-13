<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $table = "cat_files";
    protected $primaryKey = "idFile";
    protected $fillable = [

        'nameA',
        'path',
        'logicdeleted',
        'filesize',
        'type',
        'idFolder'

    ];
}
