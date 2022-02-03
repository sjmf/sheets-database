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

<trk>
<trkseg>
<?php
	// With a little help from: https://stackoverflow.com/a/67629945/1681205
	require __DIR__ . '/../client.php';

	// Get the API client and construct the service object.
	$client = getClient();
	$service = new Google_Service_Sheets($client);
	$spreadsheetId = "1zibFT1sfZVbdmqw5jSJok0D3SBQvQS-195BZbRSTp_A";
	$sheetName = 'Sheet1'; 

	$sheets = $service->spreadsheets->get($spreadsheetId, ["ranges" => [$sheetName]])->getSheets(); 
	$sheetId = $sheets[0]->getProperties()->getSheetId();

	$filterView = updateFilterView($sheets, $service, $spreadsheetId);
	createBasicFilter($service, $spreadsheetId, $sheetId, $filterView);
	$filteredRows = getFilteredRows($service, $sheetName, $spreadsheetId);

	// Our data is contiguous, so we can restrict the query based on showingRows
	$first = $filteredRows['showingRows'][1];
	$last = end($filteredRows['showingRows']);

	// Get all from spreadsheet range
	$sheets = $service->spreadsheets->get($spreadsheetId, ["ranges" => [$sheetName], "fields" => "sheets"])->getSheets();
	$range = "Sheet1!C$first:V$last";

	$response = $service->spreadsheets_values->get($spreadsheetId, $range);
	$values = $response->getValues();

	foreach($values as $row) {
		printf("<trkpt lat=\"%s\" lon=\"%s\"><ele>%s</ele><time>%s</time></trkpt>\n", $row[1], $row[2], $row[3], $row[0]);
	}
?>
</trkseg>
</trk>
</gpx>
