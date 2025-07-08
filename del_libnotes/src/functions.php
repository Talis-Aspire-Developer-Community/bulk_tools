<?php

// Talis API tool created by Michael Whitton (mw2@soton.ac.uk) based on the 'Importance Updater' 
function item($shortCode, $TalisGUID, $token, $item_id) {
	
	$url = 'https://rl.talis.com/3/' . $shortCode . '/items/' . $item_id . '?include=list,resource&draft=1';
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		
		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	));

	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	// echo $info;

	$output_json = json_decode($output);

	curl_close($ch);
	if ($info !== 200){
		echo "<p>ERROR: There was an error getting item information</p><pre>" . var_export($output, true) . "</pre>";
        $item = array(null);
	}
    else {
	//var_export($output_json);
	$resource_id = $output_json->included[0]->id;
	$resource_title = $output_json->included[0]->attributes->title;
	$list_id = $output_json->included[1]->id;
	$list_title = $output_json->included[1]->attributes->title;
    $pub_status = $output_json->included[1]->attributes->published_status;
	
	$item = array($resource_id, $resource_title, $list_id, $list_title, $pub_status);}
	return $item;
}

function notePost($shortCode, $TalisGUID, $token, $item_body, $item_id, $resource_title) {
	
    //echo $item_body;
	$item_patch = 'https://rl.talis.com/3/' . $shortCode . '/draft_items/' . $item_id ;
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $item_patch);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		
		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	));

	curl_setopt($ch, CURLOPT_POSTFIELDS, $item_body);

	
	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	// echo $info;

	$output_json_etag = json_decode($output);

	curl_close($ch);
    if ($info == 200){echo $item_id . " Item Updated</br>"; return "Success";} else {echo "Error with Item: " . $item_id . "</br>"; return "Error: " . strval($info);}
}

function noteDelBody($item_id, $etag, $list_id) {
					
	$item_body= ' {
	"meta": {
		"list_id": "' . $list_id .'",
		"list_etag": "' . $etag . '"
	},
    "data": {
		"id": "' . $item_id . '",
		"type": "items",
        "attributes": {"library_note": null}
	}
	}';

	return $item_body;
}

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

	if ($info !== 200){
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

function publish_single_list($shortCode, $listID, $TalisGUID, $token, $etag) {
    //echo "Etag is:" . $etag;
	$body = '{
    "data": {
        "type": "list_publish_actions"
    },
    "meta": {
        "list_etag": "' . $etag . '"
			}
	}';
	//var_export ($etag);
	//var_export ($body);
	
	$url = 'https://rl.talis.com/3/' . $shortCode . '/draft_lists/' . $listID . '/publish_actions';
	//echo $url;
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	
		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	
	));
	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$output_json = json_decode($output);
	curl_close($ch);
	
    if ($info == 202){echo $listID . " List  Published</br>"; return "List Published";} else {echo "Error with List: " . $listID . "</br>"; return "Error: " . strval($info);}
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
	
	$etag = $output_json->data->meta->list_etag;
    $has_unpubchanges = $output_json->data->meta->has_unpublished_changes;
	
	/* uncomment for debugging
	echo "    </br>";
	echo "    Updated ETag: " . $etag . "</br>";
	echo "</br>";
	*/

	return array($etag, $has_unpubchanges);
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
?>
