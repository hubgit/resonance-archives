<?php

ini_set('memory_limit', '1G');

// read the items parsed from HTML and fetched from the Google Calendar

$files = array(
  __DIR__ . '/../calendar/data/html.csv',
  __DIR__ . '/../calendar/data/google.csv',
);

$shows = array();

foreach ($files as $file) {
  $input = fopen($file, 'r');
  $headers = fgetcsv($input);

  while (($row = fgetcsv($input)) !== false) {
    $show = array_combine($headers, $row);
    $show['start'] = strtotime($show['start']);
    $show['end'] = $show['end'] ? strtotime($show['end']) : null;
    $show['files'] = array();
    $shows[] = $show;
  }

  fclose($input);
}

// sort the shows earliest first

usort($shows, function($a, $b) {
  return $a['start'] - $b['start'];
});

// read in the files information

$files = array();

$input = fopen(__DIR__ . '/../files/data/files.csv', 'r');
$headers = fgetcsv($input);

while (($row = fgetcsv($input)) !== false) {
  $file = array_combine($headers, $row);
  $file['start'] = strtotime($file['start']);
  $file['end'] = strtotime($file['end']);
  $files[] = $file;
}

fclose($input);

// sort the files earliest first

usort($files, function($a, $b) {
  return $a['start'] - $b['start'];
});

// match each show to corresponding files

foreach ($shows as $i => &$show) {
  $start = $show['start'];

  if ($show['end']) {
    $end = $show['end'];
  } else {
    // if the end date is missing, set it to the start date of the following item (?)
    $next = $i + 1;

    if (isset($shows[$next])) {
      $end = $shows[$next]['start'];
    } else {
      // default to 1 hour if there's no known next show
      $end = $start + 3600;
    }
  }

  // loop through each file looking for a match
  // not particularly efficient, but good enough
  foreach ($files as $file) {
    // if the file started after the show ended, no need to check this or later files
    if ($file['start'] > $end) {
      break;
    }

    // if the file starts before the show ends, and ends after the show starts,
    // it probably contains some of the show
    if ($file['start'] < $end && $file['end'] > $start) {
      $offset = max(0, $start - $file['start']);

      // if the end is known, could add the length here (time and bytes)

      $file = array(
        'file' => array(
          'file' => $file['file'],
          //'size' => (int) $file['size'],
          //'length' => (int) $file['length'],
          'start' => date(DATE_ATOM, $file['start']),
          'end' => date(DATE_ATOM, $file['end']),
          'bitrate' => (int) $file['bitrate'],
        ),
        'offsetTime' => $offset,
        'offsetBytes' => floor(($offset * (int) $file['bitrate'] * 1000) / 8),
      );

      $show['files'][] = $file;
    }
  }

  $show['start'] = date(DATE_ATOM, $show['start']);
  $show['end'] = $show['end'] ? date(DATE_ATOM, $show['end']) : null;

  //print_r($show);
}

file_put_contents(__DIR__ . '/data/shows.json', json_encode($shows, JSON_PRETTY_PRINT));