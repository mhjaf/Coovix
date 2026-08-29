<?php

// Public mail settings. Keep credentials outside the web root in
// /etc/coovix/mail.php, or provide them through environment variables.
$privateConfigPath = getenv('COOVIX_MAIL_CONFIG') ?: '/etc/coovix/mail.php';
$privateConfig = [];

if (is_readable($privateConfigPath)) {
    $loadedConfig = require $privateConfigPath;
    if (is_array($loadedConfig)) {
        $privateConfig = $loadedConfig;
    }
}

$environmentPassword = getenv('COOVIX_SMTP_PASSWORD');

return array_merge([
    // Zoho Mail SMTP for an organization using a custom domain.
    'smtp_host' => getenv('COOVIX_SMTP_HOST') ?: 'smtppro.zoho.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'info@coovix.com',
    'smtp_password' => $environmentPassword !== false ? $environmentPassword : '',

    // Contact-form delivery.
    'from_email' => 'info@coovix.com',
    'from_name' => 'Coovix Website',
    'to_email' => 'info@coovix.com',
    'reply_to_name' => 'Coovix Support',
    'subject_prefix' => 'Coovix Website - ',
    'send_copy_to_sender' => false,

    // Never expose SMTP diagnostics to public form users in production.
    'enable_debug' => false,
    'allowed_domains' => [
        'coovix.com',
        'www.coovix.com',
        'localhost',
    ],
], $privateConfig);

