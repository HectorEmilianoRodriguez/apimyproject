<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Card extends Model
{
    use HasFactory;
    protected $table = 'cat_cards';
    protected $primaryKey = 'idCard';

   
    protected $fillable = [
        'nameC',
        'descriptionC',
        'end_date',
        'evidence',
        'logicdeleted',
        'approbed',
        'important',
        'done',
        'idList'
    ];

    // Relación de una Card con una Lista
    public function list()
    {
        return $this->belongsTo(Lists::class, 'idList');
    }
}
