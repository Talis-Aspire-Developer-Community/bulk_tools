<?php

// This file is part of the Talis Bulk Tools project.
// It contains some useful functions used by the bulk_importance tool.

function echo_message_to_screen($log_level, $message){
	echo "</br><strong>{$log_level}</strong>: $message";
}

function item($shortCode, $TalisGUID, $token, $item_id) {
	
	$url = 'https://rl.talis.com/3/' . $shortCode . '/draft_items/' . $item_id . '?include=list,resource';
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
		exit;
	}
	//var_export($output_json);
	$resource_id = $output_json->included[0]->id;
	$resource_title = $output_json->included[0]->attributes->title;
	$list_id = $output_json->included[1]->id;
	$list_title = $output_json->included[1]->attributes->title;
	
	$item = array($resource_id, $resource_title, $list_id, $list_title);
	return $item;
}

function impPost($shortCode, $TalisGUID, $token, $input_imp, $item_id, $resource_title) {
	
	//var_export($input_imp);
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

	curl_setopt($ch, CURLOPT_POSTFIELDS, $input_imp);

	
	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	// echo $info;

	$output_json_etag = json_decode($output);

	curl_close($ch);
	if ($info !== 200){
		echo "<p>ERROR: There was an error adding the importance to: $resource_title</p><pre>" . var_export($output, true) . "</pre>";
	}
}

function impBody($item_id, $etag, $list_id, $importanceID) {
					
	$input_imp= ' {
	"data": {
		"id": "' . $item_id . '",
		"type": "items",
		"relationships": {
		"importance": {
			"data": {
			"id": "' . $importanceID . '",
			"type": "importances"
			}
		}
		}
	},
	"meta": {
		"list_id": "' . $list_id .'",
		"list_etag": "' . $etag . '"
	}
	}';

	return $input_imp;
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

function bulk_publish_lists($shortCode, $TalisGUID, $token, $dedupe_pub_list) {
	// Prepare the array for the bulk publish request
	$publishListArray_encoded = json_encode($dedupe_pub_list);

	//**************PUBLISH**LIST***************
	$patch_url2 = 'https://rl.talis.com/3/' . $shortCode . '/bulk_list_publish_actions'; 
	$input2 = '{
				"data": {
					"id": "' . sprintf(
						'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
						mt_rand(0, 0xffff), mt_rand(0, 0xffff),
						mt_rand(0, 0xffff),
						mt_rand(0, 0x0fff) | 0x4000,
						mt_rand(0, 0x3fff) | 0x8000,
						mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
					) . '",
					"type": "bulk_list_publish_actions",
					"relationships": {
						"draft_lists": {
							"data": ' . $publishListArray_encoded . '
						}
					}
				}	
			}';

	//**************PUBLISH POST*****************

	$ch3 = curl_init();

	curl_setopt($ch3, CURLOPT_URL, $patch_url2);
	curl_setopt($ch3, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch3, CURLOPT_HTTPHEADER, array(

		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	));

	curl_setopt($ch3, CURLOPT_POSTFIELDS, $input2);

	$output3 = curl_exec($ch3);
	$info3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
	curl_close($ch3);
	if ($info3 !== 202){
		echo "<p>ERROR: There was an error publishing the lists</p><pre>" . var_export($output3, true) . "</pre>";
		exit;
	} else {
		echo "Added lists to bulk list publish queue</br>";
	}

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
	
	/* uncomment for debugging
	echo "    </br>";
	echo "    Updated ETag: " . $etag . "</br>";
	echo "</br>";
	*/

	return $etag;
}
      


?>
