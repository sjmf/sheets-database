<?php
require __DIR__ . '/client.php';

function main() {
    // Handle Tracker POSTS to this API
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['HTTP_USER_AGENT'] === "FONA") {
        return handle_tracker_post();
    } 

    // GET access from a browser?
    if(preg_match('/Mozilla/i',$_SERVER['HTTP_USER_AGENT'])) {
        echo "<pre>";
    }
    print_r(file_get_contents("loc.htm"));
    print_r("\n");
}

function handle_tracker_post() {
    //echo ; // debug
    file_put_contents("loc.htm", file_get_contents("php://input"));
    $data = explode(',', $_POST['gps']);
    array_push($data, $_POST['vbat'], $_POST['vpc']);
    write_to_sheet($data);
    echo 'k'; // Due to a bug in Adafruit_FONA.cpp (line 2277) we must return a body of >1 byte long.
}

function write_to_sheet($data) {
    $client = getClient();
    $service = new Google_Service_Sheets($client);
    $spreadsheetId = "1zibFT1sfZVbdmqw5jSJok0D3SBQvQS-195BZbRSTp_A";

    // Write to Google Sheet
    // https://stackoverflow.com/a/60001963
    $range = 'Sheet1!A:W';
    $body = new Google_Service_Sheets_ValueRange([ 'values' => [$data] ]);
    $params = [ 'valueInputOption' => "RAW" ];
    $result = $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
}

main();
