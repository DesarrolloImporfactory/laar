<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';


use PHPMailer\PHPMailer\PHPMailer;

class LaarModel extends Query
{
    public function capturador($json)
    {
        $this->insert("INSERT INTO laar (json) VALUES (?)", [$json]);
    }

    public function actualizarEstado($estado, $guia)
    {
        $sql = "UPDATE facturas_cot set estado_guia_sistema = '$estado' WHERE numero_guia = '$guia' ";
        $response =  $this->select($sql);
        $update = "UPDATE cabecera_cuenta_pagar set estado_guia = '$estado' WHERE guia = '$guia' ";
        $response =  $this->select($update);
        if ($estado > 2) {
            // Obtener datos de la factura
            $sql = "SELECT * FROM facturas_cot WHERE numero_guia = '$guia' ";
            $stmt = $this->select($sql);
            $data = $stmt[0];
            if ($data) {
                $id_plataforma = $data['id_plataforma']; // Vendedor
                $id_propietario = $data['id_propietario']; // Proveedor

                // Obtener datos del vendedor
                $sql = "SELECT refiere FROM plataformas WHERE id_plataforma = '$id_plataforma'";
                $stmt = $this->select($sql);
                $vendedorData = $stmt[0];
                $refiere_vendedor = $vendedorData['refiere'] ?? null;

                // Obtener datos del proveedor
                $sql = "SELECT refiere FROM plataformas WHERE id_plataforma = '$id_propietario'";
                $stmt = $this->select($sql);
                $proveedorData = $stmt[0];
                $refiere_proveedor = $proveedorData['refiere'] ?? null;

                // Caso 1: Vendedor y Proveedor son referidos
                if (!empty($refiere_vendedor) && !empty($refiere_proveedor)) {
                    // Verificar si la tienda que refiere al proveedor es 1188
                    if ($refiere_proveedor == 1188) {
                        // Añadir al que refiere al vendedor y al proveedor
                        $this->procesarGuia($guia, $refiere_vendedor);
                        $this->procesarGuia($guia, $refiere_proveedor);
                    } else {
                        // Añadir solo al que refiere al vendedor
                        $this->procesarGuia($guia, $refiere_vendedor);
                    }
                } elseif (!empty($refiere_vendedor)) {
                    // Caso 2: Solo el vendedor es referido
                    $this->procesarGuia($guia, $refiere_vendedor);
                } elseif (!empty($refiere_proveedor)) {
                    // Caso 3: Solo el proveedor es referido
                    $this->procesarGuia($guia, $refiere_proveedor);
                }
                // Caso 4: Si ninguno es referido, no hacer nada
            }
        }
    }

    function procesarGuia($guia, $refiere)
    {
        // Verificar si la guía ya existe para esta plataforma
        $sql = "SELECT 1 FROM cabecera_cuenta_referidos WHERE guia = '$guia' AND id_plataforma = '$refiere'";
        $stmt = $this->select($sql);
        $exists =  $stmt[0];

        if ($exists) {
            // Añadir nuevo registro a cabecera_cuenta_referidos
            $sql = "REPLACE INTO cabecera_cuenta_referidos (guia, monto, fecha, id_plataforma) VALUES ('$guia', 0.3, NOW(), '$refiere')";
            $stmt = $this->select($sql);

            // Actualizar el saldo en billetera_referidos
            $sql = "UPDATE billetera_referidos SET saldo = saldo + 0.3 WHERE id_plataforma = '$refiere'";
            $stmt = $this->select($sql);

            // Obtener saldo actual para el historial
            $sql = "SELECT saldo FROM billetera_referidos WHERE id_plataforma = '$refiere'";
            $stmt = $this->select($sql);
            // Insertar en el historial de referidos
            $sql = "INSERT INTO historial_referidos (id_billetera, motivo, monto, previo, fecha) 
                 SELECT id_billetera, 'Referido por guia: $guia', 0.3, saldo, NOW() 
                 FROM billetera_referidos WHERE id_plataforma = '$refiere'";
            $stmt = $this->select($sql);
        }
    }

