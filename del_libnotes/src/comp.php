<?php

// Talis API tool created by Michael Whitton (mw2@soton.ac.uk) based on the 'Importance Updater' 

print("</br><a href='del-libnotes.html'>Back to Library Notes Deletion tool</a>");

ini_set('max_execution_time', '0');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<p>Starting</p>";

//classes
//functions

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
require('functions.php');

echo "Tenancy Shortcode set: " . $shortCode;
echo "</br>";

echo "Client ID set: " . $clientID;
echo "</br>";

echo "User GUID to use: " . $TalisGUID;
echo "</br>";


//**********CREATE LOG FILE TO WRITE OUTPUT*

$myfile = fopen("../../report_files/del_libnotes_output.log", "a") or die("Unable to open del_libnotes_output.log");
fwrite($myfile, "Started | Input File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n");
fwrite($myfile, "Item ID" . "\t" . "List ID" . "\t" . "Unpublished Changes" . "\t" . "Outcome" . "\r\n");

$tokenURL = 'https://users.talis.com/oauth/tokens';
$content = "grant_type=client_credentials";

$token=token_fetch($clientID, $secret); 


	
	$file_handle = fopen($uploadfile, "r");
    if ($file_handle == FALSE) {
		echo_message_to_screen(ERROR, "Could not open tsv file - Process Stopped.");
		exit;
    }

	$pub_list = array();
    // Loop over the CSV file of item ids
	while (($line = fgetcsv($file_handle, 1000, "\t")) !== FALSE) {
		// Get the item id and query the Talis API for associated metadata
		$item_id = trim($line[0]);
		$item=item($shortCode, $TalisGUID, $token, $item_id);
        // Skip to next line if no item found
        if ($item[0] == null) {
            fwrite($myfile, $item_id  . "\t Item not found \t\r\n");
            continue;}
        // Extract the ids retrieved
		$resource_id = $item[0];
		$resource_title = $item[1];
		$list_id = $item[2];
		$list_title = $item[3];
        $listinfo = etag_fetch($shortCode, $list_id, $TalisGUID, $token);
		$etag = $listinfo[0];
        $has_unpubchanges = $listinfo[1];
        // Write ids to the log
        fwrite($myfile, $item_id  . "\t" . $list_id . "\t");
        // Print  has_unpubchanges as false / true
        if ($has_unpubchanges == 1) {fwrite($myfile, "true" . "\t");}
        elseif ($has_unpubchanges == 0) {fwrite($myfile, "false" . "\t");}
        else {fwrite($myfile, $has_unpubchanges . "\t");}
        //echo "Etag is: " . $etag . "Unpub changes is: " . $has_unpubchanges;
        // Produce the json needed
		$item_body = noteDelBody($item_id, $etag, $list_id);
        // Update the item to remove library notes
		$status = notePost($shortCode, $TalisGUID, $token, $item_body, $item_id, $resource_title);
        // Print the status of the item update to the log
        fwrite($myfile, $status . "\t\r\n");
        // If there were originally no unpublished changes, flag the list for publishing
        if ($has_unpubchanges == 0) {array_push($pub_list, $list_id);}
		
	}

	fclose($file_handle);

		// Here we deduplicate and publish the lists.
		//var_export($pub_list);
		$dedup_pub_list = array_unique($pub_list);
		//var_export($dedup_pub_list);
		$merge_pub_list = array_merge($dedup_pub_list);
		//var_export($merge_pub_list);
        $arrayLength = count($merge_pub_list);
       // echo $arrayLength;
        $i = 0;

        // Loop over the lists that need publishing
        while ($i < $arrayLength)
        {
			$list_id = $merge_pub_list[$i];
			//echo $list_id . " " . $i;
            // Get the list etag
            $etag = etag_fetch($shortCode, $list_id, $TalisGUID, $token)[0];
            // Publish the list
			$lstatus = publish_single_list($shortCode, $list_id, $TalisGUID, $token, $etag);
            // Write the status of the list update to the log`
            fwrite($myfile, $list_id . "\t" . $lstatus);
            $i++;
		
        }

// Print the end of the log of this session
fwrite($myfile, "\r\n" . "Stopped | End of File: $uploadfile | Date: " . date('d-m-Y H:i:s') . "\r\n");
fclose($myfile);
?>