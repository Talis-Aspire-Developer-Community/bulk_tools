<?php

function fetchToken($clientID, $secret) {
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
        echo "Successfully received token<br>";
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

function getTenantTimePeriods($shortCode, $TalisGUID, $token) {
    $request_url = 'https://rl.talis.com/3/' . $shortCode . '/time_periods';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $request_url);
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

    if ($info !== 200) {
        echoMessageToScreen(WARNING, "Unable to retrieve tenancy time periods: <pre>" . var_export($output, true) . "</pre>");
        return false;
    } else {
        echoMessageToScreen(DEBUG, "Successfully retrieved tenancy time periods: <pre>" . var_export($output, true) . "</pre>");
        return $output_json;
    }
}

function getListTimePeriod($shortCode, $TalisGUID, $token, $listID) {
    $request_url = 'https://rl.talis.com/3/' . $shortCode . '/lists/' . $listID;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $request_url);
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

    if ($info !== 200) {
        echoMessageToScreen(WARNING, "Unable to retrieve list data: <pre>" . var_export($output, true) . "</pre>");
        return false;
    } else {
        echoMessageToScreen(DEBUG, "Successfully retrieved list data: <pre>" . var_export($output, true) . "</pre>");
        if (isset($output_json->data->relationships->period->data->id)) {
            $list_time_period_id = $output_json->data->relationships->period->data->id;
            return $list_time_period_id;
        } else {
            return "No time period set";
        }
        
    }
}

function patchListTimePeriod($shortCode, $TalisGUID, $token, $listID, $body) {
    $patch_url = "https://rl.talis.com/3/" . $shortCode . "/lists/" . $listID;
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $patch_url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "X-Effective-User: $TalisGUID",
        "Authorization: Bearer $token",
        'Cache-Control: no-cache'
    ));

    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

    $output = curl_exec($ch);
    $info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($info !== 200){
        echoMessageToScreen(WARNING, "Patch unsuccessful: <pre>" . var_export($output, true) . "</pre>");
        return false;
    } else {
        return true;
    }
}

function createPatchBody($listID, $new_time_period_id) {
    $body = '{
                "data":{
                    "id":"' . $listID . '",
                    "type":"lists",
                    "relationships":{
                        "period":{
                            "data":{
                                "id":"' . $new_time_period_id . '",
                                "type":"periods"
                            }
                        }
                    }
                }
            }';
    return $body;
}

function makeAllTimePeriodArray($time_period_json) {
    $time_periods = [];
        for ($i = 0, $size = count($time_period_json->data); $i < $size; ++$i) {
            $id = $time_period_json->data[$i]->id;
            $desc = $time_period_json->data[$i]->attributes->description;
            $time_periods[$id] = $desc;
        }
        return $time_periods;
}

function makeActiveTimePeriodArray($time_period_json) {
    $time_periods = [];
        for ($i = 0, $size = count($time_period_json->data); $i < $size; ++$i) {
            $is_active = $time_period_json->data[$i]->attributes->is_active;
            if ($is_active) {
                $id = $time_period_json->data[$i]->id;
                $desc = $time_period_json->data[$i]->attributes->description;
                $time_periods[$id] = $desc;
            }
        }
        return $time_periods;
}

function listActiveTimePeriods($active_time_periods) {
    asort($active_time_periods);
    $list = "";
    foreach($active_time_periods as $desc) {
        $list = $list . "$desc<br>";
    }
    return $list;
}

function listTimePeriodMode() {
    if(isset($_REQUEST['LIST_MODE']) &&
    $_REQUEST['LIST_MODE'] == "list_mode") {
        $time_period_mode = true;
        echo "List time period mode: true<br><br>";
    }
    else
    {
        $time_period_mode = false;
    }

    return $time_period_mode;
}

function setDryRun() {
    if(isset($_REQUEST['DRY_RUN']) &&
    $_REQUEST['DRY_RUN'] == "write_to_live") {
        $write_to_live = "true";
    }
    else
    {
        $write_to_live = "false";
    }

    echo "Writing to live tenancy?: $write_to_live";
    echo "<br>";
    echo "<br>";

    return $write_to_live;
}

function getFriendlyLogLevelName($log_level) {
    // Map log levels to friendly names for humans
    $log_level_map = [
        4 => "DEBUG",
        3 => "INFO",
        2 => "WARN",
        1 => "ERROR"
    ];
    return $log_level_map[$log_level];
}

function echoMessageToScreen($log_level, $message){
    global $LOG_LEVEL;
    // Echo the log message if the log level says we should.
    if ($LOG_LEVEL >= $log_level) {
        $friendly_name = getFriendlyLogLevelName($log_level);
        echo "<br><strong>{$friendly_name}</strong>  $message";
    }
}

?>
