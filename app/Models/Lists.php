<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lists extends Model
{
    use HasFactory;
    protected $table = 'cat_lists';
    protected $primaryKey = 'idList'; // Cambiar a 'idList' si es la clave primaria de cat_lists

    protected $fillable = [ // Corrige 'fillabe' a 'fillable'
        'nameL',
        'descriptionL',
        'colorL',
        'logicdeleted',
        'idBoard'
    ];

    // Relación de una Lista con muchas Cards
    public function cards()
    {
        return $this->hasMany(Card::class, 'idList');
    }
}

