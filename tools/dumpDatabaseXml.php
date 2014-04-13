<?php

require_once __DIR__ . '/../phplib/util.php';

$TODAY = date("Y-m-d");
$TODAY_TIMESTAMP = strtotime("$TODAY 00:00:00");
$REMOTE_FOLDER = 'download/xmldump';
$STATIC_FILES = file(Config::get('static.url') . 'fileList.txt');
$LAST_DUMP = getLastDumpDate($REMOTE_FOLDER);
$LAST_DUMP_TIMESTAMP = $LAST_DUMP ? strtotime("$LAST_DUMP 00:00:00") : null;
$USERS = getActiveUsers();

log_scriptLog("generating dump for $TODAY; previous dump is " . ($LAST_DUMP ? $LAST_DUMP : '-never-'));

if ($LAST_DUMP == $TODAY) {
  log_scriptLog("a dump already exists for $TODAY; aborting");
  die("a dump already exists for $TODAY; aborting\n");
}

dumpSources("$REMOTE_FOLDER/$TODAY-sources.xml.gz");
dumpInflections("$REMOTE_FOLDER/$TODAY-inflections.xml.gz");
dumpAbbrevs("$REMOTE_FOLDER/$TODAY-abbrevs.xml.gz");
dumpDefinitions("SELECT * FROM Definition WHERE sourceId IN (SELECT id FROM Source WHERE canDistribute) AND status = 0 AND modDate < $TODAY_TIMESTAMP",
                "$REMOTE_FOLDER/$TODAY-definitions.xml.gz",
                'dumping definitions');
dumpLexems("SELECT * FROM Lexem where modDate < $TODAY_TIMESTAMP",
           "$REMOTE_FOLDER/$TODAY-lexems.xml.gz",
           'dumping lexems and inflected forms');
dumpLdm("SELECT M.lexemId, M.definitionId FROM LexemDefinitionMap M, Definition D " .
        "WHERE D.id = M.definitionId AND D.sourceId in (SELECT id FROM Source WHERE canDistribute) " .
        "AND D.status = 0 AND M.modDate < $TODAY_TIMESTAMP ORDER BY M.lexemId, M.definitionId",
        "$REMOTE_FOLDER/$TODAY-ldm.xml.gz",
        'dumping lexem-definition map');

if ($LAST_DUMP) {
  dumpDefinitions("SELECT * FROM Definition WHERE sourceId IN (SELECT id FROM Source WHERE canDistribute) " .
                  "AND modDate >= $LAST_DUMP_TIMESTAMP AND modDate < $TODAY_TIMESTAMP",
                  "$REMOTE_FOLDER/$TODAY-definitions-diff.xml.gz",
                  'dumping definitions diff');

  dumpLexems("SELECT * FROM Lexem where modDate >= $LAST_DUMP_TIMESTAMP AND modDate < $TODAY_TIMESTAMP",
             "$REMOTE_FOLDER/$TODAY-lexems-diff.xml.gz",
             'dumping lexems and inflected forms diff');

  dumpLdmDiff("$REMOTE_FOLDER/$LAST_DUMP-ldm.xml.gz", "$REMOTE_FOLDER/$TODAY-ldm.xml.gz", "$REMOTE_FOLDER/$TODAY-ldm-diff.xml.gz");
}

removeOldDumps($REMOTE_FOLDER, $TODAY, $LAST_DUMP);

/**************************************************************************/

function getActiveUsers() {
  $results = db_execute("SELECT id, nick FROM User WHERE id IN (SELECT DISTINCT userId FROM Definition)");
  $users = array();
  foreach ($results as $row) {
    $users[$row[0]] = $row[1];
  }
  return $users;
}

function getLastDumpDate($folder) {
  global $STATIC_FILES;

  // Group existing files by date, excluding the diff files
  $map = array();
  foreach ($STATIC_FILES as $file) {
    $matches = array();
    if (preg_match(":^{$folder}/(\\d\\d\\d\\d-\\d\\d-\\d\\d)-[a-z]+.xml.gz:", $file, $matches)) {
      if (array_key_exists($matches[1], $map)) {
        $map[$matches[1]]++;
      } else {
        $map[$matches[1]] = 1;
      }
    }
  }

  // Now check if the most recent date has 6 dump files
  if (count($map)) {
    krsort($map);
    $date = key($map); // First key
    return ($map[$date] == 6) ? $date : null;
  } else {  
    return null;
  }
}

function dumpSources($remoteFile) {
  log_scriptLog("dumping sources");
  SmartyWrap::assign('sources', Model::factory('Source')->order_by_asc('id')->find_many());
  $xml = SmartyWrap::fetch('xmldump/sources.ihtml');
  FtpUtil::staticServerPutContents(gzencode($xml), $remoteFile);
}

function dumpInflections($remoteFile) {
  log_scriptLog("dumping inflections");
  SmartyWrap::assign('inflections', Model::factory('Inflection')->order_by_asc('id')->find_many());
  $xml = SmartyWrap::fetch('xmldump/inflections.ihtml');
  FtpUtil::staticServerPutContents(gzencode($xml), $remoteFile);
}

