<?php

print("</br><a href='issn_updater.html'>Back to ISSN Updater tool</a>");

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

// Get settings from form

$idType = $_REQUEST['ID_TYPE'];
echo "Identifier Type: " . $idType;
echo "</br>";

$articleMode = $_REQUEST['UPDATE_MODE'];
echo "Chapters/Articles Mode: " . $articleMode;
echo "</br>";

/**
 * Get the user config file. This script will fail disgracefully if it has not been created and nothing will happen.
 */
require('../../user.config.php');
require_once 'IssnUpdater.php';
require_once 'resource.php';

echo "Tenancy Shortcode set: " . $shortCode;
echo "</br>";

echo "Client ID set: " . $clientID;
echo "</br>";

echo "User GUID to use: " . $TalisGUID;
echo "</br>";


//**********CREATE LOG FILE TO WRITE OUTPUT*

$myfile = fopen("../../report_files/issn_updater_output.log", "a") or die("Unable to open lcn_updater_output.log");
fwrite($myfile, "Started | Input File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n");
fwrite($myfile, "Identifier type: $idType & Chapters/Articles Mode: $articleMode \r\n\r\n");
fwrite($myfile, "Item ID" . "\t" . "Old ISSN" . "\t" . "New ISSN" . "\t" . "Resource ID" . "\t" . "Update Status?" . "\r\n");


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
 * Update the resource
 *
 * @param  string $shortCode tenant short code
 * @param  string $resource_id the resource_id to update
 * @param  string $TalisGUID The user making the update
 * @param  string $token Your access token for the API
 * @param  array $new_eissns 
 * @param  array $new_issns
 * @param  mixed $myfile Log output to a file.
 * @return void
 */
function updateResource($shortCode, $resource_id, $TalisGUID, $token, array $new_eissns, array $new_issns, $myfile)
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
	$body['data']['attributes']['eissns'] = $new_eissns;
	$body['data']['attributes']['issns']  = $new_issns;

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
		echo "<p>ERROR: There was an error updating the ISSN:</p><pre>" . var_export($output_json, true) . "</pre>";
		fwrite($myfile, "ERROR: There was an error updating the ISSN" . "\t\r\n");
	} else {
		echo "<br/> - EISSN values are now: " . var_export($new_eissns, true) . "</br>";
		echo "<br/> - ISSN values are now: " . var_export($new_issns, true) . "</br>";
		fwrite($myfile, "ISSN Updated Successfully" . "\t\r\n");
	}
}

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
	//echo $output;
	$output_json = json_decode($output);
	curl_close($ch);
    
	if ($info !== 200) {
		echo "<p>ERROR: There was an error getting the resource:</p><pre>" . var_export($output, true) . "</pre>";
		return false;
	} else {
		// echo "Resource details acquired </br>";
		// var_export($output);
		return $output_json ;
	}
}

/**
 * Look at an item's data and detect the resources in it.
 *
 * @param  mixed $item The JSON API response from the get item request
 * @return array[mixed] an array of key value pairs that describe the primary and secondary resources.
 */
function detect_resources($item, $articleMode)
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
                // For article/chapter mode take the main part only
                if ($articleMode == "article/chapter") {
                    $output['primary_id'] = $resource->id;
                    $output['primary'] = $resource;
                }
                // For both mode take both elements
                elseif ($articleMode == "both") {
                    $output['primary_id'] = $resource->id;
                    $output['primary'] = $resource;
                    $output['secondary_id'] = $resource->relationships->part_of->data->id;
                    $output['secondary'] = array_filter($resources, function ($var) use ($output) {
                        return $var->id == $output['secondary_id'];
                    })[0];
                }
                # For Journal/book mode take the secondary only
                elseif ($articleMode == "journal/book") {
                    $output['primary_id'] = $resource->relationships->part_of->data->id;
                    $output['primary'] = array_filter($resources, function ($var) use ($output) {
                        return $var->id == $output['primary_id'];
                    })[0];
			    }
		    }
	    }
    }
   else {
		// there is just the one resource
		$output['primary_id'] = $resources[0]->id;
		$output['primary'] = $resources[0];
	    }
	//var_export($output);
	return $output;
}

