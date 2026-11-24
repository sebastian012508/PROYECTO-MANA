<?php
/**
 * Plantilla HTML del PDF de eventos (la convierte Dompdf).
 * Variables: $reporte, $etiquetaUsuario, $nombreUsuario
 */
if (!isset($reporte, $etiquetaUsuario, $nombreUsuario)) {
    http_response_code(404);
    exit;
}
$e = static fn($texto): string => htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
$claseSeveridad = ['Alto' => 'sev-alto', 'Medio' => 'sev-medio', 'Bajo' => 'sev-bajo'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Listado de eventos de somnolencia</title>
<style>
  @page { size: A4 landscape; margin: 14mm 12mm; }
  body {
    margin: 0;
    color: #1f2937;
    font-family: "DejaVu Sans", sans-serif;
    font-size: 10px;
  }
  .encabezado { width: 100%; border-collapse: collapse; border-bottom: 3px solid #0b192c; margin-bottom: 14px; }
  .encabezado td { vertical-align: bottom; padding: 0 0 10px; }
  .sistema { margin: 0 0 4px; color: #0f766e; font-size: 9px; font-weight: bold; letter-spacing: 1px; }
  h1 { margin: 0; color: #0b192c; font-size: 19px; }
  .meta { text-align: right; line-height: 1.6; }
  .meta strong { color: #0b192c; }
  .filtros {
    border: 1px solid #cbd5e1;
    border-left: 4px solid #0f766e;
    background: #f8fafc;
    padding: 8px 12px;
    margin-bottom: 14px;
  }
  .filtros h2, .resumen h2 { margin: 0 0 5px; color: #0b192c; font-size: 11px; }
  .filtros p { margin: 0; line-height: 1.6; }
  .eventos { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
  .eventos thead th {
    background: #0b192c;
    color: #ffffff;
    text-align: left;
    padding: 7px 8px;
    font-size: 9.5px;
  }
  .eventos tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
  .eventos tbody tr.par td { background: #f1f5f9; }
  .eventos tr { page-break-inside: avoid; }
  .num { text-align: right; }
  .etiqueta {
    padding: 2px 8px;
    border-radius: 8px;
    color: #ffffff;
    font-size: 8.5px;
    font-weight: bold;
  }
  .sev-alto { background: #dc2626; }
  .sev-medio { background: #ea580c; }
  .sev-bajo { background: #16a34a; }
  .vacio {
    border: 2px dashed #94a3b8;
    background: #f8fafc;
    color: #475569;
    padding: 22px;
    text-align: center;
    font-size: 12px;
    margin-bottom: 14px;
  }
  .resumen { border: 1px solid #0b192c; padding: 10px 12px; page-break-inside: avoid; }
  .resumen table { width: 100%; border-collapse: separate; border-spacing: 8px 0; }
  .resumen td { width: 25%; background: #f1f5f9; padding: 8px; text-align: center; }
  .resumen span { display: block; color: #475569; font-size: 9px; }
  .resumen strong { display: block; margin-top: 3px; color: #0b192c; font-size: 17px; }
  .resumen .alto strong { color: #dc2626; }
  .resumen .medio strong { color: #ea580c; }
  .resumen .bajo strong { color: #16a34a; }
</style>
</head>
<body>

<table class="encabezado">
  <tr>
    <td>
      <p class="sistema">PROYECTO MANA - DETECCIÓN DE SOMNOLENCIA</p>
      <h1>Listado de eventos de somnolencia</h1>
    </td>
    <td class="meta">
      <div><strong>Generado:</strong> <?= $e($reporte['generado']) ?></div>
      <div><strong><?= $e($etiquetaUsuario) ?>:</strong> <?= $e($nombreUsuario) ?></div>
    </td>
  </tr>
</table>

<div class="filtros">
  <h2>Filtros aplicados</h2>
  <?php if ($reporte['aplicados']): ?>
    <p>
      <?php foreach ($reporte['aplicados'] as $criterio => $valor): ?>
        <strong><?= $e($criterio) ?>:</strong> <?= $e($valor) ?><br>
      <?php endforeach; ?>
    </p>
  <?php else: ?>
    <p>Ninguno (Todos los registros)</p>
  <?php endif; ?>
</div>

<?php if (!$reporte['eventos']): ?>
  <div class="vacio">No se encontraron eventos con los filtros seleccionados</div>
<?php else: ?>
  <table class="eventos">
    <thead>
      <tr>
        <th>ID Evento</th>
        <th>Fecha y hora</th>
        <th>Nombre del chofer</th>
        <th>Placa vehículo</th>
        <th>Tipo de alerta</th>
        <th>Severidad</th>
        <th class="num">Duración (s)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reporte['eventos'] as $i => $ev): ?>
        <tr class="<?= $i % 2 === 1 ? 'par' : '' ?>">
          <td>#<?= (int)$ev['id'] ?></td>
          <td><?= $e($ev['fecha']) ?></td>
          <td><?= $e($ev['chofer']) ?></td>
          <td><?= $e($ev['placa']) ?></td>
          <td><?= $e($ev['tipo']) ?></td>
          <td><span class="etiqueta <?= $claseSeveridad[$ev['severidad']] ?>"><?= $e($ev['severidad']) ?></span></td>
          <td class="num"><?= $e($ev['duracion']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<div class="resumen">
  <h2>Resumen</h2>
  <table>
    <tr>
      <td><span>Total de eventos</span><strong><?= (int)$reporte['resumen']['total'] ?></strong></td>
      <td class="alto"><span>Severidad Alta</span><strong><?= (int)$reporte['resumen']['Alto'] ?></strong></td>
      <td class="medio"><span>Severidad Media</span><strong><?= (int)$reporte['resumen']['Medio'] ?></strong></td>
      <td class="bajo"><span>Severidad Baja</span><strong><?= (int)$reporte['resumen']['Bajo'] ?></strong></td>
    </tr>
  </table>
</div>

</body>
</html>
