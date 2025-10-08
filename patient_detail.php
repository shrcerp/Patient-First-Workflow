<?php

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://copperjam.in/vlf/ipd_api/patient_details.php',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'GET',
  CURLOPT_POSTFIELDS => 'id=74&form_status=2&remark=Approved%20by%20TPA',
  CURLOPT_HTTPHEADER => array(
    'form_status: 1',
    'Content-Type: application/x-www-form-urlencoded'
  ),
));

$response = curl_exec($curl);
echo $response;
curl_close($curl);
 