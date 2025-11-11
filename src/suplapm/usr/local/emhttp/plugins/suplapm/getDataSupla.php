<?php
header('Content-Type: application/json; charset=utf-8');

$cfg = @parse_ini_file('/boot/config/plugins/suplapm/suplapm.cfg');
$url = isset($cfg['DEVICE_URL']) ? trim($cfg['DEVICE_URL']) : '';
if ($url === '') {
	http_response_code(400);
	echo json_encode(['error' => 'no_device_url']);
	exit;
}

$ch = curl_init(rtrim($url, '/') . '/read?format=json');
curl_setopt_array($ch, [
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_TIMEOUT => 5,
]);
$res = curl_exec($ch);
$err = curl_errno($ch) ? curl_error($ch) : null;
curl_close($ch);
if ($res === false) {
	http_response_code(502);
	echo json_encode(['error' => 'curl', 'msg' => $err]);
	exit;
}

$data = json_decode($res, true);
if (!is_array($data)) {
	http_response_code(502);
	echo json_encode(['error' => 'invalid_json']);
	exit;
}

$p = isset($data['phases'][0]) ? $data['phases'][0] : [];

$out = [
	'Power' => $p['powerActive'] ?? null,
	'Voltage' => $p['voltage'] ?? null,
	'Current' => $p['current'] ?? null,
	'Factor' => isset($p['powerFactor']) ? $p['powerFactor'] * 100 : null,
	'Energy' => $p['totalForwardActiveEnergy'] ?? null,
	'ApparentPower' => $p['powerApparent'] ?? null,
	'ReactivePower' => $p['powerReactive'] ?? null,
	'Costs_Price' => $data['pricePerUnit'] ?? null,
	'Costs_Unit' => $data['currency'] ?? null,
];

echo json_encode($out);

?>