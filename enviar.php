<?php
/**
 * Envío del formulario de contacto de la landing.
 * Recibe el POST del formulario (fetch desde index.html) y lo manda por correo con mail() de PHP.
 * El correo de ikaan.mx / ikaan.com.mx vive en el mismo servidor Plesk, así que la entrega es local.
 *
 * ====== CONFIGURACIÓN (solo editar esto) ======
 */
$DESTINO   = '';                                          // PENDIENTE: correo que recibe las solicitudes de bodas. Mientras esté vacío, el formulario muestra error y ofrece WhatsApp
$ASUNTO    = 'Nueva solicitud — Landing Bodas';
$REMITENTE = 'no-reply@ikaan.com.mx';                     // Remitente técnico (debe ser de ikaan.com.mx o ikaan.mx)
$NOMBRE_REMITENTE = 'Landing Ikaan';
/* ============================================== */

date_default_timezone_set('America/Monterrey');
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex');

function responder($ok, $codigo = 200, $error = null) {
  http_response_code($codigo);
  echo json_encode($error ? ['ok' => $ok, 'error' => $error] : ['ok' => $ok]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(false, 405, 'metodo');
if (trim($DESTINO) === '') responder(false, 503, 'sin-destino');

// Anti-spam: campo trampa que una persona nunca ve, y tiempo mínimo para llenar el formulario.
// A los bots se les responde "ok" para que no reintenten, pero no se envía nada.
if (!empty($_POST['sitio_web'])) responder(true);
if (isset($_POST['_t']) && (int)$_POST['_t'] < 3000) responder(true);

// Etiquetas legibles para el correo. Solo se envían los campos de esta lista.
$CAMPOS = [
  'nombre'    => 'Nombre',
  'puesto'    => 'Puesto',
  'empresa'   => 'Empresa o institución',
  'tipo'      => 'Tipo de evento',
  'personas'  => 'Número de personas',
  'invitados' => 'Número de invitados',
  'fecha'     => 'Fecha estimada',
  'motivo'    => 'Motivo de contacto',
  'telefono'  => 'Teléfono',
  'celular'   => 'Celular',
  'correo'    => 'Correo electrónico',
  'mensaje'   => 'Mensaje',
];

$datos = [];
foreach ($CAMPOS as $clave => $etiqueta) {
  if (!isset($_POST[$clave]) || is_array($_POST[$clave])) continue;
  $valor = trim(mb_substr((string)$_POST[$clave], 0, $clave === 'mensaje' ? 3000 : 200));
  if ($clave !== 'mensaje') $valor = preg_replace('/\s+/u', ' ', $valor); // campos de una línea
  if ($valor !== '') $datos[$clave] = $valor;
}

$correo = $datos['correo'] ?? '';
if (empty($datos['nombre']) || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
  responder(false, 422, 'datos');
}

$cuerpo = '';
foreach ($datos as $clave => $valor) {
  $cuerpo .= $CAMPOS[$clave] . ': ' . $valor . "\n";
}
$pagina = $_SERVER['HTTP_REFERER'] ?? '';
$cuerpo .= "\n—\nEnviado desde: " . ($pagina ?: 'landing') . "\nFecha: " . date('Y-m-d H:i') . "\n";

// Encabezados: el nombre del prospecto no puede traer saltos de línea (inyección de encabezados)
$nombreLimpio = preg_replace('/[\r\n"<>]+/', ' ', $datos['nombre']);
$encabezados  = [
  'From: =?UTF-8?B?' . base64_encode($NOMBRE_REMITENTE) . '?= <' . $REMITENTE . '>',
  'Reply-To: =?UTF-8?B?' . base64_encode($nombreLimpio) . '?= <' . $correo . '>',
  'MIME-Version: 1.0',
  'Content-Type: text/plain; charset=UTF-8',
  'Content-Transfer-Encoding: 8bit',
];
$asunto = '=?UTF-8?B?' . base64_encode($ASUNTO . ' — ' . $nombreLimpio) . '?=';

$enviado = @mail($DESTINO, $asunto, $cuerpo, implode("\r\n", $encabezados), '-f' . $REMITENTE);
if (!$enviado) responder(false, 500, 'envio');

responder(true);
