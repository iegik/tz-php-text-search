<?php
namespace App;

interface RecursiveDirectorySearchIteratorInterface {
  function find($text);
  static function human_filesize(int $bytes, int $decimals);
  function parse_file();
}

class RecursiveDirectorySearchIterator extends \RecursiveDirectoryIterator implements RecursiveDirectorySearchIteratorInterface {
  public static $BUFFER_SIZE = 1024;

  public static function human_filesize(int $bytes, int $decimals = 2) {
    $sz = 'BKMGTP';
    $factor = floor((strlen(strval($bytes)) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor] . ($factor > 0 ? 'b' : '');
  }
  
  public function parse_file() {
    $name = $this->getPathname();
    $info = self::human_filesize(intval(filesize($this->getPathname())));
  
    return function (array $item) use ($name, $info) {
      $content = $item[0];
      $line = $item[1];
      $position = join(',', array_column($item[2],1));
  
      return [
        'name' => $name,
        'line' => $line,
        'position' => $position,
        'content' => $content,
        'info' => $info,
      ];
    };
  }
  
  function find($text) {
    $file = fopen($this::getPathname(), 'r');
    if ($file === false) {
      echo "File not found\n";
      return true;
    }

    $all_matches = [];
    $line_number = 0;
    while (($buffer = fgets($file, self::$BUFFER_SIZE)) !== false) {
      $line_number++;

      // TODO: pass function througth interface
      if (preg_match_all("/" . $text . "/i", $buffer, $matches, PREG_OFFSET_CAPTURE)) {
        $all_matches = array_merge($all_matches, array_map(function ($item) use ($buffer, $line_number) {
          return [
            $buffer,
            $line_number,
            $item // position
          ];
        }, $matches));
      }
    }

    if (!feof($file)) {
      echo "Unexpected end of file\n";
      return true;
    }
    fclose($file);

    return $all_matches;
  }
}
