<?php 
    header('Content-Type: text/xml');
    //header('Content-Type: text/plain');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>" 
?>

<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpxx="http://www.garmin.com/xmlschemas/GpxExtensions/v3" 
     xmlns:gpxtpx="http://www.garmin.com/xmlschemas/TrackPointExtension/v1" creator="Oregon 400t" version="1.1" 
     xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
     xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">

<metadata>
    <link href="http://finnigan.dev/">
        <text>Samantha Finnigan</text>
    </link>
    <time><?php print gmdate("Y-m-d\TH:i:s\Z"); ?></time>
</metadata>

<?php
require __DIR__ . '/../client.php';

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

$spreadsheetId = "1zibFT1sfZVbdmqw5jSJok0D3SBQvQS-195BZbRSTp_A";

// Get all values from spreadsheet. TODO: restrict query!
$range = 'Sheet1!C2:V';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

if (! empty($values)) {
    foreach ($values as $row) {
        printf("<wpt lat=\"%s\" lon=\"%s\"><ele>%s</ele><time>%s</time></wpt>\n", $row[1], $row[2], $row[3], $row[0]);
    }
}
?>
</gpx>
