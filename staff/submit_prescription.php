<?php
// submit_prescription.php
session_start();
header('Content-Type: application/json');

try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : null;
$patient_name = isset($_POST['patient_name']) ? $_POST['patient_name'] : null;
$medicines = isset($_POST['medicines']) ? $_POST['medicines'] : null;
$notes = isset($_POST['notes']) ? $_POST['notes'] : null;
$prescribed_by = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Staff';

if ($patient_id && $patient_name && $medicines) {
    $stmt = $db->prepare('INSERT INTO pending_prescriptions (patient_id, patient_name, prescribed_by, medicines, notes) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$patient_id, $patient_name, $prescribed_by, $medicines, $notes]);
    // Log action
    $user_email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : $prescribed_by;
    $logDb = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8', 'root', '');
    $logDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $logDb->prepare('CREATE TABLE IF NOT EXISTS logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        user_email VARCHAR(255),
        action VARCHAR(255),
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
    )')->execute();
    $logDb->prepare('INSERT INTO logs (user_email, action) VALUES (?, ?)')->execute([
        $user_email,
        'Submitted prescription for patient: ' . $patient_name
    ]);
    echo json_encode(['success' => true, 'message' => 'Prescription saved to pending.']);
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required data.']);
}
?>
