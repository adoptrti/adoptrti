<?php
/**
 * @see https://stackoverflow.com/questions/1463480/how-can-i-use-php-to-dynamically-publish-an-ical-file-to-be-read-by-google-calen
 * @see https://developers.google.com/sheets/api/quickstart/php
 */
require_once __DIR__ . '/vendor/autoload.php';


define('APPLICATION_NAME', 'Google Sheets API PHP Quickstart');
define('CREDENTIALS_PATH', __DIR__ . '/data/sheets.googleapis.com-php-quickstart.json');
define('CLIENT_SECRET_PATH', __DIR__ . '/client_secret.json');
// If modifying these scopes, delete your previously saved credentials
// at ~/.credentials/sheets.googleapis.com-php-quickstart.json
define('SCOPES', implode(' ', array(
        Google_Service_Sheets::SPREADSHEETS_READONLY)
        ));

/*if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}*/

/**
 * Returns an authorized API client.
 * @return Google_Client the authorized client object
 */
function getClient() {
    $client = new Google_Client();
    $client->setApplicationName(APPLICATION_NAME);
    $client->setScopes(SCOPES);
    $client->setAuthConfig(CLIENT_SECRET_PATH);
    $client->setAccessType('offline');
    
    // Load previously authorized credentials from a file.
    $credentialsPath = expandHomeDirectory(CREDENTIALS_PATH);
    if (file_exists($credentialsPath)) {
        $accessToken = json_decode(file_get_contents($credentialsPath), true);
    } else {
        // Request authorization from the user.
        $authUrl = $client->createAuthUrl();
        printf("Open the following link in your browser:\n%s\n", $authUrl);
        print 'Enter verification code: ';
        $authCode = trim(fgets(STDIN));
        
        // Exchange authorization code for an access token.
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
        
        // Store the credentials to disk.
        if(!file_exists(dirname($credentialsPath))) {
            mkdir(dirname($credentialsPath), 0700, true);
        }
        file_put_contents($credentialsPath, json_encode($accessToken));
        printf("Credentials saved to %s\n", $credentialsPath);
    }
    $client->setAccessToken($accessToken);
    
    // Refresh the token if it's expired.
    if ($client->isAccessTokenExpired()) {
        $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        file_put_contents($credentialsPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

/**
 * Expands the home directory alias '~' to the full path.
 * @param string $path the path to expand.
 * @return string the expanded path.
 */
function expandHomeDirectory($path) {
    $homeDirectory = getenv('HOME');
    if (empty($homeDirectory)) {
        $homeDirectory = getenv('HOMEDRIVE') . getenv('HOMEPATH');
    }
    return str_replace('~', realpath($homeDirectory), $path);
}

// Get the API client and construct the service object.
$client = getClient();
$service = new Google_Service_Sheets($client);

// Prints the names and majors of students in a sample spreadsheet:
// https://docs.google.com/spreadsheets/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/edit
$spreadsheetId = '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms';
$spreadsheetId = trim(file_get_contents(__DIR__ . '/data/spreadsheet-id.txt'));
$range = 'Data Sheet!A2:D100';
$response = $service->spreadsheets_values->get($spreadsheetId, $range);
$values = $response->getValues();

$ical = "BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//hacksw/handcal//NONSGML v1.0//EN\n";

/*BEGIN:VEVENT
UID:" . md5(uniqid(mt_rand(), true)) . "@yourhost.test
DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z
DTSTART:19970714T170000Z
DTEND:19970715T035959Z
SUMMARY:Bastille Day Party
END:VEVENT
END:VCALENDAR";
*/

//set correct content-type-header
header('Content-type: text/csv; charset=utf-8');
//header('Content-Disposition: inline; filename=calendar.csv');
$csv = fopen('del.csv','w');

$ICS = fopen('del.ics','w');
fputs($ICS,$ical);
fputcsv($csv, ['Subject','Start date','Start time']);

if (count($values) == 0) {
    print "No data found.\n";
} else {
    print "Name, Major:\n";
    foreach ($values as $row) {
        // Print columns A and E, which correspond to indices 0 and 4.
        printevent($row,$ICS,$csv);        
    }
}

fputs($ICS,"END:VCALENDAR\n");

function printevent($row,$ICS,$CSV)
{
    list($project,$task,$date1,$status) = $row;
    $date2 = strtotime($date1);
    $date3 = gmdate('Ymd',$date2);//.'T'. gmdate('His',$date2) . "Z";
    //echo "$date3\n";
    //return;
     fputs($ICS,"BEGIN:VEVENT
     UID:" . md5(uniqid(mt_rand(), true)) . "@adoptrti.org
    DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z
     
    DTSTART;VALUE=DATE:$date3
    DTEND;VALUE=DATE:$date3
    
     SUMMARY:$status
     END:VEVENT\n");
     
     fputcsv($CSV,[$status,date('Y-m-d',$date2),date('Y-m-d',$date2)] );
     
     
    
}

fclose($ICS);
fclose($csv);
file_get_contents('del.csv');