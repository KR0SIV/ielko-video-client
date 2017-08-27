<?php

/**
* @author Robert Widdick
* @category PHP Classes, parsing m3u files (generated by such applications like WinAmp)
* @copyright Copyleft - See below
* @license  http://www.gnu.org/licenses/gpl-3.0.txt - GNU GPL 3.0
* @todo Not sure what else "to do"
* @tutorial See usage and tutorial below m3uParser class
* @version 2.1.5
*
* Benchmark
* - 100,000 Entries parsed in 3.186 seconds (with pretty formatting)
* - 100,000 Entries parsed in 1.509 seconds (without any output)
*
* Changelog
* - Version 2.1.5: Removed BC Math calculation functions, replaced with legacy usage
* - Version 2.1.4: Bug fix (can't remember)
* - Version 2.1.3: Secondary public release with extra features
* - Version 1: Intial release
**/

/**
* @desc Disable/Enable error reporting
* Use 'E_ALL' for all errors, 'E_NONE' for nothing (without quotes)
*/
error_reporting(E_ALL);

/**
* @desc Set the time limit (seconds)
*/
set_time_limit(15);

class m3uParser {
  /*
  * Private Variables
  */
  private $m3uFile;
  private $m3uFile_SongLengths;
  private $m3uFile_SongTitles;
  private $m3uFile_SongLocations;

  /**
  * @desc Load the M3u file and initiate it for parsing
  */
  public function __construct($m3uFile) {
    /**
    * @desc Load the file into an array
    **/
    if(file_exists($m3uFile))
      $this -> m3uFile = file($m3uFile);
    else
      die("Unable to locate '$m3uFile'");

    /**
    * @desc "Loosely" check that the file is an m3u file
    **/
    if(strtoupper(trim($this -> m3uFile[0])) != "#EXTM3U")
      die("The file specified {$this -> m3uFileLocation} is not a valid M3U playlist.");

    /**
    * @desc Remove extra empty lines
    */
    $buffer = array();
    foreach($this -> m3uFile as $line) {
      if($line != "\n" || $line != "\r" || $line != "\r\n" || $line != "\n\r")
        $buffer[] = $line;
    }
    $this -> m3uFile = $buffer;

    /**
    * @desc Shift the first line "#EXTM3U" off the array
    **/
    array_shift($this -> m3uFile);

    /**
    * @desc Start parsing the m3u file
    */
    $this -> _init();
  }

  /**
  * @desc Hopefully free some memory (though not yet proven to work as thought)
  */
  public function __destruct() {
    unset($this);
  }

  /**
  * @desc Initiate each array storing the Song Lengths, Titles and Locations
  */
  private function _init() {
    foreach($this -> m3uFile as $key => $line) {
      if(strtoupper(substr($line, 0, 8)) == "#EXTINF:") {
        $line = substr_replace($line, "", 0, 8);
        $line = explode(",", $line, 2);

        $this -> m3uFile_SongLengths[]   = $line[0];
        $this -> m3uFile_SongTitles[]    = $line[1];
        $this -> m3uFile_SongLocations[] = $this -> m3uFile[$key + 1];
      }
    }
  }

  /**
  * @desc Single or Multi case[in]sensitive searching
  * @return array Returns array such as ["search string"] => "result[s]"
  */
  public function searchTitles($search, $caseSensitive = false) {
    $results = array();

    if(is_array($search)) {
      foreach($search as $terms) {
        foreach($this -> m3uFile_SongTitles as $songTitle) {
          $_search = $caseSensitive ? strstr($songTitle, $terms) : stristr($songTitle, $terms);

          if($_search)
            $results[$terms][] = $songTitle;
        }
      }
    } else {
      foreach($this -> m3uFile_SongTitles as $songTitle) {
        $_search = $caseSensitive ? strstr($songTitle, $search) : stristr($songTitle, $search);

        if($_search)
          $results[] = $songTitle;
      }
    }

    return $results;
  }

