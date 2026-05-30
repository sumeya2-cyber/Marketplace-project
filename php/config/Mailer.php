<?php
class MailerConfig {
    public static function getConfig() {
        return [
            'host' => 'smtp.example.com',
            'username' => 'smtp-user@example.com',
            'password' => 'your-smtp-password',
            'port' => 587,
            'smtp_secure' => 'tls',
            'from_email' => 'no-reply@marketplace.local',
            'from_name' => 'MarketPlace Notifications',
            'reply_to' => 'support@marketplace.local',
            'is_smtp' => true,
            'smtp_debug' => 0
        ];
    }
}
?>
