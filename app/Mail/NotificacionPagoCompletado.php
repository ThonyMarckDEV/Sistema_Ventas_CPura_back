<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotificacionPagoCompletado extends Mailable
{
    use Queueable, SerializesModels;

    public $nombreCompleto;
    public $detallesPedido;
    public $total;

    public function __construct($nombreCompleto, $detallesPedido, $total)
    {
        $this->nombreCompleto = $nombreCompleto;
        $this->detallesPedido = $detallesPedido;
        $this->total = $total;
    }

    public function build()
    {
        return $this->view('emails.notificacionPagoCompletado')
                    ->subject('Pago Completado - Detalles de su Pedido');
    }
}
