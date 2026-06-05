<?php
class PaymentConfig {
    public static function getConfig() {
        return [
            'callback_secret' => 'marketplace_payment_secret_2026',
            'home_url' => 'http://localhost/MarketPlace',
            'default_admin_email' => 'admin@marketplace.local'
        ];
    }

    public static function getCallbackSecret() {
        $config = self::getConfig();
        return $config['callback_secret'];
    }

    public static function getAdminEmail() {
        $config = self::getConfig();
        return $config['default_admin_email'];
    }
}
?>