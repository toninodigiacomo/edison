<?php
// Proxy server-side vers l'API Energy-Charts (Fraunhofer ISE).
// But : éviter toute dépendance au comportement CORS/Origin du navigateur
// (l'appel direct depuis le front-end renvoyait un 404 intermittent côté
// cross-origin, alors que le même appel réussit systématiquement en
// server-to-server). Ajoute aussi un petit cache fichier pour ne pas
// solliciter l'API upstream à chaque chargement de page.

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store'); // c'est nous qui gérons le cache, pas le navigateur

$country = strtolower($_GET['country'] ?? 'ch');
$country = preg_replace('/[^a-z\-]/', '', $country) ?: 'ch';

$startTimestamp = time() - 7 * 24 * 3600;
$startTimestamp -= $startTimestamp % 3600; // arrondi à l'heure pleine inférieure (résolution de l'API)
$endTimestamp = time();
$endTimestamp -= $endTimestamp % 3600;

$upstream = 'https://api.energy-charts.info/v2/public_power?' . http_build_query([
    'country' => $country,
    'start'   => gmdate('Y-m-d\TH:i\Z', $startTimestamp),
    'end'     => gmdate('Y-m-d\TH:i\Z', $endTimestamp),
]);
$cacheFile = sys_get_temp_dir() . '/courant_public_power_' . $country . '.json';
$cacheTtl = 900; // secondes — les données suisses ont ~1 jour de décalage, inutile de solliciter l'API trop souvent

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    readfile($cacheFile);
    exit;
}

$context = stream_context_create([
    'http' => [
        'method'          => 'GET',
        'protocol_version' => 1.1,
        'header'          => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36\r\n" .
                             "Accept: application/json\r\n" .
                             "Connection: close\r\n",
        'timeout'         => 10,
        'ignore_errors'   => true,
    ],
]);

$body = @file_get_contents($upstream, false, $context);
$lastErr = error_get_last();
$statusLine = $http_response_header[0] ?? '';
$ok = $body !== false && strpos($statusLine, '200') !== false;

if ($ok) {
    @file_put_contents($cacheFile, $body);
    echo $body;
    exit;
}

// L'upstream a échoué : on sert une réponse un peu périmée plutôt que rien,
// si on en a une sous la main.
if (is_file($cacheFile)) {
    readfile($cacheFile);
    exit;
}

http_response_code(502);
echo json_encode([
    'error' => 'upstream_unreachable',
    'upstream_status' => $statusLine,
    'upstream_url' => $upstream,
    'php_warning' => $lastErr['message'] ?? null,
    'allow_url_fopen' => ini_get('allow_url_fopen'),
]);