function dumpAbbrevs($remoteFile) {
  log_scriptLog("dumping abbreviations");
  $raw_abbrevs = AdminStringUtil::loadRawAbbreviations();
  $sources = array();
  $sections = array();
  foreach ($raw_abbrevs as $name => $raw_section) {
    if ($name == "sources") {
      // the index of sources
      foreach ($raw_section as $id => $source) {
        $sources[$id] = preg_split('/, */', $source);
      }
    } else {
      // a single source
      $section = array();
      foreach ($raw_section as $short => $long) {
        $abbrev_info = array('short' => $short, 'long' => $long, 'ambiguous' => false);
        if (substr($short, 0, 1) == "*") {
          $abbrev_info['short'] = substr($short, 1);
          $abbrev_info['ambiguous'] = true;
        }
        $section[] = $abbrev_info;
      }
      $sections[$name] = $section;
    }
  }
  SmartyWrap::assign('sources', $sources);
  SmartyWrap::assign('sections', $sections);
  $xml = SmartyWrap::fetch('xmldump/abbrev.ihtml');
  FtpUtil::staticServerPutContents(gzencode($xml), $remoteFile);
}

function dumpDefinitions($query, $remoteFile, $message) {
  global $USERS;

  log_scriptLog($message);
  $results = db_execute($query);
  $tmpFile = tempnam('/tmp', 'xmldump_');
  $file = gzopen($tmpFile, 'wb9');
  gzwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
  gzwrite($file, "<Definitions>\n");
  foreach ($results as $row) {
    $def = Model::factory('Definition')->create($row);
    $def->internalRep = AdminStringUtil::xmlizeRequired($def->internalRep);
    SmartyWrap::assign('def', $def);
    SmartyWrap::assign('nick', $USERS[$def->userId]);
    gzwrite($file, SmartyWrap::fetch('xmldump/definition.ihtml'));
  }
  gzwrite($file, "</Definitions>\n");
  gzclose($file);
  FtpUtil::staticServerPut($tmpFile, $remoteFile);
  unlink($tmpFile);
}

function dumpLexems($query, $remoteFile, $message) {
  log_scriptLog($message);
  $results = db_execute($query);
  $tmpFile = tempnam('/tmp', 'xmldump_');
  $file = gzopen($tmpFile, 'wb9');
  gzwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
  gzwrite($file, "<Lexems>\n");
  foreach($results as $row) {
    $lexem = Model::factory('Lexem')->create($row);
    SmartyWrap::assign('lexem', $lexem);
    SmartyWrap::assign('ifs', InflectedForm::loadByLexemId($lexem->id));
    gzwrite($file, SmartyWrap::fetch('xmldump/lexem.ihtml'));
  }
  gzwrite($file, "</Lexems>\n");
  gzclose($file);
  FtpUtil::staticServerPut($tmpFile, $remoteFile);
  unlink($tmpFile);
}

function dumpLdm($query, $remoteFile, $message) {
  log_scriptLog($message);
  $results = db_execute($query);
  $tmpFile = tempnam('/tmp', 'xmldump_');
  $file = gzopen($tmpFile, 'wb9');
  gzwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
  gzwrite($file, "<LexemDefinitionMap>\n");
  foreach ($results as $row) {
    gzwrite($file, "  <Map lexemId=\"{$row[0]}\" definitionId=\"{$row[1]}\"/>\n");
  }
  gzwrite($file, "</LexemDefinitionMap>\n");
  gzclose($file);
  FtpUtil::staticServerPut($tmpFile, $remoteFile);
  unlink($tmpFile);
}

function dumpLdmDiff($oldRemoteFile, $newRemoteFile, $diffRemoteFile) {
  log_scriptLog('dumping lexem-definition map diff');

  // Transfer the files locally
  $oldXml = wgetAndGunzip(Config::get('static.url') . '/' . $oldRemoteFile);
  $newXml = wgetAndGunzip(Config::get('static.url') . '/' . $newRemoteFile);
  $output = null;
  exec("diff $oldXml $newXml", $output, $ignored);
  $tmpFile = tempnam('/tmp', 'xmldump_');  
  $file = gzopen($tmpFile, 'wb9');
  gzwrite($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
  gzwrite($file, "<LexemDefinitionMap>\n");
  foreach ($output as $line) {
    if (substr($line, 0, 2) == '< ') {
      gzwrite($file, preg_replace('/<Map /', '<Unmap ', substr($line, 2)) . "\n"); // Deleted line
    } else if (substr($line, 0, 2) == '> ') {
      gzwrite($file, substr($line, 2) . "\n"); // Added line
    }
  }
  gzwrite($file, "</LexemDefinitionMap>\n");
  gzclose($file);
  FtpUtil::staticServerPut($tmpFile, $diffRemoteFile);
  unlink($tmpFile);
  unlink($oldXml);
  unlink($newXml);
}

// Returns a file name in /tmp pointing to the unzipped file
function wgetAndGunzip($url) {
  $tmpFile = tempnam('/tmp', 'xmldump_');
  OS::executeAndAssert("wget -q -O $tmpFile.gz $url");
  OS::executeAndAssert("gunzip -f $tmpFile.gz");
  return $tmpFile;
}

// Delete all dumps other than current one and previous one. Keep the diffs
function removeOldDumps($folder, $today, $lastDump) {
  global $STATIC_FILES;

  log_scriptLog('removing old dumps');
  foreach ($STATIC_FILES as $file) {
    $matches = array();
    $file = trim($file);
    if (preg_match(":^{$folder}/(\\d\\d\\d\\d-\\d\\d-\\d\\d)-(abbrevs|definitions|inflections|ldm|lexems|sources).xml.gz$:", $file, $matches)) {
      $date = $matches[1];
      if ($date != $today && $date != $lastDump) {
        log_scriptLog("  deleting $file");
        FtpUtil::staticServerDelete($file);
      }
    }
  }
}

?>
