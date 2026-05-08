<?php
    $marca = isset($_GET['marca']) ? $_GET['marca'] : '';
    $resultados = [];
    $error = '';

    if ($marca) {
        $url = "https://vpic.nhtsa.dot.gov/api/vehicles/GetModelsForMake/" . urlencode($marca) . "?format=json";
        $response = @file_get_contents($url);

        if ($response === FALSE) {
            $error = "No se pudo conectar con el servicio de NHTSA.";
        } else {
            $data = json_decode($response, true);
            if (isset($data['Results'])) {
                $resultados = $data['Results'];
            } else {
                $error = "No se encontraron modelos para la marca ingresada.";
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Modelos de Vehículos</title>
    <link rel="stylesheet" href="estilopagina.css?v=<?= time() ?>">
    <style>
        .container {
            min-height: 80vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .search-form {
            margin-bottom: 30px;
            text-align: center;
        }
        .search-form input[type="text"] {
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
            width: 250px;
            font-family: 'AlumniSans_Regular', sans-serif;
            font-size: 18px;
        }
        .search-form button {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            background-color: #FF7400;
            color: white;
            cursor: pointer;
            font-family: 'Big_Shoulders_Medium', sans-serif;
            font-size: 18px;
        }
        .search-form button:hover {
            background-color: #cc5d00;
        }
        .table-container {
            width: 100%;
            max-width: 800px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #FF8E31;
            padding: 12px;
            text-align: left;
            font-family: 'AlumniSans_Regular', sans-serif;
            font-size: 18px;
        }
        th {
            background-color: #ff8e31a8;
            font-family: 'Big_Shoulders_Medium', sans-serif;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .error-msg {
            color: red;
            margin-bottom: 20px;
            font-family: 'AlumniSans_Medium', sans-serif;
        }
        h2 {
            font-family: 'Big_Shoulders_ExtraBold', sans-serif;
            color: rgb(153, 0 , 33);
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <?php include("navegador.php"); ?>

    <section class="container">
        <h2>Consultar Modelos por Marca</h2>

        <div class="search-form">
            <form action="consultar_modelos.php" method="GET">
                <input type="text" name="marca" placeholder="Ingrese marca (ej: mazda)" value="<?= htmlspecialchars($marca) ?>" required>
                <button type="submit">Consultar</button>
            </form>
        </div>

        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($marca && !empty($resultados)): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Modelo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $modelo): ?>
                            <tr>
                                <td><?= htmlspecialchars($modelo['Make_Name']) ?></td>
                                <td><?= htmlspecialchars($modelo['Model_Name']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php elseif ($marca && empty($resultados) && !$error): ?>
            <p>No se encontraron resultados para "<?= htmlspecialchars($marca) ?>".</p>
        <?php endif; ?>
    </section>

    <?php include("piedepagina.php"); ?>
</body>
</html>
