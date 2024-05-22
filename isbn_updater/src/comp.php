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
	echo "File was successfully uploaded.\n";
} else {
	echo "File failed to upload.\n";
}
echo "</br>";
print_r($uploadfile);
echo "</br>";
echo "</br>";

/**
 * Get the user config file. This script will fail disgracefully if it has not been created and nothing will happen.
 */
require('../../user.config.php');
require_once 'IsbnParser.php';
require_once 'IsbnUpdater.php';
require_once 'resource.php';

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


function getToken($clientID, $secret)
{
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

	if ($info !== 200) {
		echo "<p>ERROR: There was an error getting a token:</p><pre>" . var_export($return, true) . "</pre>";
	} else {
		echo "Got Token</br>";
	}

	curl_close($ch);

	$jsontoken = json_decode($return);

	if (!empty($jsontoken->access_token)) {
		$token = $jsontoken->access_token;
	} else {
		echo "<p>ERROR: Unable to get an access token</p>";
		exit;
	}

	return $token;
}

/**
 * Update the resource
 *
 * @param  string $shortCode tenant short code
 * @param  string $resource_id the resource_id to update
 * @param  string $TalisGUID The user making the update
 * @param  string $token Your access token for the API
 * @param  array $new_isbn13s 
 * @param  array $new_isbn10s
 * @param  mixed $myfile Log output to a file.
 * @return void
 */
function updateResource($shortCode, $resource_id, $TalisGUID, $token, array $new_isbn13s, array $new_isbn10s, $myfile)
{
	$url = 'https://rl.talis.com/3/' . $shortCode . '/resources/' . $resource_id;

	$body = [
		"data" => [
			"type" => "resources",
			"id" => "",
			"attributes" => []
		]
	];

	$body['data']['id'] = $resource_id;
	$body['data']['attributes']['isbn13s'] = $new_isbn13s;
	$body['data']['attributes']['isbn10s']  = $new_isbn10s;

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
	if ($info !== 200) {
		echo "<p>ERROR: There was an error updating the ISBN:</p><pre>" . var_export($output_json, true) . "</pre>";
		fwrite($myfile, "ERROR: There was an error updating the ISBN" . "\t\r\n");
	} else {
		echo "<br/> - ISBN Updated Successfully to " . var_export($new_isbn13s, true) . "</br>";
		fwrite($myfile, "ISBN Updated Successfully" . "\t\r\n");
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
function getItem($shortCode, $item_id, $TalisGUID, $token)
{
	$url = "https://rl.talis.com/3/$shortCode/draft_items/$item_id?include=resource.part_of";

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

	if ($info !== 200) {
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
function detect_resources($item)
{
	$output = [
		'primary' => false,
		'primary_id' => false,
		'secondary' => false,
		'secondary_id' => false
	];

	$resources = array_filter($item->included, function ($var) {
		return $var->type == 'resources';
	});
	var_export(count($resources));
	if (count($resources) == 2) {
		// there are two resources
		foreach ($resources as $resource) {
			// one of the resources has a relationship to another resource
			if (!empty($resource->relationships->part_of)) {
				$output['primary_id'] = $resource->id;
				$output['primary'] = $resource;
				$output['secondary_id'] = $resource->relationships->part_of->data->id;
				$output['secondary'] = array_filter($resources, function ($var) use ($output) {
					return $var->id == $output['secondary_id'];
				})[0];
			}
		}
	} else {
		// there is just the one resource
		$output['primary_id'] = $resources[0]->id;
		$output['primary'] = $resources[0];
	}
	// var_export($output);
	return $output;
}

$token = getToken($clientID, $secret);

//***********Running Code******************
$file_handle = fopen($uploadfile, "rb");

while (!feof($file_handle)) {
	echo "<br />----------- <br />";

	$line_of_text = fgets($file_handle);
	$parts = explode(",", $line_of_text);

	if (count($parts) !== 3) {
		echo "<br/>Skipping - This row does not have three columns";
		fwrite($myfile, "Skipped - This row does not have three columns: {$line_of_text}\r\n");
		continue;
	}

	$item_id = trim($parts[0]);
	$old_isbn = trim($parts[1]);
	$new_isbn = trim($parts[2]);

	echo "processing item_id: $item_id";
	fwrite($myfile, $item_id . "\t");
	fwrite($myfile, $old_isbn . "\t");
	fwrite($myfile, $new_isbn . "\t");

	if (empty($item_id)) {
		echo "<br/>Skipping - No item ID provided: " . $line_of_text;
		fwrite($myfile, "Skipped - Empty columns\r\n");
		continue;
	}

	if (empty($old_isbn) && empty($new_isbn)) {
		echo "<br/>Skipping - At lest one ISBN column needs a value: " . $line_of_text;
		fwrite($myfile, "Skipped - At lest one ISBN column needs a value\r\n");
		continue;
	}

	// We are not going to validate the old ISBN, so we can handle any bad data.
	// But we will validate the new ISBN so we are only adding in valid ISBNs.
	// An empty isbn is allowed (ie. no add, only remove)
	$isbnParser = new IsbnParser();
	if (!empty($new_isbn) && !$isbnParser->isValid($new_isbn)) {
		echo "<br/>Skipping - New ISBN is not a valid ISBN";
		fwrite($myfile, "Skipped - New ISBN is invalid\r\n");
		continue;
	}

	$item = getItem($shortCode, $item_id, $TalisGUID, $token);
	if (!empty($item)) {
		//detect if there is a part
		$resources = detect_resources($item);
		$did_an_update = false;

		// if the resource has a part_of then check both records.
		$selected_resources = [$resources['primary'], $resources['secondary']];
		foreach ($selected_resources as $r) {
			if (empty($r)) {
				continue;
			}
			$resource = new Resource($r);
			$updated = processResource(
				$resource,
				$old_isbn,
				$new_isbn,
				$shortCode,
				$TalisGUID,
				$token,
				$myfile
			);
			if ($updated) {
				$did_an_update = true;
			}
		}

		if (empty($did_an_update)) {
			echo "<br/>Nothing to do";
			fwrite($myfile, "No updates need to be made\r\n");
		}
	}
}

function processResource(Resource $resource, $old_isbn, $new_isbn, $shortCode, $TalisGUID, $token, $myfile): bool
{
	// Work out the new isbn array values
	$isbnUpdater = new IsbnUpdater();
	$isbnUpdater->setExistingIsbn10s($resource->getIsbn10s());
	$isbnUpdater->setExistingIsbn13s($resource->getIsbn13s());
	$isbnUpdater->removeIsbn($old_isbn);
	$isbnUpdater->addIsbn($new_isbn);

	$replacement_isbn13s = $isbnUpdater->getIsbn13s();
	$replacement_isbn10s = $isbnUpdater->getIsbn10s();

	// Check if we need to update the resource
	if ($replacement_isbn10s == $resource->getIsbn10s() && $replacement_isbn13s == $resource->getIsbn13s()) {
		echo "<br/>Skipping - No updates need to be made";
		fwrite($myfile, "No updates need to be made\r\n");
		return false;
	}

	// Finally do the actual update
	updateResource(
		$shortCode,
		$resource->getId(),
		$TalisGUID,
		$token,
		$replacement_isbn13s,
		$replacement_isbn10s,
		$myfile
	);
	echo 'Resource: ' . $resource->getId();
	fwrite($myfile, $resource->getId() . "\t");
	return true;
}


fwrite($myfile, "\r\n" . "Stopped | End of File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n");

fclose($file_handle);
fclose($myfile);
