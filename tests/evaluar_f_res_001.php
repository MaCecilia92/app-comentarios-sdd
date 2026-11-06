<?php
/**
 * Evaluador externo de F-RES-001 (Semana 6, "Stop Hook").
 * Responsabilidad: comprobar contra la API, sin mirar el código, el criterio de evaluación
 *   del ciclo: F-RES-001 · CA-2, CA-3 y CA-5, más las reglas que los sostienen (RN-003,
 *   RN-006, RN-008, D-02) y el rechazo del borrado (D-08, F-MOD-001 · CA-4).
 * Contrato que se prueba: design.md D-14.
 * Uso: C:\xampp\php\php.exe tests\evaluar_f_res_001.php [url-de-la-api]
 *   Por defecto: http://localhost:8080/app-comentarios-sdd/api/conversacion.php
 * Resultado: imprime PASA / FALLA por caso y termina con código 0 solo si todo pasa.
 *   Crea comentarios y respuestas de prueba en la base; no borra nada (RN-009).
 */

$api = $argv[1] ?? 'http://localhost:8080/app-comentarios-sdd/api/conversacion.php';
$marca = 'eval-' . date('YmdHis') . '-' . mt_rand(1000, 9999);
$resultados = [];

// ---------- Utilidades ----------

function pedir($api, $metodo, $cuerpo = null)
{
    $opciones = [
        'method' => $metodo,
        'ignore_errors' => true,
        'timeout' => 10,
        'header' => "Content-Type: application/json\r\n",
    ];
    if ($cuerpo !== null) {
        $opciones['content'] = json_encode($cuerpo, JSON_UNESCAPED_UNICODE);
    }
    $texto = @file_get_contents($api, false, stream_context_create(['http' => $opciones]));
    $codigo = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0] . ' ', $m)) {
        $codigo = (int) $m[1];
    }
    $json = is_string($texto) ? json_decode($texto, true) : null;
    return ['codigo' => $codigo, 'json' => is_array($json) ? $json : null, 'crudo' => $texto];
}

function registrar(&$resultados, $id, $descripcion, $ok, $detalle = '')
{
    $resultados[] = ['id' => $id, 'ok' => $ok];
    echo ($ok ? '  PASA  ' : '  FALLA ') . str_pad($id, 10) . $descripcion . "\n";
    if (!$ok && $detalle !== '') {
        echo '          -> ' . $detalle . "\n";
    }
}

function resumen_respuesta($r)
{
    $mensaje = $r['json']['mensaje'] ?? '(sin mensaje)';
    return 'código ' . $r['codigo'] . ', mensaje: "' . $mensaje . '"';
}

function buscar_comentario($api, $contenido)
{
    $r = pedir($api, 'GET');
    foreach (($r['json']['conversacion'] ?? []) as $comentario) {
        if (($comentario['contenido'] ?? null) === $contenido) {
            return $comentario;
        }
    }
    return null;
}

function cantidad_respuestas($api, $contenidoPadre)
{
    $c = buscar_comentario($api, $contenidoPadre);
    return $c === null ? -1 : count($c['respuestas'] ?? []);
}

function publicar_respuesta($api, $comentarioId, $autor, $contenido)
{
    return pedir($api, 'POST', [
        'operacion' => 'publicar_respuesta',
        'comentario_id' => $comentarioId,
        'autor' => $autor,
        'contenido' => $contenido,
    ]);
}

function ultima_respuesta($api, $contenidoPadre)
{
    $c = buscar_comentario($api, $contenidoPadre);
    $respuestas = $c['respuestas'] ?? [];
    return $respuestas ? end($respuestas) : null;
}

echo "Evaluación de F-RES-001 contra $api\n";
echo "Marca de esta corrida: $marca\n\n";

// ---------- Prerrequisito: un comentario padre (F-COM-001, D-14) ----------

echo "Prerrequisito\n";
$contenidoPadre = "Comentario padre $marca";
$r = pedir($api, 'POST', ['operacion' => 'publicar_comentario', 'autor' => 'Evaluador', 'contenido' => $contenidoPadre]);
$padre = buscar_comentario($api, $contenidoPadre);
$okPadre = $r['codigo'] === 200 && ($r['json']['exito'] ?? null) === true && $padre !== null;
registrar($resultados, 'PRE', 'Se puede publicar un comentario y aparece en la conversación', $okPadre, resumen_respuesta($r));

