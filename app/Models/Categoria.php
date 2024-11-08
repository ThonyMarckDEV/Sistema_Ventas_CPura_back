<?php

// app/Models/Categoria.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $table = 'categorias';
    protected $primaryKey = 'idCategoria';

    public $timestamps = false;

    protected $fillable = [
        'nombreCategoria',
        'idProducto',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'idProducto');
    }
}
