<?php

// app/Models/Producto.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';
    protected $primaryKey = 'idProducto';

    public $timestamps = false;

    protected $fillable = [
        'nombreProducto',
        'descripcion',
        'precio',
        'stock',
        'imagen',
    ];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'idProducto');
    }
}
