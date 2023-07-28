<?php
	require_once 'src/WordpressClient.php';
	require_once('src/Exception/NetworkException.php');
	require_once('src/Exception/XmlrpcException.php');
	
	// Function to make a request to the ChatGPT API - you can test message and other details here: https://platform.openai.com/playground
	function callChatGPTAPI($message) {
		$url = 'https://api.openai.com/v1/chat/completions';
		$apiKey = '12133123131312312312321'; // Replace with your actual API key -  you need a key - payment will be for what you use at the end of the month
		$headers = [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $apiKey
		];
		$data = [
		'model' => 'gpt-3.5-turbo-16k', // Replace with the desired model, e.g., 'gpt-3.5-turbo' - list of all models: https://platform.openai.com/docs/models/gpt-3-5
		'messages' => [
		['role' => 'system', 'content' => $message]
		],
		"temperature"=> 1,
		"max_tokens"=> 3400,
		"top_p"=> 1,
		"frequency_penalty"=> 0,
		"presence_penalty"=> 0.6
		];
		
		
		
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		
		$response = curl_exec($ch);
		if ($response === false) {
			} else {
			$responseData = json_decode($response, true);
			
			$message = $responseData['choices'][0]['message']['content'];
			
			curl_close($ch);
			return $message;
		}
	}
	
	$message=''; //insert the subject of the article  you want to be genrated and tell it to be returned as JSON with a title with array key "grc-title" and article content with array key "grc-content"
	
	//example: $message='Return a JSON containing a title with array key "grc-title" and article content with array key "grc-content". The article must simulate as if it was written by a famous chef and be about a dinner recipe. The recipe can be from any country in the world. The article must be between a minimum of 200 words and a maximum of 400 words with the following sections: Ingredients, Instructions, recommendation Presentation, recommendation of wine to drink and the necessary utensils. The article must be in Romanian. The content of the article should be HTML formatted.';
	
	$continut = callChatGPTAPI($message);
	
	// Convert JSON string to an associative array
	$data = json_decode($continut, true);
	
	// Extract the title
	$title = $data['grc-titlu'];
	// Extract the content
	$body = $data['grc-continut'];
	
	//echo $title;
	//echo $body;
	
	$endpoint = 'https://'.$_SERVER['SERVER_NAME'].'/xmlrpc.php';
	$wpUser = ''; //enter the wp user login
	$wpPass = ''; //enter the wp user assword
	
	
	$wpClient = new \HieuLe\WordpressXmlrpcClient\WordpressClient();
	$wpClient->setCredentials($endpoint, $wpUser, $wpPass);
	
	$content = array(
    'post_type' => 'post',
    'post_status' => 'publish', 
    'post_title' => $title,
    'post_content' => $body
	);
	
	try {
		$result = $wpClient->newPost($title,$body,$content);
		$postname = $wpClient->getPost($result);
		$new_post_url_slug = $postname['post_name'];
		
		echo 'Article posted successfully with ID: ' . $result;
		echo '<br>New post URL slug: ' . $new_post_url_slug;
		} catch (\HieuLe\WordpressXmlrpcClient\Exception\XmlrpcException $e) {
		die('XML-RPC Error: ' . $e->getMessage());
		} catch (\HieuLe\WordpressXmlrpcClient\Exception\NetworkException $e) {
		die('Network Error: ' . $e->getMessage());
	}
	
	//after you have inserted OpenAI API key, wp user and wp password run it with a cronjob
	//cronjob comand example to run once a day: 0	0	1	*	*	wget -q -O /dev/null "https://yourdomain.com/cronchatgpt/index.php"
	
?>