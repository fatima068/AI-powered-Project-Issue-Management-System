<?php
/**
 * ai_client.php — Thin wrapper around the Python/Flask AI service.
 * Drop in project root (alongside connect_db.php).
 */

if (!defined('AI_API_BASE')) {
    define('AI_API_BASE', 'http://127.0.0.1:5001');
}

function ai_call($path, $body = null) {
    $url = AI_API_BASE . $path;
    $ch  = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT,        30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
    }

    $raw  = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        return [
            'error' => 'AI service unreachable. Is Flask running on '
                . AI_API_BASE . '? (' . $err . ')',
        ];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'error'     => 'Malformed response from AI service.',
            'raw'       => substr($raw, 0, 400),
            'http_code' => $code,
        ];
    }
    return $decoded;
}

function ai_error_banner($response) {
    if (!is_array($response) || empty($response['error'])) return '';
    return '<div class="alert alert-warning">'
         . '<strong>AI service error:</strong> '
         . htmlspecialchars($response['error'])
         . '</div>';
}
