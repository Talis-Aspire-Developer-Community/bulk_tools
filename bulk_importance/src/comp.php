<?php
require('functions.php');

print("</br><a href='imp.html'>Back to tool input page</a>");

ini_set('max_execution_time', '0');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo_message_to_screen("INFO", "Starting the bulk importance update process.");

//*****************GRAB_INPUT_DATA**********

$uploaddir = '../uploads/';
$uploadfile = $uploaddir . basename($_FILES['userfile']['name']);

echo '<pre>';
if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadfile)) {
	echo_message_to_screen("INFO", "File is valid, and was successfully uploaded.");
} else {
	echo_message_to_screen("INFO", "File failed to upload - Please try again.");
}

echo "</br>";
print_r($uploadfile);
echo "</br>";
echo "</br>";

/**
 * Get the user config file. This script will fail disgracefully if it has not been created and nothing will happen.
 */
require('../../user.config.php');

echo_message_to_screen("INFO", "Tenancy Shortcode set: " . $shortCode);
echo "</br>";

echo_message_to_screen("INFO", "Client ID set: " . $clientID);
echo "</br>";

echo_message_to_screen("INFO", "User GUID to use: " . $TalisGUID);
echo "</br>";

echo_message_to_screen("INFO", "Importance ID set: " . $importanceID);
echo "</br>";


//**********CREATE LOG FILE TO WRITE OUTPUT*

$myfile = fopen("../../report_files/bulkimp_output.log", "a") or die("Unable to open bulkimp_output.log");
fwrite($myfile, "Started | Input File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n");
fwrite($myfile, "Item ID" . "\t" . "List ID" . "\t" . "Outcome" . "\r\n");

//**********CREATE TOKEN**********
$token=token_fetch($clientID, $secret); 
	
$file_handle = fopen($uploadfile, "r");
if ($file_handle == FALSE) {
	echo_message_to_screen("ERROR", "Could not open file - Process Stopped.");
	exit;
}

$pub_list = array();

while (($line = fgetcsv($file_handle, 1000, ",")) !== FALSE) {
	
	$item_id = trim($line[0]);
	$item=item($shortCode, $TalisGUID, $token, $item_id);

	$resource_title = $item[1];
	$list_id = $item[2];

	$etag = etag_fetch($shortCode, $list_id, $TalisGUID, $token);
	$input_imp = impBody($item_id, $etag, $list_id, $importanceID);
	impPost($shortCode, $TalisGUID, $token, $input_imp, $item_id, $resource_title);
	fwrite($myfile, $item_id . "\t" . $list_id . "\t" . "Importance Updated" . "\r\n");
	echo_message_to_screen("INFO", "Item ID: $item_id | List ID: $list_id | Resource Title: $resource_title | Outcome: Importance Updated");
	$pub_list[$list_id] = array('id' => $list_id, 'type' => 'draft_lists');
}

fclose($file_handle);

// Here we deduplicate and publish the lists.
//var_export($pub_list);
$dedupe_pub_list = array_values($pub_list);
$arrayLength = count($dedupe_pub_list);
echo_message_to_screen("INFO", "Total unique lists to publish: $arrayLength");
bulk_publish_lists($shortCode, $TalisGUID, $token, $dedupe_pub_list);

fwrite($myfile, "\r\n" . "Stopped | End of File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n");
fclose($myfile);
?>