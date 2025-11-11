<?php
// getDataSupla.php
// Returns JSON with current values from Supla device

header('Content-Type: application/json; charset=utf-8');

$cfgFile = '/boot/config/plugins/suplapm/suplapm.cfg';
if (!file_exists($cfgFile)) {
	http_response_code(500);
	echo json_encode(['error' => 'Config file missing']);
	exit;
}

$suplapm_cfg = parse_ini_file($cfgFile);
$suplapm_device_url = isset($suplapm_cfg['DEVICE_URL']) ? trim($suplapm_cfg['DEVICE_URL']) : "";

if ($suplapm_device_url === "") {
	http_response_code(400);
	echo json_encode(['error' => 'Device URL is missing']);
	exit;
}

// Simple helper to safely get nested values
function safe_get($arr, $keys, $default = null) {
	$cur = $arr;
	foreach ($keys as $k) {
		if (!is_array($cur) || !array_key_exists($k, $cur)) return $default;
		$cur = $cur[$k];
	}
	return $cur;
}

// Fetch JSON from device
function getvalue($url) {
	$curl = curl_init($url . "/read?format=json");
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_TIMEOUT, 5);
	// If you need to disable SSL checks (not recommended), uncomment below
	// curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

	$result = curl_exec($curl);
	$err = null;
	if ($result === false) {
		$err = curl_error($curl);
	}
	curl_close($curl);

	if ($result === false) {
		return ['error' => 'curl_error', 'message' => $err];
	}

	$decoded = json_decode($result, true);
	if ($decoded === null) {
		return ['error' => 'invalid_json', 'raw' => $result];
	}
	return $decoded;
}

$temp = getvalue($suplapm_device_url);
if (is_array($temp) && isset($temp['error'])) {
	http_response_code(502);
	echo json_encode($temp);
	exit;
}

if (!is_array($temp)) {
	http_response_code(502);
	echo json_encode(['error' => 'invalid_response', 'message' => 'Device returned invalid response']);
	exit;
}

$phase0 = safe_get($temp, ['phases', 0], []);

$powerFactor = safe_get($phase0, ['powerFactor'], null);
$factorPercent = is_numeric($powerFactor) ? ($powerFactor * 100) : null;

$json = [
	'Power' => safe_get($phase0, ['powerActive'], null),
	'Voltage' => safe_get($phase0, ['voltage'], null),
	'Current' => safe_get($phase0, ['current'], null),
	'Factor' => $factorPercent,
	'Energy' => safe_get($phase0, ['totalForwardActiveEnergy'], null),
	'ApparentPower' => safe_get($phase0, ['powerApparent'], null),
	'ReactivePower' => safe_get($phase0, ['powerReactive'], null),
	'Costs_Price' => safe_get($temp, ['pricePerUnit'], null),
	'Costs_Unit' => safe_get($temp, ['currency'], null),
	'connected' => safe_get($temp, ['connected'], null)
];

echo json_encode($json);

?>