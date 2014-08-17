<?php

define('API_KEY', '***'); // edit this

// open the output CSV file and write the header

$output = fopen(__DIR__ . '/data/google.csv', 'w');
fputcsv($output, array('start', 'end', 'title', 'description'));

// the base URL for the calendar

$url = sprintf('https://www.googleapis.com/calendar/v3/calendars/%s/events', rawurlencode('schedule@resonancefm.com'));

// the parameters for the request

$params = array(
  'timeMin' => date(DATE_ATOM, '2007-07-30'),
  'timeMax' => date(DATE_ATOM),
  'timeZone' => 'Europe/London',
  'orderBy' => 'startTime',
  'singleEvents' => 'true',
  'maxResults' => 2500,
  'key' => API_KEY,
);

// initialise cURL

$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_VERBOSE => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => array('Accept: application/json'),
  CURLOPT_ENCODING => '', // enable gzipped responses
));

// make HTTP requests to fetch each page of the calendar

do {
  curl_setopt($curl, CURLOPT_URL, $url . '?' . http_build_query($params));

  $json = curl_exec($curl);
  $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
  $data = json_decode($json, true);

  if ($status !== 200 || !$data) {
    exit("The HTTP request was not successful\nRemember to add a Google API key to this script before running it\n");
  }

  // write the event data to the CSV file

  foreach ($data['items'] as $item) {
    fputcsv($output, array(
      'start' => $item['start']['dateTime'],
      'end' => $item['end']['dateTime'],
      'title' => trim($item['summary']),
      'description' => isset($item['description']) ? trim($item['description']) : null,
    ));
  }

  $params['pageToken'] = isset($data['nextPageToken']) ? $data['nextPageToken'] : null;
} while ($params['pageToken']);
