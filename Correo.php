<?php
/* Este archivo implementa el patrón Facade para el envío de correos electrónicos.
 * El patrón Facade proporciona una interfaz simplificada a un subsistema complejo (en este caso, PHPMailer).
 * Permite a los clientes enviar correos sin necesidad de conocer los detalles de implementación de PHPMailer.
*/

// Incluir las dependencias necesarias de PHPMailer
require_once __DIR__ . '/../phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/src/SMTP.php';
require_once __DIR__ . '/../phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* Principios de Orientación a Objetos utilizados:
 * 1. Abstracción: Oculta la complejidad de PHPMailer detrás de una interfaz simple.
 * 2. Encapsulamiento: Los detalles de implementación están ocultos, solo se expone un método público.
*/
class CorreoFacade {
    public static function enviarCorreo($to, $subject, $body, $from = 'eliasricardooliver@gmail.com', $fromName = 'PhotoStudio') {
        // Crear una nueva instancia de PHPMailer con el manejo de excepciones habilitado
        $mail = new PHPMailer(true);
        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'eliasricardooliver@gmail.com';
            $mail->Password = 'wshu iwfv cqhi lctl'; 
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            // Configuración del remitente y destinatario
            $mail->setFrom($from, $fromName);
            $mail->addAddress($to); 
            // Contenido del correo
            $mail->isHTML(true); // Permite enviar correos en formato HTML
            $mail->Subject = $subject;
            $mail->Body = $body;
            // Intento de envío del correo
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} 