// Function to process the resource for update
function processResource(Resource $resource, $old_issn, $new_issn, $idType, $shortCode, $TalisGUID, $token, $myfile): bool
{
	// Work out the new issn array values
	$issnUpdater = new IssnUpdater();
    // Load existing ISSNs
    $issnUpdater->setExistingEIssns($resource->getEIssns());
    $issnUpdater->setExistingIssns($resource->getIssns());
    // Remove old ISSN
    if ($idType=="issns") {$issnUpdater->removeIssn($old_issn);} else {$issnUpdater->removeEIssn($old_issn);}
    // Add new ISSN if not blank
    if (!empty($new_issn)) {
        if ($idType=="issns") {$issnUpdater->addIssn($new_issn);} else {$issnUpdater->addEIssn($new_issn);};
        }
    // Get new ISSNs
	$replacement_eissns = $issnUpdater->getEIssns();
    //echo "Replacement EISSNs";
    //var_export($replacement_eissns);
	$replacement_issns = $issnUpdater->getIssns();
    //echo "Replacement ISSNs";
    //var_export($replacement_issns);
    //echo "Current ISSNs";
    //var_export($resource->getIssns());
    //echo "Current EISSNs";
    //var_export($resource->getEIssns());
	// Check if we need to update the resource
	if ($replacement_eissns == $resource->getEIssns() && $replacement_issns == $resource->getIssns()) {
		echo "<br/>Skipping - No updates need to be made";
		fwrite($myfile, "No updates need to be made\r\n");
		return false;
	}
	echo 'Resource: ' . $resource->getId();
	fwrite($myfile, $resource->getId() . "\t");
	// Finally do the actual update
	updateResource(
		$shortCode,
		$resource->getId(),
		$TalisGUID,
		$token,
		$replacement_eissns,
		$replacement_issns,
		$myfile
	);
	return true;
}

$token = getToken($clientID, $secret);

//***********Running Code******************
$file_handle = fopen($uploadfile, "rb");

while (!feof($file_handle) )  {
    echo "<br />----------- <br />";
	$line_of_text = fgets($file_handle);
	$parts = explode(",", $line_of_text);
        // Skip to next line if no item found
        if ($parts[0] == null) {
            fwrite($myfile, $parts[0]  . "\t Item not found \t\r\n");
            continue;}
        # Make sure at least three columns are provided
        if (count($parts) !== 3) {
            echo "<br/>Skipping - This row does not have three columns";
            fwrite($myfile, "Skipped - This row does not have three columns: {$line_of_text}\r\n");
            continue;
        }
        // The Talis item id is the first csv field
		$item_id = trim($parts[0]);
        echo "processing item_id: $item_id";
        fwrite($myfile, $item_id ."\t");
        # Old and New ISSNs are the second and third columns
        $old_issn = trim($parts[1]);
        fwrite($myfile, $old_issn . "\t");
        //echo "</br>this is the Old ISSN: $old_issn";
        $new_issn = trim($parts[2]);
        fwrite($myfile, $new_issn . "\t");
        //echo "</br>this is the New ISSN: $new_issn";
        // Warn if both issn columns are blank
        if (empty($old_issn) && empty($new_issn)) {
            echo "<br/>Skipping - At lest one ISSN column needs a value: " . $line_of_text;
            fwrite($myfile, "Skipped - At lest one ISSN column needs a value\r\n");
            continue;
        }
        // Get item metadata from Talis API
        $item = getItem($shortCode, $item_id, $TalisGUID, $token);
        if (!empty($item)) {
            //detect if there is a part
            $resources = detect_resources($item, $articleMode);
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
                    $old_issn,
                    $new_issn,
                    $idType,
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
                echo "</br>";
                fwrite($myfile, "No updates need to be made\r\n");
            }
        }
}


fwrite($myfile, "\r\n" . "Stopped | End of File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n");

fclose($file_handle);
fclose($myfile);

?>