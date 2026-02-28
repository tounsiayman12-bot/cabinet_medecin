<?php
require_once '../includes/config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$cin = isset($data['cin']) ? sanitize($data['cin']) : '';
$phone = isset($data['phone']) ? sanitize($data['phone']) : '';

$conn = getDBConnection();

$patient = null;

if (!empty($cin)) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE CIN = ?");
    $stmt->bind_param("s", $cin);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$patient && !empty($phone)) {
    $stmt = $conn->prepare("SELECT * FROM patients WHERE phone = ?");
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
    }
    $stmt->close();
}

$conn->close();

if ($patient) {
    echo json_encode(['success' => true, 'patient' => $patient]);
} else {
    echo json_encode(['success' => false, 'message' => 'Patient non trouvé']);
}
?>
