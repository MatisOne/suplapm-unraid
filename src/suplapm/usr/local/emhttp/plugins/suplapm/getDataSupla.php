<?php
$suplapm_cfg = parse_ini_file( "/boot/config/plugins/suplapm/suplapm.cfg" );
$suplapm_device_url	= isset($suplapm_cfg['DEVICE_URL']) ? $suplapm_cfg['DEVICE_URL'] : "";

if ($suplapm_device_url == "") {
	die("Device URL is missing!");
}

$temp = json_decode(getvalue($suplapm_device_url),true);
$json = array(
	'Power' => $temp["phases"][0]["powerActive"],
	'Voltage' => $temp["phases"][0]["voltage"],
	'Current' => $temp["phases"][0]["current"],
	'Factor' => $temp["phases"][0]["powerFactor"]*100,
	'Energy' => $temp["phases"][0]["totalForwardActiveEnergy"],
	'ApparentPower' => $temp["phases"][0]["powerApparent"],
	'ReactivePower' => $temp["phases"][0]["powerReactive"],
	'Costs_Price' => $temp["pricePerUnit"],
	'Costs_Unit' => $temp["currency"]
);

//header('Content-Type: application/json');
echo json_encode($json);

function getvalue($url) {
	$curl = curl_init($url . "/read?format=json");
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);  
	$result = curl_exec($curl);
	$result1 = isset($result) ? $result : "0";
	return $result1;
}

?>