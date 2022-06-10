<?php

print("</br><a href='change_owner.html'>Back to Change Owner tool</a>");

ini_set('max_execution_time', '0');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<p>Starting</p>";

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

// Error reporting constants
const DEBUG = 4;
const INFO = 3;
const WARNING = 2;
const ERROR = 1;

// Error reporting User select
// This currently defaults to WARNING as the first select in url.html
$LOG_LEVEL = $_REQUEST['loglvl'];
echo "Logging Level Selected: " . getFriendlyLogLevelName($LOG_LEVEL) . "<br>";

// Set run options
$write_to_live = setDryRun();
$user_email_mode = setEmailMode();

$token = token_fetch($clientID, $secret);

// Upload file
$uploads_dir = '../uploads/';
$upload_file = $uploads_dir . basename($_FILES['userfile']['name']);

if (move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_file)) {
	echoMessageToScreen(INFO, "File is valid, and was successfully uploaded<br>");
} else {
	exit("File is invalid and failed to upload - please click back and try again");
}

echo 'File uploaded:';
print_r($upload_file);
echo "<br><br>";
echo "Run started<br>";

// Create log file
$myfile = fopen("../../report_files/change_owner_output.log", "a") or die("Unable to open change_owner_output.log");
fwrite($myfile, "Started | Input File: $upload_file | Date: " . date('d-m-Y H:i:s') . "\r\n");
fwrite($myfile, "Updating using user email as input: $user_email_mode\r\n\r\n");
fwrite($myfile, "List ID" . "\t" . "List link" . "\t" . "Old Owner Name" . "\t" . "Old Owner ID" . "\t" . "New Owner Name" . "\t" . "New Owner ID" . "\t" ."Outcome" . "\r\n");

$file_handle = fopen($upload_file, "r");
if ($file_handle == FALSE) {
	echoMessageToScreen(ERROR, "Could not open text file - Process Stopped.");
	exit;
}

while (($line = fgetcsv($file_handle, 1000, ",")) !== FALSE) {
	$listID = trim($line[0], "\xEF\xBB\xBF"); // Account for potential BOM
	$list_link = "https://rl.talis.com/3/$shortCode/lists/$listID.html?lang=en-GB&login=1";
	$new_owner_data = trim($line[1]);

	echoMessageToScreen(INFO, "List ID: $listID");
	fwrite($myfile, "$listID\t$list_link\t");

	// Data validation on supplied owner data to filter unnecessary api requests
	// Check that new owner data was supplied
	if(empty($new_owner_data)) {
		echoMessageToScreen(WARNING, "No new owner data supplied. Skipping...<br>");
		fwrite($myfile, "\t\t\t\tNo new owner data supplied - nothing updated.\r\n");
		continue;
	}
	// Check that supplied value looks remotely like an email address, i.e. contains @ 
	if ($user_email_mode === "true") {
		if (!strpos($new_owner_data, "@")) {
			echoMessageToScreen(WARNING, "Supplied user email not valid: \"$new_owner_data\". Skipping...<br>");
			fwrite($myfile, "\t\t\t\tSupplied user email not valid: \"$new_owner_data\" - nothing updated.\r\n");
			continue;
		}
	}
	
	// Get old list owner information for logs
	$old_owner_id = getListOwnerId($shortCode, $TalisGUID, $token, $listID);
	if (empty($old_owner_id)) {
		echoMessageToScreen(INFO, "No old list owner data found");
		fwrite($myfile, "No name data\tNo id data\t");
	} else {
		$old_owner_name = getUserName($shortCode, $TalisGUID, $token, $old_owner_id);
		if (empty($old_owner_name)) {
			echoMessageToScreen(INFO, "No user data found");
			fwrite($myfile, "No name data\tNo id data\t");
		} else {
			echoMessageToScreen(INFO, "Old list owner: $old_owner_name ($old_owner_id)");
			fwrite($myfile, "$old_owner_name\t$old_owner_id\t");
		}
	}

	// Get new owner information
	if ($user_email_mode === "true") {
		// User email supplied in upload file
		$new_owner_array = searchUser($shortCode, $TalisGUID, $token, $new_owner_data);
		if (empty($new_owner_array)) {
			echoMessageToScreen(WARNING, "Cannot find user ID using supplied email address: \"$new_owner_data\". Skipping...<br>");
			fwrite($myfile, "No user name found\tNo user id found\tUser ID not found from email address - nothing updated.\r\n");
			continue;
		} else {
			$new_owner_name = $new_owner_array['fullname'];
			$new_owner_id = $new_owner_array['id'];
			$flag = "";
			if ($new_owner_array['users_found'] > 1) {
				$flag .= "*";
			}
			echoMessageToScreen(INFO, "New list owner: $new_owner_name$flag ($new_owner_id)");
			fwrite($myfile, "$new_owner_name$flag\t$new_owner_id\t");
		}
	} else {
		// User ID supplied in upload file
		$new_owner_id = $new_owner_data;
		$new_owner_name = getUserName($shortCode, $TalisGUID, $token, $new_owner_id);
		if (empty($new_owner_name)) {
			echoMessageToScreen(INFO, "No user name for supplied ID \"$new_owner_id\" found. Skipping...<br>");
			fwrite($myfile, "No user data found for ID:\t$new_owner_id\tIncorrect user ID - nothing updated.\r\n");
			continue;
		} else {
			echoMessageToScreen(INFO, "New list owner: $new_owner_name ($new_owner_id)");
			fwrite($myfile, "$new_owner_name\t$new_owner_id\t");
		}
	}

	$etag = etag_fetch($shortCode, $listID, $TalisGUID, $token);

	if ($write_to_live === "true") {
		if (empty($etag)) {
			echoMessageToScreen(DEBUG, "Etag empty, nothing updated<br>");
			fwrite($myfile, "Etag empty, nothing updated\r\n");
			continue;
		} else {
			$input = patchBody($etag, $listID, $new_owner_id);
			$patch_result = ownerPatch($shortCode, $TalisGUID, $token, $input, $listID, $new_owner_id, $myfile);
			if ($patch_result) {
				echoMessageToScreen(INFO, "List owner updated<br>");
				fwrite($myfile, "List owner updated\r\n");
			} else {
				echoMessageToScreen(ERROR, "Nothing updated<br>");
				fwrite($myfile, "Error - nothing updated\r\n");
			}
		}
	} else {
		echoMessageToScreen(INFO, "Dry Run - nothing updated<br>");
		fwrite($myfile, "Dry Run - nothing updated\r\n");
	}
}

fwrite($myfile, "\r\n" . "Stopped | End of File: $upload_file | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n\r\n");
fclose($file_handle);
fclose($myfile);

echo "<br>Run finished<br>";
print("</br><a href=$myfile>Click Here to download the log file</a>");
?>