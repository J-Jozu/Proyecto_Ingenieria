<?php

// PRINCIPIO DE ABSTRACCIÓN: Clase abstracta que define la estructura común para todos los tipos de pago
abstract class Pago {
    // PRINCIPIO DE ENCAPSULAMIENTO: Atributo protegido que solo puede ser accedido por clases derivadas
    protected $monto;

    public function __construct($monto) {
        $this->monto = $monto;
    }

    // Método para seleccionar el método de pago
    public static function seleccionarMetodoPago($tipo, $monto, $datos = []) {
        if ($tipo === 'tarjeta') {
            return new TarjetaCredito(
                $monto,
                $datos['titular'] ?? '',
                $datos['numero'] ?? '',
                $datos['vencimiento'] ?? '',
                $datos['cvv'] ?? ''
            );
        } elseif ($tipo === 'yappy') {
            return new Yappy($monto);
        }
        throw new Exception('Método de pago no soportado');
    }
}

// PRINCIPIO DE HERENCIA: Extiende la clase Pago para implementar el pago con tarjeta de crédito
// PRINCIPIO DE POLIMORFISMO: Implementa el método obtenerFactura() de forma específica para tarjeta de crédito
class TarjetaCredito extends Pago {
    public $titular;
    public $numero;
    public $vencimiento;
    public $cvv;

    public function __construct($monto, $titular, $numero, $vencimiento, $cvv) {
        parent::__construct($monto);
        $this->titular = $titular;
        $this->numero = $numero;
        $this->vencimiento = $vencimiento;
        $this->cvv = $cvv;
    }

    public function obtenerFactura() {
        return [
            'metodo' => 'Tarjeta de Crédito',
            'titular' => $this->titular,
            'monto' => $this->monto,
            'fecha' => date('Y-m-d H:i:s')
        ];
    }
}

// PRINCIPIO DE HERENCIA: Extiende la clase Pago para implementar el pago con Yappy
// PRINCIPIO DE POLIMORFISMO: Implementa el método obtenerFactura() de forma específica para Yappy
class Yappy extends Pago {
    public function __construct($monto) {
        parent::__construct($monto);
    }

    public function obtenerFactura() {
        return [
            'metodo' => 'Yappy',
            'monto' => $this->monto,
            'fecha' => date('Y-m-d H:i:s')
        ];
    }

    public function redirigir() {
        header('Location: https://www.bgeneral.com/yappy/');
        exit;
    }
}
