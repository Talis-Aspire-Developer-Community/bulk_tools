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
fwrite($myfile, "Started | Input File: $upload_file | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n");
fwrite($myfile, "List ID" . "\t" . "Owner GUID" . "\t" . "Outcome" . "\r\n");

$file_handle = fopen($upload_file, "r");
if ($file_handle == FALSE) {
	echoMessageToScreen(ERROR, "Could not open text file - Process Stopped.");
	exit;
}

while (($line = fgetcsv($file_handle, 1000, ",")) !== FALSE) {

	$listID = trim($line[0], "\xEF\xBB\xBF"); // Account for potential BOM
	$ownerID = trim($line[1]);

	echoMessageToScreen(INFO, "List ID: $listID");
	fwrite($myfile, $listID . "\t");
	echoMessageToScreen(INFO, "Owner ID: $ownerID");
	fwrite($myfile, $ownerID . "\t");
		
	$etag = etag_fetch($shortCode, $listID, $TalisGUID, $token);

	if ($write_to_live === "true") {
		if (empty($etag)) {
			echoMessageToScreen(DEBUG, "Etag empty, nothing updated<br>");
			continue;
		} else {
			$input = patchBody($etag, $listID, $ownerID);
			$patch_result = ownerPatch($shortCode, $TalisGUID, $token, $input, $listID, $ownerID, $myfile);
			if ($patch_result) {
				echoMessageToScreen(INFO, "List owner updated<br>");
				fwrite($myfile, "List owner updated\r\n");
			} else {
				fwrite($myfile, "Error - nothing updated\r\n");
			}
		}
	} else {
		echoMessageToScreen(INFO, "Dry Run - nothing updated.<br>");
        fwrite($myfile, "Dry Run - nothing updated.\r\n");
	}

}

fwrite($myfile, "\r\n" . "Stopped | End of File: $upload_file | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n\r\n");
fclose($file_handle);
fclose($myfile);

echo "<br>Run finished<br>";
print("</br><a href=$myfile>Click Here to download the log file</a>");
?>