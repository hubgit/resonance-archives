# Resonance FM Archives

## Description

A set of PHP scripts for fetching and parsing historical calendar/listings data for Resonance FM, then matching those shows up to directories of MP3 files.

## Instructions

1. Parse the HTML listing files (in [calendar/data/html](calendar/data/html)) to a CSV file: `php calendar/html.php`
1. Fetch and parse the Google Calendar to a CSV file: `php calendar/google.php` (edit the script to add an API key before running it - see notes below)
1. From the directories of MP3 files, generate file listings in [files/data](files/data) - TODO: instructions.
1. Parse the file listings to a summary CSV file: `php files/files.php`
1. Find all the files that contain parts of each show: `php shows/shows.php`

## Google Calendar API key

Accessing the Google Calendar API requires an API key, which can be obtained from the [Google API Console](https://code.google.com/apis/console/): create a project, switch on Calendar API and generate a "Simple API Access" key (key for server apps).