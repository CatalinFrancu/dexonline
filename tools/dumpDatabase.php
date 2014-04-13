<?php

require_once __DIR__ . '/../phplib/util.php';

$SQL_FILE = '/tmp/dex-database.sql';
$GZ_FILE = '/tmp/dex-database.sql.gz';
$LICENSE = util_getRootPath() . '/tools/dumpDatabaseLicense.txt';

$parts = db_splitDsn();
$COMMON_COMMAND = sprintf("mysqldump -h %s -u %s --password='%s' %s ", $parts['host'], $parts['user'], $parts['password'], $parts['database']);

$schemaOnly = array('RecentLink', 'Cookie', 'UserWordBookmark', 'DefinitionSimple', 'history_Comment', 'history_Definition');
$currentYear = date("Y");

// Full/Public dump: the public dump omits the user table, which contains emails and md5-ed passwords.
$doFullDump = false;

for ($i = 1; $i < count($argv); $i++) {
  $arg = $argv[$i];
  if ($arg == "--full") {
    $doFullDump = true;
  } else if ($arg == '--public') {
    $doFullDump = false;
  } else {
    OS::errorAndExit("Unknown flag: $arg");
  }
}

log_scriptLog('Running dumpDatabase.php with argument ' . ($doFullDump ? 'full' : 'public'));

$dbName = $parts['database'];
$tablesToIgnore = '';
foreach ($schemaOnly as $table) {
  $tablesToIgnore .= "--ignore-table=$dbName.$table ";
}
if ($doFullDump) {
  $remoteFile = '/download/mirrorAccess/dex-database.sql.gz';
} else {
  $remoteFile = '/download/dex-database.sql.gz';
  $tablesToIgnore .= "--ignore-table=$dbName.User --ignore-table=$dbName.Definition --ignore-table=$dbName.diverta_Book --ignore-table=$dbName.divertaIndex ";
}

OS::executeAndAssert("rm -f $SQL_FILE");
OS::executeAndAssert("echo \"-- Copyright (C) 2004-$currentYear DEX online (http://dexonline.ro)\" > $SQL_FILE");
OS::executeAndAssert("cat $LICENSE >> $SQL_FILE");
$mysql = "$COMMON_COMMAND $tablesToIgnore >> $SQL_FILE";
OS::executeAndAssert($mysql);

// Dump only the schema for some tables
$command = "$COMMON_COMMAND --no-data";
foreach ($schemaOnly as $table) {
  $command .= " $table";
}
$command .= " >> $SQL_FILE";
OS::executeAndAssert($command);

if (!$doFullDump) {
  // Anonymize the User table. Handle the case for id = 0 separately, since
  // "insert into _User_Copy set id = 0" doesn't work (it inserts an id of 1).
  log_scriptLog('Anonymizing the User table');
  db_execute("create table _User_Copy like User");
  db_execute("insert into _User_Copy select * from User where id = 0");
  db_execute("update _User_Copy set id = 0 where id = 1");
  db_execute("insert into _User_Copy select * from User where id > 0");
  db_execute("update _User_Copy set password = md5('1234'), email = concat(id, '@anonymous.com'), identity = null");
  OS::executeAndAssert("$COMMON_COMMAND _User_Copy | sed 's/_User_Copy/User/g' >> $SQL_FILE");
  db_execute("drop table _User_Copy");

  // Dump only the Definitions for which we have redistribution rights
  log_scriptLog('Filtering the Definition table');
  OS::executeAndAssert("$COMMON_COMMAND Definition --lock-all-tables --where='Definition.sourceId in (select id from Source where canDistribute)' " .
                      ">> $SQL_FILE");
}

OS::executeAndAssert("gzip -f $SQL_FILE");
FtpUtil::staticServerPut($GZ_FILE, $remoteFile);
unlink($GZ_FILE);

log_scriptLog('dumpDatabase.php completed successfully (against all odds)');

?>
