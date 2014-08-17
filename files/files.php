<?php

// open the output CSV file and write the header

$output = fopen(__DIR__ . '/data/files.csv', 'w');
fputcsv($output, array('file', 'name', 'size', 'start', 'bitrate', 'length', 'end'));

// read in file bitrates (generated with a bash script running something like `find -name *.mp3 -exec file {}\;`)

$bitrates = array();

$rows = file(__DIR__ . '/data/files.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($rows as $row){
  preg_match('/^(.+?\.mp3).+?(\d+) kbps/', $row, $matches);

  if (!$matches){
    printf("No bitrate match for %s\n", $row);
    continue;
  }

  list(, $file, $bitrate) = $matches;

  $name = basename($file, '.mp3');

  $bitrates[$name] = $bitrate;
}

// handle each item from the file listing

$files = file(__DIR__ . '/data/file-sizes.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

foreach ($files as $line){
  preg_match('/staff\s+(\d+)\s+.*\.\/(.+\.mp3)/', $line, $matches);

  list(, $size, $file) = $matches;

  $name = basename($file, '.mp3');

  $start = parse_filename($name);

  if (!$start) {
    printf("Unknown date format: %s\n", $file);
    continue;
  }

  $item = array(
    'file' => $file,
    'name' => $name,
    'size' => $size,
    'start' => strtotime($start),
    'bitrate' => null,
    'length' => null,
    'end' => null,
  );

  if (isset($bitrates[$name])) {
    $item['bitrate'] = $bitrates[$name];
    $item['length'] = floor(($item['size'] * 8) / ($bitrate * 1000)); // number of bits / bits per second; floor?
    $item['end'] = date(DATE_ATOM, $item['start'] + $item['length']);
  }

  $item['start'] = date(DATE_ATOM, $item['start']);

  fputcsv($output, $item);
}

function parse_filename($name){
  if (preg_match('/^(\d{4})(\d{2})(\d{2}) (\d{2})-(\d{2})-(\d{2})$/', $name, $matches)) {
    list(, $year, $month, $day, $hour, $minutes, $seconds) = $matches;
  } else if (preg_match('/^(\d{4})(\d{2})(\d{2}) (\d{2})-(\d{2})$/', $name, $matches)){
    list(, $year, $month, $day, $hour, $minutes) = $matches;
    $seconds = '00';
  } else if (preg_match('/^(\d{4})(\d{2})(\d{2}) [a-z].*$/i', $name, $matches)){
    list(, $year, $month, $day) = $matches;
    $hour = '17'; // '00';
    $minutes = '00';
    $seconds = '00';
  } else if (preg_match('/^(\d{4})(\d{2})(\d{2})$/i', $name, $matches)){
    list(, $year, $month, $day) = $matches;
    $hour = '17'; // '00';
    $minutes = '00';
    $seconds = '00';
  } else if (preg_match('/^(\d{2})(\d{2})(\d{2})_(\d{2})(\d{2})$/i', $name, $matches)){
    list(, $day, $month, $year, $hour, $minutes) = $matches;
    $seconds = '00';
  } else if (preg_match('/^(\d{4})(\d{2})(\d{2})_(\d{2})_(\d{2})_(\d{2})$/', $name, $matches)) {
    list(, $year, $month, $day, $hour, $minutes, $seconds) = $matches;
  } else {
    return;
  }

  if (strlen($year) === 2) {
    $year = '20' . $year;
  }

  return sprintf('%d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minutes, $seconds);
}