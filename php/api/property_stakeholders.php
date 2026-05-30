<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireAdmin();

$action = isset($_REQUEST['action']) ? strtolower(trim($_REQUEST['action'])) : 'list';
$database = new Database();
$db = $database->getConnection();

try {
    if ($action === 'list') {
        $propertyId = isset($_REQUEST['property_id']) ? trim($_REQUEST['property_id']) : null;
        if (!$propertyId) {
            jsonResponse(false, 'Property ID is required.');
        }
        $stmt = $db->prepare('SELECT ps.id, ps.property_id, ps.user_id, ps.ownership_percentage, ps.created_at, u.f_name, u.l_name, u.email FROM property_stakeholders ps JOIN users u ON ps.user_id = u.user_id WHERE ps.property_id = :property_id ORDER BY ps.created_at ASC');
        $stmt->bindParam(':property_id', $propertyId);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(true, 'Stakeholders retrieved.', $rows);
    }

    if ($action === 'add') {
        $propertyId = isset($_POST['property_id']) ? trim($_POST['property_id']) : null;
        $userId = isset($_POST['user_id']) ? trim($_POST['user_id']) : null;
        $percent = isset($_POST['ownership_percentage']) ? floatval($_POST['ownership_percentage']) : null;
        if (!$propertyId || !$userId || $percent === null) {
            jsonResponse(false, 'Property ID, user ID, and ownership percentage are required.');
        }
        if ($percent <= 0 || $percent > 100) {
            jsonResponse(false, 'Ownership percentage must be greater than 0 and up to 100.');
        }
        $stmt = $db->prepare('SELECT IFNULL(SUM(ownership_percentage), 0) AS total FROM property_stakeholders WHERE property_id = :property_id');
        $stmt->bindParam(':property_id', $propertyId);
        $stmt->execute();
        $sum = floatval($stmt->fetchColumn());
        if ($sum + $percent > 100) {
            jsonResponse(false, 'Total ownership percentage cannot exceed 100%.');
        }
        $stakeholderId = 'STK-' . bin2hex(random_bytes(5));
        $insert = $db->prepare('INSERT INTO property_stakeholders (id, property_id, user_id, ownership_percentage, created_at) VALUES (:id, :property_id, :user_id, :ownership_percentage, NOW())');
        $insert->execute([':id' => $stakeholderId, ':property_id' => $propertyId, ':user_id' => $userId, ':ownership_percentage' => $percent]);
        jsonResponse(true, 'Stakeholder added successfully.', ['stakeholder_id' => $stakeholderId]);
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : null;
        $percent = isset($_POST['ownership_percentage']) ? floatval($_POST['ownership_percentage']) : null;
        if (!$id || $percent === null) {
            jsonResponse(false, 'Stakeholder ID and ownership percentage are required.');
        }
        if ($percent <= 0 || $percent > 100) {
            jsonResponse(false, 'Ownership percentage must be greater than 0 and up to 100.');
        }
        $stmt = $db->prepare('SELECT property_id, ownership_percentage FROM property_stakeholders WHERE id = :id LIMIT 1');
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$record) {
            jsonResponse(false, 'Stakeholder entry not found.');
        }
        $stmt = $db->prepare('SELECT IFNULL(SUM(ownership_percentage), 0) - :current AS total FROM property_stakeholders WHERE property_id = :property_id');
        $stmt->execute([':current' => $record['ownership_percentage'], ':property_id' => $record['property_id']]);
        $sum = floatval($stmt->fetchColumn());
        if ($sum + $percent > 100) {
            jsonResponse(false, 'Total ownership percentage cannot exceed 100%.');
        }
        $update = $db->prepare('UPDATE property_stakeholders SET ownership_percentage = :ownership_percentage WHERE id = :id');
        $update->execute([':ownership_percentage' => $percent, ':id' => $id]);
        jsonResponse(true, 'Stakeholder updated successfully.');
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? trim($_POST['id']) : null;
        if (!$id) {
            jsonResponse(false, 'Stakeholder ID is required.');
        }
        $delete = $db->prepare('DELETE FROM property_stakeholders WHERE id = :id');
        $delete->bindParam(':id', $id);
        $delete->execute();
        jsonResponse(true, 'Stakeholder removed successfully.');
    }

    jsonResponse(false, 'Unknown action.');
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>