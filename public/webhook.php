<?php
$secret = 'clothonic_webhook_secret_2025';
$payload = file_get_contents('php://input');
$signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

if (!hash_equals($signature, $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '')) {
    http_response_code(403);
    exit('Unauthorized');
}

exec('/home/clothonic/htdocs/laravel.netpilotdigital.com/deploy.sh > /var/log/clothonic-deploy.log 2>&1 &');
echo 'Deploy triggered!';