    public function notificar($novedades, $guia)
    {
        $id_plataforma = $this->select("SELECT id_plataforma FROM facturas_cot WHERE numero_guia = '$guia' ")[0]['id_plataforma'];
        echo $id_plataforma;


        $avisar = false;
        $nombre = "";
        $sql = "SELECT * FROM facturas_cot WHERE numero_guia = '$guia' ";
        $response = $this->select($sql);
        $nombreC = $response[0]['nombre'];

        foreach ($novedades as $novedad) {
            if ($novedad['codigoTipoNovedad'] == 42 || $novedad['codigoTipoNovedad'] == 43 || $novedad['codigoTipoNovedad'] == 92 || $novedad['codigoTipoNovedad'] == 96) {

                if ($novedad['codigoTipoNovedad'] == 92 || $novedad['codigoTipoNovedad'] == 96) {

                    $this->actualizarEstado(9, $guia);
                    $valor_pendiente = $this->obtenerValorPendiente($guia);
                    echo ". " . $valor_pendiente;
                    if ($valor_pendiente != 0) {
                        $response2 = $this->select("UPDATE cabecera_cuenta_pagar set valor_pendiente = ((precio_envio + full) * -1), monto_recibir = ((precio_envio + full) * -1) where guia = '$guia'; ");
                    }
                }
                $avisar = false;
                break;
            }

            $sql = "SELECT * FROM detalle_novedad WHERE guia_novedad = '$guia' AND codigo_novedad = '" . $novedad['codigoTipoNovedad'] . "' ";
            $response = $this->select($sql);
            print_r($response);

            if (count($response) == 0) {
                echo "entre";

                $avisar = true;
                $codigo = $novedad["codigoTipoNovedad"];
                $nombre = $novedad['nombreDetalleNovedad'];
                $detalle = $novedad['nombreTipoNovedad'];
                $observacion = $novedad['observacion'];

                $response = $this->insert("INSERT INTO detalle_novedad (codigo_novedad, guia_novedad, nombre_novedad, detalle_novedad, observacion, id_plataforma) VALUES (?, ?, ?, ?, ?, ?)", [$codigo, $guia, $nombre, $detalle, $observacion, $id_plataforma]);
                print_r($response);
            }
        }
        echo $avisar;
        if ($avisar) {

            $sql = "INSERT INTO novedades (guia_novedad, cliente_novedad, estado_novedad, novedad, tracking, fecha, id_plataforma) VALUES (?, ?, ?, ?, ?, ?, ?)";
            if (strpos($guia, 'IMP') == 0) {
                $tracking = "https://fenix.laarcourier.com/Tracking/Guiacompleta.aspx?guia=" . $guia;
            } else if (strpos($guia, 'I00') == 0) {
                $tracking = "https://ec.gintracom.site/web/site/tracking";
            } else if (is_numeric($guia)) {
                $tracking = "https://www.servientrega.com.ec/Tracking/?guia=" . $guia . "&tipo=GUI";
            }
            $response = $this->insert($sql, [$guia, $nombreC, $codigo, $detalle, $tracking, $novedad["fechaNovedad"], $id_plataforma]);
            print_r($response);
            if ($avisar) {
                //$this->enviarCorreo($guia);
            }
        } else {
            echo "No hay novedades";
        }
    }

    public function enviarCorreo($guia)
    {
        $datos = "SELECT * FROM facturas_cot WHERE numero_guia = '$guia' ";
        $select = $this->select($datos);
        $data_factura = $select[0];
        $id_plataforma = $data_factura['id_plataforma'];
        $id_usuario = $data_factura['id_usuario'];

        $datos = "SELECT * FROM users WHERE id_users = '$id_usuario' ";
        $select = $this->select($datos);
        $data_usuario = $select[0];
        $correo = $data_usuario['email_users'];

        require_once 'PHPMailer/Mail_devolucion.php';
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug = $smtp_debug;
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->Port = 465;
        $mail->SMTPSecure = $smtp_secure;
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($smtp_from, $smtp_from_name);
        $mail->addAddress($correo);
        $mail->Subject = 'Novedad de pedido en Imporsuitpro';
        $mail->Body = $message_body_pedido;
        // $this->crearSubdominio($tienda);

        if ($mail->send()) {
            echo "Correo enviado";
        } else {
            //  echo "Error al enviar el correo: " . $mail->ErrorInfo;
        }
    }
    public function masivo()
    {
        $sql = "SELECT * FROM facturas_cot WHERE numero_guia like 'IMP%' and estado_guia_sistema != 8 ORDER BY `numero_factura` DESC";
        $guias = $this->select($sql);

        // Procesar en lotes
        $batch_size = 20; // Tamaño del lote
        $delay = 1; // Retraso en segundos entre lotes
        $total_guias = count($guias);
        $batches = ceil($total_guias / $batch_size);

        for ($i = 0; $i < $batches; $i++) {
            $batch_guias = array_slice($guias, $i * $batch_size, $batch_size);
            foreach ($batch_guias as $guia) {
                $this->verificar($guia['numero_guia']);
            }
            // Añadir un retraso entre lotes
            sleep($delay);
        }
    }

    public function verificar($guia)
    {
        // Inicializar cURL para la primera solicitud
        $ch = curl_init("https://api.laarcourier.com:9727/guias/" . $guia);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if ($response === false) {
            // Manejar errores en la solicitud
            $error_msg = curl_error($ch);
            curl_close($ch);
            echo "Error en la solicitud: $error_msg";
            return;
        }

        // Inicializar cURL para la segunda solicitud
        $url = "https://laar.imporsuit.app/Laar";
        $ch2 = curl_init($url);
        curl_setopt($ch2, CURLOPT_POST, 1);
        curl_setopt($ch2, CURLOPT_POSTFIELDS, $response);
        curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);

        $response2 = curl_exec($ch2);

        if ($response2 === false) {
            // Manejar errores en la segunda solicitud
            $error_msg2 = curl_error($ch2);
            curl_close($ch2);
            curl_close($ch);
            echo "Error en la segunda solicitud: $error_msg2";
            return;
        }

        curl_close($ch2);
        curl_close($ch);

        echo $response2;
    }

    public function obtenerValorPendiente($guia)
    {
        $sql = "SELECT * FROM cabecera_cuenta_pagar where guia = '$guia'";
        $response = $this->select($sql);
        $valor_pendiente = $response[0]['valor_pendiente'];
        return $valor_pendiente;
    }
}
