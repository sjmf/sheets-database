<?php
require __DIR__ . '/vendor/autoload.php';

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient()
{
    $client = new Google_Client();
    $client->setApplicationName('Google Sheets API PHP Quickstart');
    $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
    $client->setAuthConfig(dirname(__FILE__) . '/secrets/client_secret_65380065949-dt8mbe1h4r1s7ui7pfak2lu6eilgrfks.apps.googleusercontent.com.json');
    $client->setAccessType('offline');
    $client->setPrompt('select_account consent');

    // Load previously authorized token from a file, if it exists.
    // The file token.json stores the user's access and refresh tokens, and is
    // created automatically when the authorization flow completes for the first time.
    $tokenPath = dirname(__FILE__) . '/secrets/token_65380065949-dt8mbe1h4r1s7ui7pfak2lu6eilgrfks.json';
    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}


function createSpreadsheet($service, $title) 
{
    $spreadsheet = new Google_Service_Sheets_Spreadsheet([
        'properties' => [
                'title' => $title
                    ]
                    ]);
    $spreadsheet = $service->spreadsheets->create($spreadsheet, [
        'fields' => 'spreadsheetId'
        ]);
    printf("Spreadsheet ID: %s\n", $spreadsheet->spreadsheetId);
    return $spreadsheet->spreadsheetId;
}


// Retrieve and update a filterView for querying data
// This requires a filterView 'gpxFilter' already set up with the condition 
// BETWEEN that we are going to modify to query on column 3
function updateFilterView($sheets, $service, $spreadsheetId) 
{
	// Find our filter view
	$filterViewName = "gpxFilter";
	$filterViews = $sheets[0]->getFilterViews();
	$filterView = NULL;

	// Find the filterView indicated by $filterViewName
	foreach ($filterViews as $i => $f) {
		if ($f->getTitle() == $filterViewName) {
			$filterView = $f;
			break;
		};
	};
	if (! $filterView) 
		throw new ErrorException("FilterView $filterViewName not found");

	// Update filterview to the parameters specified
	// Set range to whole sheet (get row count, set that as upper bound)
	$rows = $sheets[0]->getProperties()->getGridProperties()->getRowCount();
	$filterView->range->setEndRowIndex($rows);
	$filterView->range->setSheetId($sheetId);

	// Update date in range to from parameter
	if(! ($_GET['from'] && $_GET['to'])) 
		throw new ErrorException("Provide <from> and <to> parameters to GET");

	$bound_from = strval(intval($_GET['from']) * 1000000);
	$bound_to   = strval(intval($_GET['to']) * 1000000);

	// Criteria can be off at a weird index (mine is at [2])
	foreach ($filterView->getCriteria() as $index => $criteria) {
		if($criteria) {
			break;
		}
	}

	// This is naughty but PHP doesn't scope variables properly so whatever.
	// Set 'from' and 'to' values
	$criteria->condition->values[0]->setUserEnteredValue($bound_from);
	$criteria->condition->values[1]->setUserEnteredValue($bound_to);

	$filterValues = $filterView->getFilterSpecs()[0]->getFilterCriteria()->getCondition()->getValues();
	$filterValues[0]->setUserEnteredValue($bound_from);
	$filterValues[1]->setUserEnteredValue($bound_to);

	// 2. Batch update the sheet using the modified filterView
	$fvreq = new Google_Service_Sheets_UpdateFilterViewRequest();
	$fvreq->setFilter($filterView);
	//$fvreq->setFields("range", "criteria", "filterSpecs");
	$fvreq->setFields("*");

	// Make the batch update request
	$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest([
		'requests' => [new Google_Service_Sheets_Request(['updateFilterView' => $fvreq])]
	]);
    
    //print_r($filterView);
	$service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);

	return $filterView;
}


// Retrieve the `rowMetadata` of the sheet, and use these
// to build a filtereredRows array
function getFilteredRows($service, $sheetName, $spreadsheetId) {
	$sheets = $service->spreadsheets->get($spreadsheetId, ["ranges" => [$sheetName], "fields" => "sheets"])->getSheets();
	$rowMetadata = $sheets[0]->getData()[0]->getRowMetadata();
    //print_r($rowMetadata);
	$filteredRows = array(
		'hiddenRows' => array(),
		'showingRows' => array()
	);
	foreach ($rowMetadata as $i => $r) {
		if (isset($r['hiddenByFilter']) && $r['hiddenByFilter'] === true) {
			array_push($filteredRows['hiddenRows'], $i + 1);
		} else {
			array_push($filteredRows['showingRows'], $i + 1);
		};
	};
	return $filteredRows;
}

// Create new basic filter to the sheet you want to use using the retrieved settings of the filter view.
function createBasicFilter($service, $spreadsheetId, $sheetId, $filterView) {
	$filterView->range->sheetId = $sheetId;
	$requests = [
		new Google_Service_Sheets_Request(['clearBasicFilter' => ['sheetId' => $sheetId]]),
		new Google_Service_Sheets_Request([
			'setBasicFilter' => [
				'filter' => [
					'criteria' => $filterView->criteria,
					'filterSpecs' => $filterView->filterSpecs,
					'range' => $filterView->range,
					'sortSpecs' => $filterView->sortSpecs,
				]
			]
		])
	];
	$batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(['requests' => $requests]);
	$service->spreadsheets->batchUpdate($spreadsheetId, $batchUpdateRequest);
}
