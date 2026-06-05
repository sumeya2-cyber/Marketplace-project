<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once '../config/Database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/mailer.php';

$input = json_decode(file_get_contents('php://input'), true);
$listingType = isset($input['listing_type']) ? strtolower(trim($input['listing_type'])) : null;
$listingId = isset($input['listing_id']) ? trim($input['listing_id']) : null;
$rating = isset($input['rating']) ? intval($input['rating']) : null;
$comment = isset($input['comment']) ? trim($input['comment']) : null;
$title = isset($input['title']) ? trim($input['title']) : null;
$relatedOrderId = isset($input['related_order_id']) ? trim($input['related_order_id']) : null;
$relatedContractId = isset($input['related_contract_id']) ? trim($input['related_contract_id']) : null;
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$guestToken = isset($input['guest_token']) ? trim($input['guest_token']) : null;
$ownerId = $userId ?: $guestToken;
$guestEmail = isset($input['guest_email']) ? trim($input['guest_email']) : null;

if (!$ownerId) {
    jsonResponse(false, 'Please login or provide the guest token to submit a review.');
}

if (!$listingType || !$listingId || !$rating || $rating < 1 || $rating > 5) {
    jsonResponse(false, 'Listing type, listing id and a rating between 1 and 5 are required.');
}

$database = new Database();
$db = $database->getConnection();

try {
    $eligible = false;
    $hasRegisteredUser = !empty($userId);
    $ownershipCondition = $hasRegisteredUser
        ? 'o.user_id = :user_id'
        : '(o.user_id IS NULL AND o.guest_token = :guest_token)';

    if ($listingType === 'product' || $listingType === 'property') {
        if ($relatedOrderId) {
            $orderStmt = $db->prepare('SELECT o.order_id FROM orders o JOIN order_item oi ON o.order_id = oi.order_id WHERE o.order_id = :order_id AND ' . $ownershipCondition . ' AND o.status IN ("Completed","Delivered","Paid") AND ((:listingType = "product" AND oi.product_id = :listingId1) OR (:listingType = "property" AND oi.property_id = :listingId2)) LIMIT 1');
            $params = [
                ':order_id' => $relatedOrderId,
                ':listingType' => $listingType,
                ':listingId1' => $listingId,
                ':listingId2' => $listingId
            ];
            if ($hasRegisteredUser) {
                $params[':user_id'] = $userId;
            } else {
                $params[':guest_token'] = $guestToken;
            }
            $orderStmt->execute($params);
            $eligible = $orderStmt->rowCount() > 0;
        } else {
            $orderStmt = $db->prepare('SELECT o.order_id FROM orders o JOIN order_item oi ON o.order_id = oi.order_id WHERE ' . $ownershipCondition . ' AND o.status IN ("Completed","Delivered","Paid") AND ((:listingType = "product" AND oi.product_id = :listingId1) OR (:listingType = "property" AND oi.property_id = :listingId2)) LIMIT 1');
            $params = [
                ':listingType' => $listingType,
                ':listingId1' => $listingId,
                ':listingId2' => $listingId
            ];
            if ($hasRegisteredUser) {
                $params[':user_id'] = $userId;
            } else {
                $params[':guest_token'] = $guestToken;
            }
            $orderStmt->execute($params);
            $found = $orderStmt->fetch(PDO::FETCH_ASSOC);
            $eligible = $found !== false;
            $relatedOrderId = $found['order_id'] ?? null;
        }
    } elseif ($listingType === 'service') {
        if (!$relatedContractId) {
            jsonResponse(false, 'A completed contract ID is required to review a service.');
        }

        $contractStmt = $db->prepare('SELECT sc.contract_id, sc.status, sa.request_id, sr.user_id AS requester_id, spp.user_id AS provider_id FROM service_contract sc JOIN service_application sa ON sc.application_id = sa.application_id JOIN service_request sr ON sa.request_id = sr.request_id JOIN service_provider_profile spp ON sa.profile_id = spp.profile_id WHERE sc.contract_id = :contract_id LIMIT 1');
        $contractStmt->bindParam(':contract_id', $relatedContractId);
        $contractStmt->execute();
        $contract = $contractStmt->fetch(PDO::FETCH_ASSOC);

        if ($contract && strtolower($contract['status']) === 'completed' && ($contract['requester_id'] === $ownerId || $contract['provider_id'] === $ownerId)) {
            $eligible = true;
        }
    }

    if (!$eligible) {
        jsonResponse(false, 'You are not authorized to review this listing. Reviews are allowed only after completed purchases or contracts.');
    }

    $duplicateSql = 'SELECT review_id FROM review WHERE LOWER(listing_type) = :listing_type AND listing_id = :listing_id';
    if ($relatedOrderId) {
        $duplicateSql .= ' AND related_order_id = :related_order_id';
    }
    if ($relatedContractId) {
        $duplicateSql .= ' AND related_contract_id = :related_contract_id';
    }
    if ($userId) {
        $duplicateSql .= ' AND user_id = :user_id';
    } else {
        $duplicateSql .= ' AND user_id IS NULL';
    }

    $dupStmt = $db->prepare($duplicateSql);
    $dupParams = [
        ':listing_type' => $listingType,
        ':listing_id' => $listingId
    ];
    if ($relatedOrderId) {
        $dupParams[':related_order_id'] = $relatedOrderId;
    }
    if ($relatedContractId) {
        $dupParams[':related_contract_id'] = $relatedContractId;
    }
    if ($userId) {
        $dupParams[':user_id'] = $userId;
    }
    $dupStmt->execute($dupParams);
    if ($dupStmt->rowCount() > 0) {
        jsonResponse(false, 'A review has already been submitted for this completed transaction.');
    }

    $reviewId = 'REV-' . bin2hex(random_bytes(5));
    $insert = $db->prepare('INSERT INTO review (review_id, user_id, recipient_id, listing_type, listing_id, rating, title, comment, review_date, related_order_id, related_contract_id, approved) VALUES (:review_id, :user_id, NULL, :listing_type, :listing_id, :rating, :title, :comment, NOW(), :related_order_id, :related_contract_id, 1)');
    $insert->execute([
        ':review_id' => $reviewId,
        ':user_id' => $userId,
        ':listing_type' => $listingType,
        ':listing_id' => $listingId,
        ':rating' => $rating,
        ':title' => $title,
        ':comment' => $comment,
        ':related_order_id' => $relatedOrderId,
        ':related_contract_id' => $relatedContractId
    ]);

    $customerName = 'Customer';
    $customerEmail = null;
    if ($userId) {
        $userStmt = $db->prepare('SELECT email, f_name, l_name FROM users WHERE user_id = :user_id LIMIT 1');
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);
        $customerName = $user ? trim($user['f_name'] . ' ' . $user['l_name']) : $customerName;
        $customerEmail = $user['email'] ?? null;
    } elseif ($guestEmail) {
        $customerName = 'Customer';
        $customerEmail = $guestEmail;
    }

    if ($customerEmail) {
        $subject = 'Review Submitted';
        $body = buildNotificationEmail('Review Received', "Your review for $listingType $listingId has been submitted.");
        sendMarketplaceEmail($customerEmail, $customerName, $subject, $body);
    }

    jsonResponse(true, 'Review submitted successfully.', ['review_id' => $reviewId]);
} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
?>