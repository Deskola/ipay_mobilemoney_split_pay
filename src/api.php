<?php

namespace Ipay\Api;

class Api {
	private $testUrl = 'http://localhost/test_api/processor.php';	
	private $liveUrl = 'https://apis.staging.ipayafrica.com/b2c/v3';	
	private $client_id = null;
	private $client_secret = null;

	public function __construct($client_id, $client_secret)
	{
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}
	
	public function make_api_request($endpoint, $amount, $reference, $phone, $is_test)
	{
		$url = $this->testUrl;
		if ($is_test == false) {			
			$url = "{$this->liveUrl}/{$endpoint}";
		}
		

		$dataString = "amount={$amount}&phone={$phone}&reference={$reference}&vid={$this->client_id}";

		$generatedHash = hash_hmac('sha256', $dataString, $this->client_secret);

		$data = array(
			"amount" => $amount,
		    "phone" => $phone,
		    "reference" => $reference,
		    "vid" => $this->client_id,
		    "hash" => $generatedHash
		);

		$jsonifiedData = json_encode($data);

		return $this->make_curl_post($url, $jsonifiedData);

		
	}

	private function make_curl_post($url, $json_enconded_data) {		

		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>$json_enconded_data,
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);

		curl_close($curl);
		return $response;
	}

	public function test_function($name){
		return $name;
	}


}