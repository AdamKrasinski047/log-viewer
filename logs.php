<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');


// źródło z parametru (PRO lub BETA)
$source = $_REQUEST['source'] ?? 'PRO';
$startDateTime = $_REQUEST['startDateTime']?? false;
$endDateTime = $_REQUEST['endDateTime']?? false;
$isTail = $_REQUEST['isTail'] ?? false;
$maxTimestamp = false;
// katalog i nazwę pliku na podstawie źródła
if ($source === 'PRO') {
    $logFile = __DIR__.'\logs_pro.log';
} elseif ($source === 'BETA') {
    $logFile = __DIR__.'\logs_beta.log';
} else {
    echo json_encode(["error" => "Invalid source specified"]);
    exit;
}

// Testowanie ścieżki
if (!file_exists($logFile)) {
    echo json_encode([
        "error" => "Log file not found",
        "path" => $logFile
    ]);
    exit;
}
function zassejNoweLinie($startDateTime){

}

function getUserName($details){
    // Lista słów kluczowych po których należy szukać użytkownika
    $keywords = [
        'Completed', 'Favorited', 'Adding', 'Creating', 'EditorType', 'Enabling',
        'Executing', 'Field', 'Found', 'Get editor', 'Internal', 'On change',
        'ParentFormEditorID', 'PrimaryKey', 'Skipping', 'Processing', 'Refreshing',
        'Calculating', 'Sending', 'Exposing', '(req', '(conn', 'Testing', 'History date','org.postgresql'
    ];

    // Wyodrębnianie użytkownika
    $user = '';
    $userPattern = '/^(\w+)\s/';
    if (preg_match($userPattern, $details, $matches)) {
        $potentialUser = $matches[1];

        // Sprawdź, czy użytkownik pojawia się przed słowem kluczowym
        foreach ($keywords as $keyword) {
            if (strpos($details, $keyword) !== false && strpos($details, $potentialUser) < strpos($details, $keyword)) {
                $user = $potentialUser;
                $details = trim(substr($details, strlen($potentialUser)));
                break;
            }
        }
    }
    return $user;
 }
 

// Funkcja do parsowania logów

function parseLogLine($line, &$is_error, &$is_new_error,&$maxTimestamp) {
    $line = trim($line);

    
    if (!$line) {
        return null;
    }
    $tempLineEx = explode(' --- [', $line, 2);
    if (count($tempLineEx) < 2) {
        return $line; // Linia nie pasuje do formatu
    }

    $leftPart = trim($tempLineEx[0]); // Lewa część linii (data, czas, typ, PID)
    $rightPart = trim($tempLineEx[1]); // Prawa część linii (logger, szczegóły)

    // Rozdzielenie lewego fragmentu na datę, czas i typ logu
    $timestamp = substr($leftPart, 0, 23); // Zakładamy format daty i czasu: 23 znaki
    $date = substr($timestamp, 0, 10); // YYYY-MM-DD
    $time = substr($timestamp, 11);    // HH:MM:SS.SSS
    if($maxTimestamp === false){
        $maxTimestamp = $timestamp;
    };
    // Typ logu (np. INFO, DEBUG, ERROR)
    $type = null;
    $typeMatch = preg_match('/\s(INFO|DEBUG|ERROR)\s/', $leftPart, $typeMatches);
    if ($typeMatch) {
        $type = $typeMatches[1];
    }

    // Jeśli typ to "ERROR", przetwarzamy wieloliniowy blok
    if ($type === 'ERROR') {
        $objType = null;
        $details = '';
        if($is_error){
            $is_new_error = true;
        }
        $is_error = true;
        $rightPart = substr($rightPart, strpos($rightPart, ']') + 1);
        // Pobierz logger i pierwszą linię szczegółów
        if (strpos($rightPart, ':') !== false) {
            list($objType, $firstDetail) = explode(':', $rightPart, 2);
            $objType = trim($objType);
            $details .= trim($firstDetail);
        }

        $user = getUserName($details);

        $details = trim(substr($details, strlen($user)));

        // Zwrot obiektu dla błędu
        return [
            "date" => $date,
            "time" => $time,
            "type" => $type,
            "objType" => $objType,
            "user" => $user, // Użytkownik zwykle nie dotyczy błędów
            "details" => $details,
            // "is_error" => true // Flaga wskazująca, że to log typu ERROR
        ];
    }

    // Przetwarzanie innych typów zdarzeń (INFO, DEBUG)
    $rightPart = substr($rightPart, strpos($rightPart, ']') + 1);
    $objType = null;
    $details = null;

    if (strpos($rightPart, ':') !== false) {
        list($objType, $details) = explode(':', $rightPart, 2);
        $objType = trim($objType);
        $details = trim($details);
    } else {
        $details = trim($rightPart);
    }

    $user = getUserName($details);
    $details =  trim(substr($details, strlen($user)));
    // Jeśli użytkownika nie ma, ustaw jako 'brak'
    if (!$user) {
        $user = '';
    }

    // Zwrot dla innych typów
    return [
         "date" => $date
        , "time" => $time
        , "type" => $type
        , "objType" => $objType
        , "user" => $user
        , "details" => $details
    ];
}

// Przetwarzanie pliku logów
$logData = [];
$lines = file($logFile);
$is_error = false;
$is_new_error = false;
$bufor = [];
$skipRangeError = false;

if($isTail){
    
}
foreach ($lines as $line) {
    $parsedLine = parseLogLine($line, $is_error,$is_new_error,$maxTimestamp);

    if($isTail){       
    }
    if(is_array($parsedLine)){
        if($parsedLine['type'] !=='ERROR' && $is_error){
            $index = count($logData) -1;

            if(count($bufor)>0){
                $logData[$index]['details'] .= implode("<br>", $bufor); //tutaj pewnie zepsuje "\n" - endline impode to jest złączenie tablic w tym kontekście
                $bufor = [];//ostatni i poprzedni z błędem impodujesz łączysz wszystko co miałeś w buforze i połączyć aby wszystko nie było zlane w jedną linię
            }
            $logData[] = $parsedLine;
            $is_error = false;
            continue;
        }
        if($parsedLine['type'] !=='ERROR' && !$is_error){
            $logData[] = $parsedLine;
            continue;
        }
        if($parsedLine['type'] ==='ERROR'){
            if($is_new_error) {
                $index = count($logData) -1;
                if(count($bufor)>0){
                    $logData[$index]['details'] .= "\n". implode("\n", $bufor); ///tutaj psuje
                    $bufor = [];
                }
                $logData[] = $parsedLine;
                $is_new_error = false;
                continue;
            }
            $logData[] = $parsedLine;
            continue;
        }
    }else{};
   $bufor[] = $parsedLine; // jak nie jest tablicą do bufora  
}




echo json_encode($logData, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);