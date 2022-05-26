<?php

print("<br><a href='upd_timeperiod.html'>Back to Update list time period tool</a>");

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
echo "<br>";

echo "Client ID set: " . $clientID;
echo "<br>";

echo "User GUID to use: " . $TalisGUID;
echo "<br>";

// Error reporting constants
const DEBUG = 4;
const INFO = 3;
const WARNING = 2;
const ERROR = 1;

// Error reporting User select
// This currently defaults to WARNING as the first select in url.html
$LOG_LEVEL = $_REQUEST['loglvl'];
echo "Logging Level Selected: " . getFriendlyLogLevelName($LOG_LEVEL) . "<br>";

$token = fetchToken($clientID, $secret);

// List time period mode
if (listTimePeriodMode()) {
    $time_period_json = getTenantTimePeriods($shortCode, $TalisGUID, $token);
        if (empty($time_period_json)) {
        echoMessageToScreen(ERROR, "No active tenancy time periods found - Process stopped");
        exit;
    }
    $active_time_periods = makeActiveTimePeriodArray($time_period_json);
    $list_active_time_periods = listActiveTimePeriods($active_time_periods);
    echo "<h3>Tenancy active time periods</h3>$list_active_time_periods<br>";
    echo "Process finished";
    exit;
}

$write_to_live = setDryRun();
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
$myfile = fopen("../../report_files/upd_timeperiod_output.log", "a") or die("Unable to open upd_timeperiod_output.log");
fwrite($myfile, "Started | Input File: $upload_file | Date: " . date('d-m-Y H:i:s') . "\r\n\r\n");
fwrite($myfile, "List ID" . "\t" . "List link" . "\t" . "Old Time Period" . "\t" ."New Time Period" . "\t" . "Outcome" . "\r\n");

//// Main script
// getTenantTimePeriods
$time_period_json = getTenantTimePeriods($shortCode, $TalisGUID, $token);
if (empty($time_period_json)) {
    echoMessageToScreen(ERROR, "No active time periods on tenancy found - Process stopped");
    exit;
}
$all_time_periods = makeAllTimePeriodArray($time_period_json);
$active_time_periods = makeActiveTimePeriodArray($time_period_json);

// For each list id and period description
$file_handle = fopen($upload_file, "r");
if ($file_handle == FALSE) {
    echoMessageToScreen(ERROR, "Could not open csv file - Process stopped");
    exit;
}
while (($line = fgetcsv($file_handle, 1000, ",")) !== FALSE) {
    $listID = trim($line[0], "\xEF\xBB\xBF");
    $new_time_period_desc = trim($line[1]);
    $list_link = "https://rl.talis.com/3/$shortCode/lists/$listID.html?lang=en-GB&login=1";
    echoMessageToScreen(INFO, "List ID: $listID");
    fwrite($myfile, "$listID\t$list_link\t");

    // Get list's current time period id
    $list_time_period_id = getListTimePeriod($shortCode, $TalisGUID, $token, $listID);
    if (empty($list_time_period_id)) { // getListTimePeriod failed
        fwrite($myfile, "No list data retrieved\r\n");
        continue;
    } 
    // Get list's current time period description from $all_time_periods
    if (isset($all_time_periods[$list_time_period_id])) {
        $list_time_period_desc = $all_time_periods[$list_time_period_id];                                                     //
        echoMessageToScreen(INFO, "Old time period: $list_time_period_desc");
        fwrite($myfile, $list_time_period_desc . "\t");
    } else { // No match found.
        echoMessageToScreen(INFO, "No old time period set");
        fwrite($myfile, "No old time period set\t");
    }

    // Search for the new time period's id in $active_time_periods
    if (!empty(array_search(strtolower($new_time_period_desc), array_map('strtolower', $active_time_periods)))) {
        // New time period description matches an active tenancy time period description
        $new_time_period_id = array_search(strtolower($new_time_period_desc), array_map('strtolower', $active_time_periods));
        echoMessageToScreen(INFO, "New time period: $new_time_period_desc");
        fwrite($myfile, $new_time_period_desc . "\t");
    } else {
        // New time period does not match any active tenancy time period, continue with next line of file
        echoMessageToScreen(WARNING, "$new_time_period_desc does not match an existing active time period - check your upload file for incorrect time periods");
        echoMessageToScreen(WARNING, "For a list of your tenancy's active time periods run this tool in <strong>List active time periods</strong> mode<br>");
        fwrite($myfile, "$new_time_period_desc does not match an existing active time period\tNothing updated\r\n");
        continue;
    }

    // Patch request
    if ($write_to_live === "true") {
        // Create patch body
        $body = createPatchBody($listID, $new_time_period_id);
        echoMessageToScreen(DEBUG, "Patch body: ". var_export($body, true));
        
        // Send patch request
        $patch_result = patchListTimePeriod($shortCode, $TalisGUID, $token, $listID, $body);
        if ($patch_result) {
            echoMessageToScreen(INFO, "Time period updated<br>");
            fwrite($myfile, "Time period updated\r\n");
        } else {
            fwrite($myfile, "Error - nothing updated\r\n");
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
