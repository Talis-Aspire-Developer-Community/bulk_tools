<?php

print("</br><a href='isbn_updater.html'>Back to ISBN Updater tool</a>");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<p>Starting</p>";

//*****************GRAB_INPUT_DATA**********

$uploaddir = '../uploads/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

echo '<pre>';
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
	echo "File is valid, and was successfully uploaded.\n";
} else {
	echo "File is invalid, and failed to upload - Please try again. -\n";
}
echo "</br>";
print_r($uploadfile);
echo "</br>";
echo "</br>";

/**
 * Get the user config file. This script will fail disgracefully if it has not been created and nothing will happen.
 */
require('../../user.config.php');

echo "Tenancy Shortcode set: " . $shortCode;
echo "</br>";

echo "Client ID set: " . $clientID;
echo "</br>";

echo "User GUID to use: " . $TalisGUID;
echo "</br>";


//**********CREATE LOG FILE TO WRITE OUTPUT*

$myfile = fopen("../../report_files/isbn_updater_output.log", "a") or die("Unable to open isbn_updater_output.log");
fwrite($myfile, "Started | Input File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n");
fwrite($myfile, "Item ID" . "\t" . "Old ISBN" . "\t" . "New ISBN" . "\t" . "Resource ID" . "\t" . "Update Status?" . "\r\n");