if (!$okPadre) {
    echo "\nSin comentario padre no se puede evaluar F-RES-001. Resultado: NO PASA\n";
    exit(1);
}
$padreId = $padre['id'];

// ---------- CA-2: respuesta válida ----------

echo "\nF-RES-001 · CA-2 (respuesta válida)\n";
$primera = "Primera respuesta $marca";
$r = publicar_respuesta($api, $padreId, 'Ana', $primera);
registrar($resultados, 'CA-2a', 'Responde con éxito y el mensaje exacto',
    $r['codigo'] === 200 && ($r['json']['exito'] ?? null) === true
        && ($r['json']['mensaje'] ?? '') === 'Respuesta publicada correctamente',
    resumen_respuesta($r));
$ultima = ultima_respuesta($api, $contenidoPadre);
registrar($resultados, 'CA-2b', 'La respuesta queda debajo de su comentario padre',
    ($ultima['contenido'] ?? null) === $primera, 'última respuesta: ' . json_encode($ultima, JSON_UNESCAPED_UNICODE));

$segunda = "Segunda respuesta $marca";
publicar_respuesta($api, $padreId, 'Juan', $segunda);
$c = buscar_comentario($api, $contenidoPadre);
$contenidos = array_map(function ($x) { return $x['contenido'] ?? ''; }, $c['respuestas'] ?? []);
registrar($resultados, 'RN-006', 'Las respuestas salen de la más vieja a la más nueva (la nueva, última)',
    array_slice($contenidos, -2) === [$primera, $segunda], 'orden recibido: ' . json_encode($contenidos, JSON_UNESCAPED_UNICODE));

// ---------- CA-3: datos inválidos (no se guarda nada) ----------

echo "\nF-RES-001 · CA-3 (datos inválidos, sin guardar nada)\n";
$invalidos = [
    ['CA-3a', 'Contenido solo con espacios', 'Ana', '     ', 'Completá tu nombre y tu respuesta'],
    ['CA-3b', 'Autor vacío', '', 'Texto válido', 'Completá tu nombre y tu respuesta'],
    ['CA-3c', 'Contenido de 2 caracteres', 'Ana', 'ab', 'La respuesta debe tener al menos 3 caracteres'],
    ['CA-3d', 'Contenido de 501 caracteres', 'Ana', str_repeat('x', 501), 'La respuesta no puede superar los 500 caracteres'],
    ['CA-3e', 'Autor de 1 carácter', 'A', 'Texto válido', 'El nombre debe tener entre 2 y 100 caracteres'],
    ['CA-3f', 'Autor de 101 caracteres', str_repeat('n', 101), 'Texto válido', 'El nombre debe tener entre 2 y 100 caracteres'],
];
foreach ($invalidos as [$id, $descripcion, $autor, $contenido, $mensaje]) {
    $antes = cantidad_respuestas($api, $contenidoPadre);
    $r = publicar_respuesta($api, $padreId, $autor, $contenido);
    $despues = cantidad_respuestas($api, $contenidoPadre);
    registrar($resultados, $id, "$descripcion: se rechaza con su mensaje y no se guarda",
        $r['codigo'] === 400 && ($r['json']['exito'] ?? null) === false
            && ($r['json']['mensaje'] ?? '') === $mensaje && $antes === $despues,
        resumen_respuesta($r) . " (esperado: 400, \"$mensaje\"); respuestas antes/después: $antes/$despues");
}

// ---------- Bordes aceptados (RN-001, RN-003, D-02) ----------

echo "\nBordes aceptados (RN-001, RN-003, D-02)\n";
$bordes = [
    ['BORDE-a', 'Contenido de exactamente 3 caracteres', 'Ana', 'abc'],
    ['BORDE-b', 'Contenido de exactamente 500 caracteres', 'Ana', str_repeat('x', 500)],
    ['BORDE-c', '500 letras "á" (mide caracteres, no bytes)', 'Ana', str_repeat('á', 500)],
    ['BORDE-d', 'Autor de exactamente 2 caracteres', 'Al', 'Texto válido'],
    ['BORDE-e', 'Autor de exactamente 100 caracteres', str_repeat('n', 100), 'Texto válido'],
];
foreach ($bordes as [$id, $descripcion, $autor, $contenido]) {
    $r = publicar_respuesta($api, $padreId, $autor, $contenido);
    $ultima = ultima_respuesta($api, $contenidoPadre);
    registrar($resultados, $id, "$descripcion: se acepta completo",
        $r['codigo'] === 200 && ($r['json']['exito'] ?? null) === true && ($ultima['contenido'] ?? null) === $contenido,
        resumen_respuesta($r));
}

