<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once '../config/Database.php';

$listingType = isset($_GET['listing_type']) ? strtolower(trim($_GET['listing_type'])) : null;
$listingId = isset($_GET['listing_id']) ? trim($_GET['listing_id']) : null;

if (!$listingType || !$listingId) {
    echo json_encode(['success' => false, 'message' => 'Listing type and listing id are required.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare('SELECT r.review_id, r.user_id, r.rating, r.title, r.comment, r.review_date, u.f_name, u.l_name FROM review r LEFT JOIN users u ON r.user_id = u.user_id WHERE LOWER(r.listing_type) = :listing_type AND r.listing_id = :listing_id AND r.approved = 1 ORDER BY r.review_date DESC');
    $stmt->execute([':listing_type' => $listingType, ':listing_id' => $listingId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statsStmt = $db->prepare('SELECT COUNT(*) AS review_count, AVG(rating) AS average_rating FROM review WHERE LOWER(listing_type) = :listing_type AND listing_id = :listing_id AND approved = 1');
    $statsStmt->execute([':listing_type' => $listingType, ':listing_id' => $listingId]);
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'review_count' => intval($stats['review_count'] ?? 0),
        'average_rating' => $stats['average_rating'] !== null ? round(floatval($stats['average_rating']), 2) : 0
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>