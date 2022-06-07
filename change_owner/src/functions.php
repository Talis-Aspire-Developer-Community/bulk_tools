<?php

function token_fetch($clientID, $secret) {
	$tokenURL = 'https://users.talis.com/oauth/tokens';
	$content = "grant_type=client_credentials";

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $tokenURL);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_USERPWD, "$clientID:$secret");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $content);

	$return = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	if ($info !== 200) {
		echo "<p>ERROR: There was an error getting a token:</p><pre>" . var_export($return, true) . "</pre>";
	} else {
		echo "Successfully received token</br>";
	}

	curl_close($ch);

	$jsontoken = json_decode($return);

	if (!empty($jsontoken->access_token)){
		$token = $jsontoken->access_token;
	} else {
		echo "<p>ERROR: Unable to get an access token</p>";
		exit;
	}
	return $token;
}

function etag_fetch($shortCode, $listID, $TalisGUID, $token) {
	$url = 'https://rl.talis.com/3/' . $shortCode . '/draft_lists/' . $listID;
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	
		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	
	));
	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$output_json = json_decode($output);
	curl_close($ch);
	
	if ($info != 200) {
		echo "<p>unable to get list information (is it archived?). Moving on to next row</p>";
	} else {
		$etag = $output_json->data->meta->list_etag;
		return $etag;
	}	
}    

function guidv4($data = null) {

    // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

    // Output the 36 character UUID.
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

function ownerPatch($shortCode, $TalisGUID, $token, $input, $listID, $ownerID, $myfile) {
	
	$item_patch = 'https://rl.talis.com/3/' . $shortCode . '/lists/' . $listID . '/relationships/owners';
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $item_patch);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		
		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $input);

	
	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	$output_json = json_decode($output);
	
	curl_close($ch);
	if ($info !== 200){
		echo "<p>ERROR: There was an error assigning the list owner:</p><pre>" . var_export($output, true) . "</pre>";
		return false;
	} else {
		return true;
	}
	
}

function patchBody($etag, $listID, $ownerID) {
				
	$input= 
	'{"data":
			[{"id": "' . $ownerID . '", "type": "users"}],
		"meta":
			{"list_etag": "' . $etag . '"}
	}';

			return $input;
}

function setDryRun() {
    if(isset($_REQUEST['DRY_RUN']) &&
    $_REQUEST['DRY_RUN'] == "write_to_live") {
        $write_to_live = "true";
    }
    else
    {
        $write_to_live = "false";
    }

    echo "Writing to live tenancy?: $write_to_live";
    echo "<br>";

    return $write_to_live;
}

function getFriendlyLogLevelName($log_level) {
    // Map log levels to friendly names for humans
    $log_level_map = [
        4 => "DEBUG",
        3 => "INFO",
        2 => "WARN",
        1 => "ERROR"
    ];
    return $log_level_map[$log_level];
}

function echoMessageToScreen($log_level, $message){
    global $LOG_LEVEL;
    // Echo the log message if the log level says we should.
    if ($LOG_LEVEL >= $log_level) {
        $friendly_name = getFriendlyLogLevelName($log_level);
        echo "<br><strong>{$friendly_name}</strong>  $message";
    }
}

?>
