<?php
function fetchCsv($url) {
    echo "Fetching: $url\n";
    $csv = file_get_contents($url);
    if (!$csv) {
       echo "FAILED to fetch $url\n\n"; return;
    }
    $lines = explode("\n", $csv);
    if (count($lines) > 0) {
        $headers = str_getcsv($lines[0]);
        print_r($headers);
    }
}

fetchCsv("https://docs.google.com/spreadsheets/d/1bbvU0GR5nf3LWb_yOpHLtM492r1tAcx3VcrD0Ec75d0/export?format=csv");
fetchCsv("https://docs.google.com/spreadsheets/d/1hwi4lNbMLMgXTn-0EtbZCtIqjOybT0_mWKiuFr0W1nw/export?format=csv");