  /**
  * @desc Single or Multi case[in]sensitive searching
  * @return array Returns array such as ["search string"] => "result[s]"
  */
  public function searchLocations($search, $ignoreDirectorySeperator = true, $caseSensitive = false) {
    $results = array();

    if(is_array($search)) {
      foreach($search as $terms) {
        foreach($this -> m3uFile_SongLocations as $songLocation) {
          if($ignoreDirectorySeperator)
            $_search = $caseSensitive ? strstr(str_replace(array("/", "\\"), "", $songLocation), $terms) : stristr(str_replace(array("/", "\\"), "", $songLocation), $terms);
          else
            $_search = $caseSensitive ? strstr($songLocation, $terms) : stristr($songLocation, $terms);

          if($_search)
            $results[$terms][] = $songLocation;
        }
      }
    } else {
      foreach($this -> m3uFile_SongLocations as $songLocation) {
        if($ignoreDirectorySeperator)
          $_search = $caseSensitive ? strstr(str_replace(array("/", "\\"), "", $songLocation), $search) : stristr(str_replace(array("/", "\\"), "", $songLocation), $search);
        else
          $_search = $caseSensitive ? strstr($songLocation, $terms) : stristr($songLocation, $terms);

        if($_search)
          $results[] = $songLocation;
      }
    }

    return $results;
  }

  /**
  * @desc Search song lengths by equal length, less than length, less than or equal to length, greater than length, greater than or equal to length or in between [start, end].
  * @return array Returns array such as ["length"] => "title[s]"
  */
  public function searchLengths($type, $start, $end = null) {
    $results = array();

    foreach($this -> m3uFile_SongLengths as $key => $length) {
      switch($type) {
        // Find lengths that equal to $start
        case 0: {
          if(!is_array($start)) {
            if($length == $start)
              $results[] = array($length => $this -> m3uFile_SongTitles[$key]);
          } else {
            foreach($start as $sLength) {
              if($sLength == $length)
                $results[] = array($sLength => $this -> m3uFile_SongTitles[$key]);
            }
          }
        } break;

        // Find lengths that are less than $start
        case 1: {
          if(!is_array($start)) {
            if($start < $length)
              $results[] = array($length => $this -> m3uFile_SongTitles[$key]);
          } else {
            foreach($start as $length) {
              if($sLength < $sLength)
                $results[] = array($sLength => $this -> m3uFile_SongTitles[$key]);
            }
          }
        } break;

        // Find lengths that are less than or equal to $start
        case 2: {
          if(!is_array($start)) {
            if($start <= $length)
              $results[] = array($length => $this -> m3uFile_SongTitles[$key]);
          } else {
            foreach($start as $sLength) {
              if($sLength <= $length)
                $results[] = array($sLength => $this -> m3uFile_SongTitles[$key]);
            }
          }
        } break;

        // Find lengths that are longer than $start
        case 3: {
          if(!is_array($start)) {
            if($start > $length)
              $results[] = array($length => $this -> m3uFile_SongTitles[$key]);
          } else {
            foreach($start as $sLength) {
              if($sLength > $length)
                $results[] = array($sLength => $this -> m3uFile_SongTitles[$key]);
            }
          }
        } break;

        // Find lengths that are longer or equal to $start
        case 4: {
          if(!is_array($start)) {
            if($start >= $length)
              $results[] = array($length => $this -> m3uFile_SongTitles[$key]);
          } else {
            foreach($start as $sLength) {
              if($sLength >= $length)
                $results[] = array($sLength => $this -> m3uFile_SongTitles[$key]);
            }
          }
        } break;

        // Find lengths between $start and $end
        case 5: {
          if(!is_array($start) && !is_array($end)) {
            if($length >= $start && $length <= $end)
              $results[] = array($length => $this -> m3uFile_SongTitles[$key]);
          } else {
            foreach($start as $sLength) {
              if($sLength >= $start[$key] && $sLength <= $end[$key])
                $results[] = array($sLength => $this -> m3uFile_SongTitles[$key]);
            }
          }
        } break;
      }
    }

    return $results;
  }