function getToken($clientID, $secret) {
	$tokenURL = 'https://users.talis.com/oauth/tokens';
	$content = "grant_type=client_credentials";

	//*********GET DATE**********************

	$date = date('Y-m-d\TH:i:s');
	// $date1 = "2015-12-21T15:44:36";

	//************GET_TOKEN***************

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
		echo "Got Token</br>";
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

/**
 * Update the resource if we need to
 *
 * @param  mixed $resource
 * @param  mixed $old_isbn
 * @param  mixed $new_isbn
 * @return boolean|array False or the new ISBN13s to update
 */
function apply_update_logic($resource, $old_isbn, $new_isbn){
	// if there are some isbn13s to check
	// echo "<br/>Apply Update Logic - " . var_export($resource, true);
	if (!empty($resource->attributes->isbn13s)) {
		// echo "<br/>Apply Update Logic - there are ISBNs";
		// make a copy of the isbns to keep any additional ones (safest)
		$output_isbn13s = $resource->attributes->isbn13s;
		
		// for each of the input ISBNs, see if it should be updated
		foreach ($resource->attributes->isbn13s as $key => $value) {
			if ($value == $old_isbn){
				// echo "<br/>Apply Update Logic - the value is the old ISBN";
				$output_isbn13s[$key] = $new_isbn;
			}
		}
		
		// if the input and output are not the same then changes were made.
		if ($output_isbn13s !== $resource->attributes->isbn13s){
			return $output_isbn13s;
		}
	}
	// otherwise return false as there is nothing to do
	return false;
}

/**
 * Update the resource
 *
 * @param  string $shortCode tenant short code
 * @param  string $resource_id the resource_id to update
 * @param  string $TalisGUID The user making the update
 * @param  string $token Your access token for the API
 * @param  array $new_isbn13s 
 * @param  mixed $myfile Log output to a file.
 * @return void
 */
function updateResource($shortCode, $resource_id, $TalisGUID, $token, $new_isbn13s, $myfile) {
	$url = 'https://rl.talis.com/3/' . $shortCode . '/resources/' . $resource_id;

	$body = json_decode('{
			"data": {
				"type": "resources",
				"id": "",
				"attributes": {
					"isbn13s": []
				}
			}
			}');

	$body->data->id = $resource_id;
	$body->data->attributes->isbn13s = $new_isbn13s;
	
	$ch = curl_init();
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		
		"X-Effective-User: $TalisGUID",
		"Authorization: Bearer $token",
		'Cache-Control: no-cache'
	
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
	$output = curl_exec($ch);
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	// echo $info;
	$output_json = json_decode($output);
	curl_close($ch);
	if ($info !== 200){
		echo "<p>ERROR: There was an error updating the ISBN:</p><pre>" . var_export($output_json, true) . "</pre>";
		fwrite($myfile, "ERROR: There was an error updating the ISBN" ."\t\r\n");
	} else {
		echo " - ISBN Updated Successfully to ". var_export($new_isbn13s, true) ."</br>";
		fwrite($myfile, "ISBN Updated Successfully" ."\t\r\n");
	}

}

/**
 * Get the Item that we need to edit
 *
 * @param  mixed $shortCode The tenant short code
 * @param  mixed $item_id The item Id to fetch
 * @param  mixed $TalisGUID Your talis GUID
 * @param  mixed $token Your access token for the API.
 * @return boolean|object
 */
function getItem($shortCode, $item_id, $TalisGUID, $token) {
	$url = "https://rl.talis.com/3/$shortCode/draft_items/$item_id?include=resource.part_of" ;

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
	// echo $info;
	$output_json = json_decode($output);
	curl_close($ch);
	
	if ($info !== 200){
		echo "<p>ERROR: There was an error getting the resource:</p><pre>" . var_export($output, true) . "</pre>";
		return false;
	} else {
		// echo "Resource details acquired </br>";
		// var_export($output);
		return json_decode($output);
	}
}

/**
 * Look at an item's data and detect the resources in it.
 *
 * @param  mixed $item The JSON API response from the get item request
 * @return array[mixed] an array of key value pairs that describe the primary and secondary resources.
 */
function detect_resources($item){
	$output = [
		'primary' => false,
		'primary_id' => false,
		'secondary' => false,
		'secondary_id' => false
	];

	$resources = array_filter($item->included, function($var){return $var->type == 'resources';});
	var_export(count($resources));
	if (count($resources) == 2){
		// there are two resources
		foreach($resources as $resource){
			// one of the resources has a relationship to another resource
			if (!empty($resource->relationships->part_of)){
				$output['primary_id'] = $resource->id;
				$output['primary'] = $resource;
				$output['secondary_id'] = $resource->relationships->part_of->data->id;
				$output['secondary'] = array_filter($resources, function($var) use ($output){return $var->id == $output['secondary_id'];})[0];
			} 
		}
	} else {
		// there is just the one resource
		$output ['primary_id'] = $resources[0]->id;
		$output ['primary'] = $resources[0];
	}
	// var_export($output);
	return $output;
}

$token = getToken($clientID, $secret);

//***********Running Code******************
$file_handle = fopen($uploadfile, "rb");

while (!feof($file_handle) )  {

	$line_of_text = fgets($file_handle);
	$parts = explode(",", $line_of_text);
	
		$item_id = trim($parts[0]);
		$old_isbn = trim($parts[1]);

		if (!empty(trim($parts[2]))) {
			$new_isbn = trim($parts[2]);
		} else {
			$new_isbn = "null";
			echo "no new ISBN found. Removing $old_isbn from $item_id.</br>";
		}

	echo "processing item_id: $item_id";
	fwrite($myfile, $item_id ."\t");
	//echo "</br>";
	//echo "this is the old_isbn: $old_isbn";
	fwrite($myfile, $old_isbn ."\t");
	//echo "</br>";
	//echo "this is the new_isbn: $new_isbn";
	fwrite($myfile, $new_isbn ."\t");
	//echo "</br>";

	$item = getItem($shortCode, $item_id, $TalisGUID, $token);
	if (!empty($item)){
		//detect if there is a part
		$resources = detect_resources($item);
		$did_an_update = false;

		// TODO - update logic to satisfy these rules.
		// if the resource has a part_of then check both records.
		if (!empty($resources['primary'])) {
			$replacement_isbn13s = apply_update_logic($resources['primary'], $old_isbn, $new_isbn);
			if (!empty($replacement_isbn13s)) {
				updateResource($shortCode, $resources['primary_id'], $TalisGUID, $token, $replacement_isbn13s, $myfile);
				echo 'Resource: '. $resources['primary_id'];
				fwrite($myfile, $resources['primary_id'] ."\t");
				$did_an_update = true;
			}
		}
		if (!empty($resources['secondary'])) {
			$replacement_isbn13s = apply_update_logic($resources['secondary'], $old_isbn, $new_isbn);
			if (!empty($replacement_isbn13s)) {
				updateResource($shortCode, $resources['secondary_id'], $TalisGUID, $token, $replacement_isbn13s, $myfile);
				echo 'Resource: ' . $resources['secondary_id'];
				fwrite($myfile, $resources['secondary_id'] ."\t");
				$did_an_update = true;
			}

		}

		if(empty($did_an_update)) {
			echo "<br/>Nothing to do";
		}

	}
	echo "<br />----------- <br />";
}


fwrite($myfile, "\r\n" . "Stopped | End of File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n");

fclose($file_handle);
fclose($myfile);

?>