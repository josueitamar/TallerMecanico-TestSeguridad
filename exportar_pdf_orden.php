<?php
require_once('tcpdf/tcpdf.php');
require_once 'conexion_base.php';

$ordenNum = $_GET['orden'] ?? '';

if (!$ordenNum) {
    die('Número de orden no especificado.');
}
// OBTENER DATOS DE LA ORDEN
$stmt = $conexion->prepare(" SELECT o.orden_numero, o.orden_fecha, o.vehiculo_patente,
           c.cliente_nombre, c.cliente_telefono, v.vehiculo_marca, v.vehiculo_modelo, v.vehiculo_anio,
           s.servicio_nombre, ot.complejidad, ot.orden_kilometros,
           ot.orden_comentario, ot.orden_estado
    FROM ordenes o
    JOIN vehiculos v ON o.vehiculo_patente = v.vehiculo_patente
    JOIN clientes c ON v.cliente_DNI = c.cliente_DNI
    JOIN orden_trabajo ot ON o.orden_numero = ot.orden_numero
    JOIN servicios s ON ot.servicio_codigo = s.servicio_codigo
    WHERE o.orden_numero = :orden
");
$stmt->execute(['orden' => $ordenNum]);
$datos = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$datos) {
    die('Orden no encontrada.');
}

// CREAR PDF
$pdf = new TCPDF();
$pdf->SetMargins(10, 35, 10); // ESPACIO PARA ENCABEZADO
$pdf->AddPage();

//ENCABEZADO 1
//Logo alineado a la izquierda
$pdf->Image('iconos/WA_Sport_pdf.jpg', 10, 15, 30); // x=10, y=10, ancho=30mm
//Fuente
$pdf->SetFont('helvetica', '', 10);
// Dirección
$pdf->SetXY(45, 15);
$pdf->Write(0, 'Dirección: Paso 1418, Ramos Mejía, Provincia de Buenos Aires');
// Teléfono
$pdf->Image('iconos/whatsapp_pdf.jpg', 45, 20, 4, 4);
$pdf->SetXY(50, 20);
$pdf->Write(0, 'Tel: (011) 5717-2522');
// Email
$pdf->Image('iconos/arroba_pdf.jpg', 45, 25, 4, 4);
$pdf->SetXY(50, 25);
$pdf->Write(0, 'Email: wasportaller@gmail.com');

/*
//ENCABEZADO 2

// ENCABEZADO COMO IMAGEN COMPLETA
$pdf->Image('iconos/encabezado_wa_sport.png', 10, 10, 170); // x, y, ancho, alto
$pdf->Ln(30); // Salto debajo del encabezado
*/


// LINEA DEBAJO ENCABEZADO
$pdf->SetLineWidth(0.3); // Grosor de la línea
$pdf->Line(10, 39, 200, 39); // Línea horizontal (X1, Y1, X2, Y2)



// TITULO
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, "Detalle de Orden Nº {$datos['orden_numero']}", 0, 1, 'C');

$pdf->SetFont('helvetica', '', 12);
$pdf->Ln(3);

// DATOS DE VEHICULO Y CLIENTE
$pdf->Write(0, "Fecha: {$datos['orden_fecha']}\n", '', 0, 'L');
$pdf->Ln(6);
$pdf->Write(0, "Cliente: {$datos['cliente_nombre']} - Tel: {$datos['cliente_telefono']}", '', 0, 'L');
$pdf->Ln(6);
$pdf->Write(0, "Vehículo: {$datos['vehiculo_patente']} - {$datos['vehiculo_marca']} {$datos['vehiculo_modelo']} ({$datos['vehiculo_anio']})", '', 0, 'L');
$pdf->Ln(6);
$pdf->Write(0, "Servicio: {$datos['servicio_nombre']}", '', 0, 'L');
$pdf->Ln(6);
$pdf->Write(0, "Kilometraje: {$datos['orden_kilometros']} km", '', 0, 'L');
$pdf->Ln(6);
$pdf->Write(0, "Complejidad: {$datos['complejidad']}", '', 0, 'L');
$pdf->Ln(6);
$estadoOrden = ($datos['orden_estado'] == 1) ? 'Finalizada' : 'Pendiente';
$pdf->Write(0, "Estado: {$estadoOrden}", '', 0, 'L');
$pdf->Ln(6);
$pdf->Write(0, "Comentario: {$datos['orden_comentario']}", '', 0, 'L');

$pdf->Output("orden_{$ordenNum}.pdf", 'I');
?>