  /**
  * @desc Output the m3u in a human-readable format (includes table-output)
  * @return string The buffer for output
  */
  public function prettyOutput($sortWhat = "songTitle", $sortDirection = "asc", $drawTable = false, $tableWidth = 700, $table_cellSpacing = 0, $table_cellPadding = 0, $table_tableBorder = 0, $table_params = null) {
    $buffer = "";

    // Get statistics
    $totalSongs    = number_format(count($this -> m3uFile_SongTitles));
    $totalPlayTime = 0;

    foreach($this -> m3uFile_SongLengths as $length)
      $totalPlayTime += $length;

    $totalPlayTime = $this -> formatPlayTime($totalPlayTime);

    // Output
    if($drawTable) {
      $buffer .= "<table width=\"{$tableWidth}\" cellspacing=\"{$table_cellSpacing}\" cellpadding=\"{$table_cellPadding}\" border=\"{$table_tableBorder}\" {$table_params}>\n";

      $buffer .= "<tr>\n";
      if($sortWhat == "songTitle") {
        if($sortDirection == "asc")
          $buffer .= "  <td align=\"center\" {$table_params}>[ASC] <u><strong>Title</strong></u></td>\n";
        else
          $buffer .= "  <td align=\"center\" {$table_params}>[DESC] <u><strong>Title</strong></u></td>\n";
      } else {
        $buffer .= "  <td align=\"center\" {$table_params}><strong>Title</strong></td>\n";
      }

      if($sortWhat == "songLocation") {
        if($sortDirection == "asc")
          $buffer .= "  <td align=\"center\" {$table_params}>[ASC] <u><strong>Location</strong></u></td>\n";
        else
          $buffer .= "  <td align=\"center\" {$table_params}>[DESC] <u><strong>Location</strong></u></td>\n";
      } else {
        $buffer .= "  <td align=\"center\" {$table_params}><strong>Location</strong></td>\n";
      }

      if($sortWhat == "songLength") {
        if($sortDirection == "asc")
          $buffer .= "  <td align=\"center\" {$table_params}>[ASC] <u><strong>Length (secs)</strong></u></td>\n";
        else
          $buffer .= "  <td align=\"center\" {$table_params}>[DESC] <u><strong>Length (secs)</strong></u></td>\n";
      } else {
        $buffer .= "  <td align=\"center\" {$table_params}><strong>Length (secs)</strong></td>\n";
      }

      $buffer .= "</tr>\n";

      switch($sortWhat) {
        // Sort by song title (using $sortDirection) -- this is the default sorting method
        case "songTitle": {
          $songTitles = $this -> m3uFile_SongTitles;
          natcasesort($songTitles);

          if($sortDirection == "desc")
            $songTitles = array_reverse($songTitles);

          foreach($songTitles as $key => $title) {
            $title    = trim($title);
            $location = trim($this -> m3uFile_SongLocations[$key]);
            $length   = trim($this -> m3uFile_SongLengths[$key]);

            $buffer .= "<tr>\n";
            $buffer .= "  <td {$table_params}>{$title}</td>\n";
            $buffer .= "  <td {$table_params}>{$location}</td>\n";
            $buffer .= "  <td {$table_params}>{$length}</td>\n";
            $buffer .= "</tr>\n";
          }
        } break;

        // Sort by song location (using $sortDirection)
        case "songLocation": {
          $songLocations = $this -> m3uFile_SongLocations;
          natcasesort($songLocations);

          if($sortDirection == "desc")
            $songLocations = array_reverse($songLocations);

          foreach($songLocations as $key => $location) {
            $title    = trim($this -> m3uFile_SongTitles[$key]);
            $location = trim($location);
            $length   = trim($this -> m3uFile_SongLengths[$key]);

            $buffer .= "<tr>\n";
            $buffer .= "  <td {$table_params}>{$title}</td>\n";
            $buffer .= "  <td {$table_params}>{$location}</td>\n";
            $buffer .= "  <td {$table_params}>{$length}</td>\n";
            $buffer .= "</tr>\n";
          }
        } break;

        // Sort by song length (using $sortDirection)
        case "songLength": {
          $songLengths = $this -> m3uFile_SongLengths;
          natsort($songLengths);

          if($sortDirection == "desc")
            $songLengths = array_reverse($songLengths);

          foreach($songLengths as $key => $length) {
            $title    = trim($this -> m3uFile_SongTitles[$key]);
            $location = trim($this -> m3uFile_SongLocations[$key]);
            $length   = trim($length);

            $buffer .= "<tr>\n";
            $buffer .= "  <td {$table_params}>{$title}</td>\n";
            $buffer .= "  <td {$table_params}>{$location}</td>\n";
            $buffer .= "  <td {$table_params}>{$length}</td>\n";
            $buffer .= "</tr>\n";
          }
        } break;
      }

      // Vertical table Break
      $buffer .= "<tr>\n";
      $buffer .= "  <td colspan=\"3\" {$table_params}>&nbsp;</td>\n";
      $buffer .= "</tr>\n";

      // Stats
      $buffer .= "<tr>\n";
      $buffer .= "  <td colspan=\"3\" align=\"center\" {$table_params}>There are {$totalSongs} songs</td>\n";
      $buffer .= "</tr>\n";
      $buffer .= "<tr>\n";
      $buffer .= "  <td colspan=\"3\" align=\"center\" {$table_params}>Combined play time of {$totalPlayTime}.</td>\n";
      $buffer .= "</tr>\n";

      $buffer .= "</table>\n";
    } else {
      foreach($this -> m3uFile_SongTitles as $key => $title) {
        $location   = $this -> m3uFile_SongLocations[$key];
        $length     = $this -> m3uFile_SongLengths[$key];
        $buffer .= "Song Title: {$title} - Song Location: {$location} - Song Length: {$length} seconds\n<br />\n";
      }

      $buffer .= "There are a total of {$totalSongs} with a combined play time of {$totalPlayTime}.";
    }

    return $buffer;
  }

