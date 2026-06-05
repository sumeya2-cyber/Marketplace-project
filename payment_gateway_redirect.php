<?php
require_once __DIR__ . '/php/config/Database.php';
require_once __DIR__ . '/php/config/Payment.php';

$orderId = isset($_GET['order_id']) ? trim($_GET['order_id']) : null;
$paymentId = isset($_GET['payment_id']) ? trim($_GET['payment_id']) : null;
$methodId = isset($_GET['method_id']) ? trim($_GET['method_id']) : null;

$database = new Database();
$db = $database->getConnection();
$order = null;
$method = null;

if ($orderId && $paymentId && $methodId) {
    try {
        $stmt = $db->prepare('SELECT order_id, total_amount FROM orders WHERE order_id = :order_id LIMIT 1');
        $stmt->execute([':order_id' => $orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        $methodStmt = $db->prepare('SELECT method_name, provider_name FROM payment_method WHERE method_id = :method_id LIMIT 1');
        $methodStmt->execute([':method_id' => $methodId]);
        $method = $methodStmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $order = null;
        $method = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace Payment Redirect</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="modal open" style="align-items:center; justify-content:center;">
        <div class="modal-content small" style="max-width:520px; text-align:center;">
            <h2>Continue to Payment</h2>
            <?php if (!$order || !$method): ?>
                <p>Unable to resolve the payment request.</p>
                <p><a href="index.php">Return to marketplace</a></p>
            <?php else: ?>
                <p>You are being redirected to <strong><?= htmlentities($method['provider_name'] ?: $methodId) ?></strong>.</p>
                <p>Order ID: <strong><?= htmlentities($orderId) ?></strong></p>
                <p>Amount: <strong>$<?= number_format(floatval($order['total_amount']), 2) ?></strong></p>
                <button id="completePayment" class="btn-order" style="width:auto; padding:0.9rem 1.6rem;">Complete Payment</button>
                <div id="providerMessage" style="margin-top:1rem; color:#444;"></div>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($order && $method): ?>
    <script>
    async function completePayment() {
        const button = document.getElementById('completePayment');
        button.disabled = true;
        button.textContent = 'Processing...';

        const payload = {
            callback_token: '<?= addslashes(PaymentConfig::getCallbackSecret()) ?>',
            order_id: '<?= addslashes($orderId) ?>',
            payment_id: '<?= addslashes($paymentId) ?>',
            method_id: '<?= addslashes($methodId) ?>',
            payment_status: 'Completed',
            amount: <?= number_format(floatval($order['total_amount']), 2, '.', '') ?>,
            transaction_reference: 'TRX-<?= time() ?>-<?= bin2hex(random_bytes(4)) ?>',
            gateway_status: 'completed'
        };

        try {
            const response = await fetch('php/api/payment_callback.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json();
            const message = document.getElementById('providerMessage');
            if (result.success) {
                message.innerHTML = '<strong>Payment verified successfully.</strong> Redirecting back to marketplace...';
                setTimeout(() => {
                    window.location.href = 'index.php?payment_success=1&order_id=' + encodeURIComponent('<?= addslashes($orderId) ?>');
                }, 1800);
            } else {
                message.innerHTML = '<strong>Error:</strong> ' + (result.message || 'Could not confirm payment.');
                button.disabled = false;
                button.textContent = 'Try Again';
            }
        } catch (err) {
            document.getElementById('providerMessage').innerHTML = '<strong>Network error.</strong> Please try again.';
            console.error(err);
            button.disabled = false;
            button.textContent = 'Try Again';
        }
    }

    document.getElementById('completePayment').addEventListener('click', completePayment);
    </script>
    <?php endif; ?>
</body>
</html>
