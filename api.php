<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permitir solicitudes desde cualquier origen
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
$host = 'mysql-39e78189-strawdb.g.aivencloud.com';
$port = 13232;
$dbname = 'defaultdb';
$user = 'avnadmin';
$password = 'AVNS_Zgg5O9UCGhyXR-LHwVs';

// Conexión a MySQL con mysqli
$mysqli = new mysqli($host, $user, $password, $dbname, $port);

// Verificar conexión
if ($mysqli->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Error de conexión: ' . $mysqli->connect_error]));
}

// Configurar SSL (requerido por Aiven)
$mysqli->ssl_set(null, null, null, null, null);
if (!$mysqli->real_connect($host, $user, $password, $dbname, $port, null, MYSQLI_CLIENT_SSL)) {
    die(json_encode(['success' => false, 'message' => 'Error SSL: ' . $mysqli->connect_error]));
}

// Crear la tabla de usuarios si no existe
$mysqli->query("
    CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(12) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

// Leer solicitud
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'login') {
    $username = $mysqli->real_escape_string($data['username'] ?? '');
    $password = $data['password'] ?? '';

    $result = $mysqli->query("SELECT * FROM users WHERE username = '$username'");
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    } else {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta']);
        }
    }
} elseif ($action === 'register') {
    $username = $mysqli->real_escape_string($data['username'] ?? '');
    $password = $data['password'] ?? '';

    $result = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE username = '$username'");
    $row = $result->fetch_assoc();
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'El usuario ya existe']);
        exit;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, password) VALUES ('$username', '$hashedPassword')";
    if ($mysqli->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Registro exitoso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar: ' . $mysqli->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}

$mysqli->close();
?>