  /**
  * @desc Format a human-readable length time
  * @return string Returns a formatted, human-readable play time length
  */
  public function formatPlayTime($seconds) {
    $return = "";

    $hours = intval(intval($seconds) / 3600);
    if($hours > 0)
      $return .= "$hours hours, ";

    $minutes = (intval($seconds) / 60) % 60;
    if($hours > 0 || $minutes > 0)
      $return .= "$minutes minutes, and ";

    $seconds = intval($seconds) % 60;
    $return .= "$seconds seconds";

    return $return;
  }

  /**
  * @desc Prints each array (Song Lengths, Song Titles, Song Locations)
  */
  public function debug() {
    echo "<pre>";
    print_r($this -> m3uFile_SongLengths);
    print_r($this -> m3uFile_SongTitles);
    print_r($this -> m3uFile_SongLocations);
    echo "</pre>";
  }
}

# Initiate the m3u parser class using "Skillet.m3u"
$m3uParser = new m3uParser("Skillet.m3u");

# Debug/print all records (Song lengths, Song Titles, Song Locations)
# Note: Each element in an array will be the same key across all three arrays
#$m3uParser -> debug();

# Output m3u information in a human-readable format
# Acceptable parameter for 1st parameter: "songTitle" "songLocation" and "songLength"
# Acceptable parameter for 2nd parameter: "asc" or "desc" - lowercase
echo $m3uParser -> prettyOutput("songLength", "desc", true, "100%", 5, 5, 1, "center", "style=\"border: 1px solid #000;border-collapse: collapse;\"");

# Search titles for (string)"STRING" or (array)array("string1", "string2", "string3", "etc")
#print_r($m3uParser -> searchTitles(array("s", "k", "omg")));

# Search locations for (string)"STRING" or (array)array("string1", "string2", "string3", "etc")
#print_r($m3uParser -> searchLocations(array("DEMO", "mu")));

# Search song lengths less than, less than or equal to, greater than, greater than or equal to, in between $start and $end
#print_r($m3uParser -> searchLengths(0, array(331, 293, 271)));

?>