<?php

/**
 * @param string $content phpunit streaming JSON
 * @return array(string "$class::$func" => $status)
 */
function parse_json_stream($content) {
  $content = '['
   . strtr($content, array("}{" => "},{"))
   . ']';
  return json_decode($content, TRUE);
}

function parse_junit_json($content) {
  $records = parse_json_stream($content);
  $results = array();
  foreach ($records as $r) {
    if ($r['event'] == 'test') {
      $results[$r['test']] = $r['status'];
    }
  }
  return $results;
}

function array_collect($arr, $col) {
  $r = array();
  foreach ($arr as $k => $item) {
    $r[$k] = $item[$col];
  }
  return $r;
}

class DiffPrinter {
  var $fromFile, $toFile;
  var $hasHeader = FALSE;

  function __construct($headers) {
    $this->headers = $headers;
  }

  function printHeader() {
    if ($this->hasHeader) return;

    ## LEGEND
    print "LEGEND\n";
    $i = 1;
    foreach ($this->headers as $header) {
      printf("% 2d: %s\n", $i, $header);
      $i++;
    }
    print "\n";

    ## HEADER
    printf("%-90s ", 'TEST NAME');
    $i = 1;
    foreach ($this->headers as $header) {
      printf("%-10d ", $i);
      $i++;
    }
    print "\n";

    $this->hasHeader = TRUE;
  }

  function printRow($test, $values) {
    $this->printHeader();
    printf("%-90s ", $test);
    foreach ($values as $value) {
      printf("%-10s ", $value);
    }
    print "\n";
  }
}

if (empty($argv[1])) {
  echo "usage: phpunit-json-diff <json-file1> [<json-file2>...]\n";
  exit(1);
}


$suites = array(); // array('file' => string, 'results' => array)
for ($i = 1; $i < count($argv); $i++) {
  $suites[$i] = array(
    'file' => $argv[$i],
    'results' => parse_junit_json(file_get_contents($argv[$i]))
  );
}

$tests = array();
foreach ($suites as $suiteName => $suite) {
  $tests = array_unique(array_merge(
    $tests,
    array_keys($suite['results'])
  ));
}
sort($tests);

$printer = new DiffPrinter(array_collect($suites, 'file'));
foreach ($tests as $test) {
  $values = array();
  foreach ($suites as $suiteName => $suite) {
    $values[] = isset($suite['results'][$test]) ? $suite['results'][$test] : 'MISSING';
  }

  if (count(array_unique($values)) > 1) {
    $printer->printRow($test, $values);
  }
}