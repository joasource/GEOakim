<?php
date_default_timezone_set('America/Sao_Paulo');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';

$entry = [
    'timestamp'     => date("Y-m-d H:i:s"),
    'ip'            => $ip,
    'user_agent'    => $user_agent,
    'gpu_vendor'    => $data['gpu_vendor'] ?? 'N/A',
    'gpu_renderer'  => $data['gpu_renderer'] ?? 'N/A',
    'screen'        => $data['screen'] ?? 'N/A',
    'platform'      => $data['platform'] ?? 'N/A',
    'lang'          => $data['lang'] ?? 'N/A',
    'timezone'      => $data['timezone'] ?? 'N/A',
    'latitude'      => $data['lat'] ?? null,
    'longitude'     => $data['lon'] ?? null,
    'accuracy'      => $data['accuracy'] ?? null,
    'geo'           => $data['geo'] ?? false
];

// Salvar log bruto (linha de texto)
$log_line = "[{$entry['timestamp']}] "
  . "IP: {$entry['ip']} | "
  . "UA: {$entry['user_agent']} | "
  . "GPU: {$entry['gpu_vendor']} / {$entry['gpu_renderer']} | "
  . "Res: {$entry['screen']} | "
  . "Plataforma: {$entry['platform']} | "
  . "Idioma: {$entry['lang']} | "
  . "Fuso: {$entry['timezone']}";

if ($entry['geo']) {
  $log_line .= " | Geo: ({$entry['latitude']}, {$entry['longitude']}) ±{$entry['accuracy']}m";
} else {
  $log_line .= " | Geo: NÃO COLETADO";
}

$log_line .= "\n";

file_put_contents("log.txt", $log_line, FILE_APPEND);

// Salvar no JSON estruturado
$data_file = 'data.json';
$existing = [];

if (file_exists($data_file)) {
    $existing = json_decode(file_get_contents($data_file), true) ?? [];
}

$existing[] = $entry;

file_put_contents($data_file, json_encode($existing, JSON_PRETTY_PRINT));
