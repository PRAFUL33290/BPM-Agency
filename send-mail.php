<?php
header('Access-Control-Allow-Origin: https://praful33290.github.io');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Requête invalide.']);
    exit;
}

function clean_text($value, $max = 1000) {
    $value = is_string($value) ? trim($value) : '';
    $value = str_replace(["\r", "\0"], ['', ''], $value);
    return mb_substr($value, 0, $max, 'UTF-8');
}

$name    = clean_text($data['name']    ?? '', 120);
$email   = clean_text($data['email']   ?? '', 200);
$note    = clean_text($data['note']    ?? '', 600);
$summary = clean_text($data['summary'] ?? '', 2000);

if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $summary === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Veuillez renseigner un nom et une adresse e-mail valides.']);
    exit;
}

$config = [
    'host'     => 'smtp.hostinger.com',
    'port'     => 465,
    'username' => 'contact@parvati-india.fr',
    'password' => '',
    'from'     => 'contact@parvati-india.fr',
    'to'       => 'contact@parvati-india.fr',
];

$configFile = __DIR__ . '/smtp-config.php';
if (is_file($configFile)) {
    $fileConfig = require $configFile;
    if (is_array($fileConfig)) {
        $config = array_merge($config, $fileConfig);
    }
}

if (empty($config['password'])) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Le mot de passe SMTP n\'est pas configuré sur le serveur.']);
    exit;
}

$subject = 'Demande spectacle Bollywood — BPM Agency — 12/12/2026';
$body  = "Bonjour Praful,\n\n";
$body .= "Une demande a été envoyée depuis le formulaire du devis BPM Agency.\n\n";
$body .= "Contact :\n";
$body .= "  Nom / organisation : {$name}\n";
$body .= "  E-mail : {$email}\n\n";
$body .= "Sélection :\n{$summary}\n";
if ($note !== '') {
    $body .= "\nMessage complémentaire :\n{$note}\n";
}
$body .= "\n---\nMessage envoyé automatiquement depuis le formulaire Parvati India.";

function smtp_read($socket) {
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
        $data .= $line;
        if (isset($line[3]) && $line[3] === ' ') break;
    }
    return $data;
}

function smtp_cmd($socket, $command, $expectedCodes) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtp_read($socket);
    $code = (int) substr($response, 0, 3);
    if (!in_array($code, (array) $expectedCodes, true)) {
        throw new RuntimeException('Erreur SMTP (' . $code . ') : ' . trim($response));
    }
    return $response;
}

function smtp_addr($email) {
    return '<' . str_replace(['<', '>', "\r", "\n"], '', $email) . '>';
}

try {
    $socket = fsockopen('ssl://' . $config['host'], (int) $config['port'], $errno, $errstr, 20);
    if (!$socket) {
        throw new RuntimeException('Connexion SMTP impossible : ' . $errstr);
    }
    stream_set_timeout($socket, 20);

    smtp_cmd($socket, null, 220);
    smtp_cmd($socket, 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'), 250);
    smtp_cmd($socket, 'AUTH LOGIN', 334);
    smtp_cmd($socket, base64_encode($config['username']), 334);
    smtp_cmd($socket, base64_encode($config['password']), 235);
    smtp_cmd($socket, 'MAIL FROM:' . smtp_addr($config['from']), 250);
    smtp_cmd($socket, 'RCPT TO:'   . smtp_addr($config['to']),   [250, 251]);
    smtp_cmd($socket, 'DATA', 354);

    $headers = [
        'From: Parvati India <' . $config['from'] . '>',
        'To: ' . $config['to'],
        'Reply-To: ' . $name . ' <' . $email . '>',
        'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $message = implode("\r\n", $headers) . "\r\n\r\n" . $body;
    $message = preg_replace('/^\./m', '..', $message);
    fwrite($socket, $message . "\r\n.\r\n");
    smtp_cmd($socket, null, 250);
    smtp_cmd($socket, 'QUIT', 221);
    fclose($socket);

    echo json_encode(['ok' => true, 'message' => 'Votre demande a bien été envoyée. Je vous répondrai sous 24h.']);

} catch (Throwable $e) {
    if (isset($socket) && is_resource($socket)) fclose($socket);
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'L\'envoi a échoué : ' . $e->getMessage()]);
}
