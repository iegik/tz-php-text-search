<?php
/**
 * Program for searching text in current directory
 * Saves found data into file in specific format
 * Outputs number of found lines
 * 
 * Some test text:
 * All I know about PHP in one index.php file
 */
declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
  exit;
}

// require 'search.php';
require __DIR__ . '/../lib/search.php';

$path = dirname($argv[0]);
$text = $argv[1];
$out = $argv[2];

$dir = new App\RecursiveDirectorySearchIterator($path);

function foreach_directory(object &$dir, $callback, int $level = 1) {
  foreach ($dir as $file) {
    if (in_array($file->getBasename(), ['.', '..'])) {
      continue;
    }

    $callback($file, $level);
    if (is_dir($file->getPathname())) {
      foreach_directory($file, $callback, $level + 1);
    }
  }
}

$all_found_data = [];
$total = 0;

foreach_directory($dir, function (&$file) use (&$dir, &$text, &$all_found_data, &$total) {
  $total++;
  $result = $dir->find($text);

  $all_found_data = array_merge($all_found_data, array_map($dir->parse_file(), $result));
});


// Rewriting output file
if (file_exists($out)) 
{
    unlink($out);
}

$files = join(', ', array_unique(array_column($all_found_data, 'name')));
echo "Found in " . count($all_found_data) . " lines in " . $files . "\n";

$fp = fopen($out, 'w');
fwrite($fp, print_r($all_found_data, true));
fclose($fp);
