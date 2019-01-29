<?php

require_once __DIR__ . '/../phplib/Core.php';

$sqlFile = Config::get('global.tempDir') . '/dex-database.sql';
$gzFile = Config::get('global.tempDir') . '/dex-database.sql.gz';
$licenseFile = Core::getRootPath() . '/tools/dumpDatabaseLicense.txt';

// Skip the username/password here to avoid a Percona warning.
// Place them in my.cnf.
$commonCommand = sprintf("mysqldump -h %s %s ", DB::$host, DB::$database);

// Tables we never want to export, whether in the public or full dump
const SKIP_TABLES = [
];

// Tables whose data is to be filtered and/or altered before dumping
const FILTER_TABLES = [
  'Definition',
  'Source',
  'User',
];

// Tables for which we only dump the schema in both the public and full dump
// (usually for sensitive information or huge volumes of data)
const SCHEMA_ONLY_TABLES = [
  'AccuracyProject',
  'AccuracyRecord',
  'Cookie',
  'DefinitionVersion',
  'Donation',
  'PasswordToken',
  'RecentLink',
  'UserWordBookmark',
];

// Tables to be included in the full dump, but schema only in the public dump
const PRIVATE_TABLES = [
  'AdsClick',
  'DefinitionSimple',
  'OCR',
  'OCRLot',
  'Typo',
];

// read command line arguments
$opts = getopt('', ['full']);
$doFullDump = isset($opts['full']);

Log::notice('started with argument %s', ($doFullDump ? 'full' : 'public'));

$currentYear = date("Y");
$license = file_get_contents($licenseFile);
$license = sprintf($license, $currentYear);
file_put_contents($sqlFile, $license);

// dump tables with data
$ignoredTables = $doFullDump
               ? array_merge(SKIP_TABLES, SCHEMA_ONLY_TABLES)
               : array_merge(SKIP_TABLES, FILTER_TABLES, SCHEMA_ONLY_TABLES, PRIVATE_TABLES);
$ignoreString = implode(' ', array_map(function($table) {
  return sprintf('--ignore-table=%s.%s', DB::$database, $table);
}, $ignoredTables));

OS::executeAndAssert("$commonCommand $ignoreString >> $sqlFile");

// dump tables with no data (schema only)
$schemaTables = $doFullDump
              ? SCHEMA_ONLY_TABLES
              : array_merge(SCHEMA_ONLY_TABLES, PRIVATE_TABLES);
$command = sprintf('%s --no-data %s >> %s',
                   $commonCommand,
                   implode(' ', $schemaTables),
                   $sqlFile);
OS::executeAndAssert($command);

if (!$doFullDump) {
  // Anonymize the User table. Handle the case for id = 0 separately, since
  // "insert into _User_Copy set id = 0" doesn't work (it inserts an id of 1).
  Log::info('Anonymizing the User table');
  DB::execute("drop table if exists _User_Copy");
  DB::execute("create table _User_Copy like User");
  DB::execute("insert into _User_Copy select * from User where id = 0");
  DB::execute("update _User_Copy set id = 0 where id = 1");
  DB::execute("insert into _User_Copy select * from User where id > 0");
  DB::execute("update _User_Copy set password = md5('1234'), email = concat(id, '@anonymous.com')");
  OS::executeAndAssert(
    "$commonCommand _User_Copy | sed 's/_User_Copy/User/g' >> $sqlFile");
  DB::execute("drop table _User_Copy");

  // Hide links to scanned pages from the Source table
  Log::info('Anonymizing the Source table');
  DB::execute("drop table if exists _Source_Copy");
  DB::execute("create table _Source_Copy like Source");
  DB::execute("insert into _Source_Copy select * from Source");
  DB::execute("update _Source_Copy set link = null");
  OS::executeAndAssert(
    "$commonCommand _Source_Copy | sed 's/_Source_Copy/Source/g' >> $sqlFile");
  DB::execute("drop table _Source_Copy");

  // Dump only the Definitions for which we have redistribution rights
  Log::info('Filtering the Definition table');
  DB::execute("drop table if exists _Definition_Copy");
  DB::execute("create table _Definition_Copy like Definition");
  DB::execute("insert into _Definition_Copy select * from Definition");
  $query = <<<EOT
    update _Definition_Copy
    set internalRep = concat(left(internalRep, 20), '...')
    where sourceId in (select id from Source where !canDistribute)
EOT;
  DB::execute($query);
  OS::executeAndAssert(
    "$commonCommand _Definition_Copy | sed 's/_Definition_Copy/Definition/g' >> $sqlFile");
  DB::execute("drop table _Definition_Copy");
}

$remoteFile = $doFullDump
  ? '/download/mirrorAccess/dex-database.sql.gz'
  :'/download/dex-database.sql.gz';

OS::executeAndAssert("gzip -f $sqlFile");
$f = new FtpUtil();
$f->staticServerPut($gzFile, $remoteFile);
unlink($gzFile);

Log::notice('finished');
