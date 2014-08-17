<?php

// open the output CSV file and write the header

$output = fopen(__DIR__ . '/data/html.csv', 'w');
fputcsv($output, array('start', 'end', 'title', 'description', 'name', 'web', 'email', 'archive'));

// the timezone for each date

$timezone = new DateTimeZone('Europe/London');

// extract content from each HTML file

$files = glob(__DIR__ . '/html/*.html');

foreach ($files as $file){
  // parse the date from the file name

  $name = basename($file, '.html');

  if (!preg_match('/\d{8}/', $name, $matches)){
    print "Couldn't find a Ymd date in $name\n";
    continue;
  }

  $date = $matches[0];

  // load the HTML file

  $dom = new DOMDocument;
  @$dom->loadHTMLFile($file);

  $xpath = new DOMXPath($dom);

  $nodes = $xpath->query("/html/body/table/tr[2]/td/table");

  if (!$nodes->length){
    printf("No listings in %s\n", $file);
    continue;
  }

  // handle each item in the HTML file

  foreach ($nodes as $node){
    $time = $xpath->evaluate("string(tr[1]/td[1])", $node);

    if (!$time || $time == 'TIME:') {
      continue;
    }

    $item = array(
      'start' => null,
      'end' => null,
      'title' => trim($xpath->evaluate("string(tr[1]/td[2]/b)", $node)),
      'description' => nodeContent($xpath->query("tr[2]/td[2]", $node)->item(0)),
      'name' => $xpath->evaluate("string(tr[1]/td[3]/b)", $node),
      'web' => null,
      'email' => null,
      'archive' => null,
      );

    foreach (array('web', 'email', 'archive') as $key){
      $item[$key] = $xpath->evaluate("string(tr[2]/td[3]/a[@class='$key']/@href)", $node);
    }

    // add the time of the item to the date from the file name

    $time_parts = preg_split('/\D/', $time);

    if (count($time_parts) == 1) {
      $time_parts[] = '00';
    }

    // hour > 23
    if ((int) $time_parts[0] > 23) {
      $time_parts[0] = sprintf('%02d', (int) $time_parts[0] - 24);
    }

    $datetime = $date . ' ' . implode(':', $time_parts);
    $start = DateTime::createFromFormat('Ymd H:i', $datetime, $timezone);

    // morning
    if ((int) $start->format('H') < 6) {
      $start->modify('+1 day');
    }

    $item['start'] = $start->format(DATE_ATOM);

    // output the item to the CSV file

    fputcsv($output, $item);
  }
}

// create a new HTML paragraph element containing the contents of this node
function nodeContent($node) {
  if (!is_object($node) || !$node->hasChildNodes()) {
    return false;
  }

  $doc = new DOMDocument;
  $p = $doc->createElement('p');
  $doc->appendChild($p);

  foreach ($node->childNodes as $child) {
    $p->appendChild($doc->importNode($child, true));
  }

  return $doc->saveHTML($p);
}