// ---------- Texto: recorte, literal y saltos de línea (RN-003, RN-008) ----------

echo "\nTexto (RN-003, RN-008)\n";
$r = publicar_respuesta($api, $padreId, '  Ana  ', "   Hola mundo $marca   ");
$ultima = ultima_respuesta($api, $contenidoPadre);
registrar($resultados, 'RN-003', 'Se recortan los espacios de los extremos del autor y del contenido',
    ($ultima['autor'] ?? null) === 'Ana' && ($ultima['contenido'] ?? null) === "Hola mundo $marca",
    'guardado: ' . json_encode($ultima, JSON_UNESCAPED_UNICODE));

$literal = "<b>negrita</b> $marca";
publicar_respuesta($api, $padreId, 'Ana', $literal);
$ultima = ultima_respuesta($api, $contenidoPadre);
registrar($resultados, 'RN-008a', 'El texto con forma de etiqueta se guarda y vuelve literal',
    ($ultima['contenido'] ?? null) === $literal, 'recibido: ' . json_encode($ultima['contenido'] ?? null, JSON_UNESCAPED_UNICODE));

$dosLineas = "Línea uno $marca\nLínea dos";
publicar_respuesta($api, $padreId, 'Ana', $dosLineas);
$ultima = ultima_respuesta($api, $contenidoPadre);
registrar($resultados, 'RN-008b', 'Se conservan los saltos de línea',
    ($ultima['contenido'] ?? null) === $dosLineas, 'recibido: ' . json_encode($ultima['contenido'] ?? null, JSON_UNESCAPED_UNICODE));

// ---------- CA-5: comentario padre inexistente ----------

echo "\nF-RES-001 · CA-5 (comentario inexistente)\n";
$r = publicar_respuesta($api, 999999999, 'Ana', "Respuesta huérfana $marca");
registrar($resultados, 'CA-5', 'Se rechaza con su mensaje',
    $r['codigo'] === 404 && ($r['json']['exito'] ?? null) === false
        && ($r['json']['mensaje'] ?? '') === 'El comentario al que querés responder no existe',
    resumen_respuesta($r) . ' (esperado: 404)');

// ---------- D-08: no hay borrado ----------

echo "\nD-08 · F-MOD-001 · CA-4 (sin borrado)\n";
$r = pedir($api . '?id=' . urlencode((string) $padreId), 'DELETE');
$sigue = buscar_comentario($api, $contenidoPadre) !== null;
registrar($resultados, 'D-08', 'Un pedido de borrado se rechaza y el comentario sigue existiendo',
    $r['codigo'] === 403 && ($r['json']['exito'] ?? null) === false
        && ($r['json']['mensaje'] ?? '') === 'No tenés permiso para borrar' && $sigue,
    resumen_respuesta($r) . ' (esperado: 403)');

// ---------- Resumen ----------

$total = count($resultados);
$pasan = count(array_filter($resultados, function ($x) { return $x['ok']; }));
$fallan = array_values(array_filter($resultados, function ($x) { return !$x['ok']; }));

echo "\n----------------------------------------\n";
echo "Casos: $total · Pasan: $pasan · Fallan: " . count($fallan) . "\n";
if ($fallan) {
    echo 'Fallan: ' . implode(', ', array_map(function ($x) { return $x['id']; }, $fallan)) . "\n";
}
echo 'Resultado F-RES-001 (CA-2, CA-3, CA-5): ' . ($fallan ? 'NO PASA' : 'PASA') . "\n";

echo "\nVerificación manual en el navegador (no la cubre este script):\n";
echo "  [ ] CA-1 Cada comentario tiene \"Responder\" y abre un formulario con autor, contenido,\n";
echo "      \"Publicar respuesta\" y \"Cancelar\".\n";
echo "  [ ] CA-2 Tras publicar se ve \"Respuesta publicada correctamente\" y el formulario se cierra.\n";
echo "  [ ] CA-3 El mensaje de error aparece junto al formulario de respuesta y lo escrito queda.\n";
echo "  [ ] CA-4 Las respuestas no tienen botón \"Responder\".\n";
echo "  [ ] CA-6 \"Cancelar\" cierra el formulario sin guardar.\n";
echo "  [ ] CA-7 Presionar de nuevo \"Responder\" cierra el formulario.\n";

exit($fallan ? 1 : 0);
