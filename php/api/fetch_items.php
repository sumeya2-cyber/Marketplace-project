<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../config/Database.php';

$type = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'properties';
$categoryId = isset($_GET['category']) ? trim($_GET['category']) : null;

$database = new Database();
$db = $database->getConnection();
$query = '';
$params = [];

switch ($type) {
    case 'properties':
        $query = "SELECT p.property_id AS id,
                         CONCAT_WS(', ', p.address, p.city) AS title,
                         pl.price AS price,
                         CONCAT_WS(', ', p.address, p.city) AS location,
                         p.description AS description,
                         pc.category_name AS category_name,
                         COALESCE(pi.image_url, '') AS image_path
                  FROM property_listing pl
                  JOIN property p ON pl.property_id = p.property_id
                  LEFT JOIN property_category pc ON p.category_id = pc.category_id
                  LEFT JOIN property_image pi ON p.property_id = pi.property_id
                  WHERE LOWER(pl.status) = 'approved'";
        if ($categoryId) {
            $query .= " AND p.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        break;

    case 'products':
        $query = "SELECT pr.product_id AS id,
                         pr.product_name AS title,
                         pl.price AS price,
                         pr.description AS description,
                         COALESCE(b.brand_name, '') AS brand_name,
                         COALESCE(pi.image_url, '') AS image_path,
                         COALESCE(pc.category_name, '') AS category_name,
                         '' AS location
                  FROM product_listing pl
                  JOIN product pr ON pl.product_id = pr.product_id
                  LEFT JOIN brand b ON pr.brand_id = b.brand_id
                  LEFT JOIN product_image pi ON pr.product_id = pi.product_id
                  LEFT JOIN product_category pc ON pr.category_id = pc.category_id
                  WHERE LOWER(pl.status) = 'approved'";
        if ($categoryId) {
            $query .= " AND pr.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        break;

    case 'services':
        $query = "SELECT sr.request_id AS id,
                         CONCAT('Service Request', ' - ', COALESCE(sc.category_name, 'General')) AS title,
                         sr.budget AS price,
                         '' AS location,
                         sr.description AS description,
                         COALESCE(sc.category_name, '') AS category_name,
                         '' AS image_path
                  FROM service_request sr
                  LEFT JOIN service_category sc ON sr.category_id = sc.category_id
                  WHERE LOWER(sr.status) = 'open'";
        if ($categoryId) {
            $query .= " AND sr.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid listing type.']);
        exit;
}

try {
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch items: ' . $e->getMessage()]);
}
?>