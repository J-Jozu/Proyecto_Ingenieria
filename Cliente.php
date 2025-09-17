<?php
// PRINCIPIO DE ENCAPSULAMIENTO: Clase que agrupa datos y comportamientos relacionados con un cliente
class Cliente {
    public $cedula;
    public $nombre_completo;
    public $telefono_celular;
    public $correo;
    public $direccion;

    public function __construct($cedula, $nombre_completo, $telefono_celular, $correo, $direccion = '') {
        $this->cedula = $cedula;
        $this->nombre_completo = $nombre_completo;
        $this->telefono_celular = $telefono_celular;
        $this->correo = $correo;
        $this->direccion = $direccion;
    }

    // Inserta cliente si no existe y agenda la sesión, retorna id de reserva
    public function reservarSesion($datosSesion, $pdo) {
        // Verificar si el cliente ya existe
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM cliente WHERE cedula = ?");
        $stmt->execute([$this->cedula]);
        $existe_cliente = $stmt->fetchColumn();

        // Si no existe, insertarlo
        if (!$existe_cliente) {
            $stmt = $pdo->prepare("INSERT INTO cliente (cedula, nombre_completo, telefono_celular, correo, direccion) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $this->cedula,
                $this->nombre_completo,
                $this->telefono_celular,
                $this->correo,
                $this->direccion
            ]);
        }

        // Insertar en agenda con todos los campos
        $stmt = $pdo->prepare("
            INSERT INTO agenda (
                tipo_sesion, descripcion_sesion, lugar_sesion, estilo_fotografia, servicios_adicionales, 
                resto_por_pagar, total_pagar, otros_datos, lugar, fecha, hora, duracion_sesion, cedula_cliente
            ) VALUES (
                :tipo_sesion, :descripcion_sesion, :lugar_sesion, :estilo_fotografia, :servicios_adicionales,
                :resto_por_pagar, :total_pagar, :otros_datos, :lugar, :fecha, :hora, :duracion_sesion, :cedula_cliente
            )
        ");
        $stmt->execute([
            ':tipo_sesion'          => $datosSesion['tipo_sesion'],
            ':descripcion_sesion'   => $datosSesion['descripcion_sesion'] ?? null,
            ':lugar_sesion'         => $datosSesion['lugar_sesion'] ?? null,
            ':estilo_fotografia'    => $datosSesion['estilo_fotografia'] ?? null,
            ':servicios_adicionales'=> $datosSesion['servicios_adicionales'] ?? null,
            ':resto_por_pagar'      => $datosSesion['resto_por_pagar'] ?? null,
            ':total_pagar'          => $datosSesion['total_pagar'] ?? null,
            ':otros_datos'          => $datosSesion['otros_datos'] ?? null,
            ':lugar'                => $datosSesion['lugar'] ?? null,
            ':fecha'                => $datosSesion['fecha'] ?? null,
            ':hora'                 => $datosSesion['hora'] ?? null,
            ':duracion_sesion'      => $datosSesion['duracion'] ?? null, // Usamos 'duracion' que viene del strategy
            ':cedula_cliente'       => $this->cedula
        ]);
        return $pdo->lastInsertId();
    }

    // Inserta el pago (abono inicial)
    public function realizarAbono($datosPago, $pdo) {
        $stmt = $pdo->prepare("
            INSERT INTO pago (monto_total, abono_inicial, estado, id_reserva)
            VALUES (:total, :abono, 'pagado', :id_reserva)
        ");
        $stmt->execute([
            ':total'      => $datosPago['total'],
            ':abono'      => $datosPago['abono'],
            ':id_reserva' => $datosPago['id_reserva']
        ]);
    }

    // Cancela la sesión (elimina de agenda y/o cambia estado)
    public function cancelarSesion($idReserva, $pdo) {
        // Ejemplo: cambiar estado a 'cancelado' en agenda
        $stmt = $pdo->prepare("UPDATE agenda SET estado = 'cancelado' WHERE id = ?");
        $stmt->execute([$idReserva]);
    }
}
