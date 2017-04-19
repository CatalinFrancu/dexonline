<?php
require_once("../phplib/Core.php");
$list = Request::get('list');
$locVersions = Request::getCsv('locVersions');

switch ($list) {
  case 'base':
    $keyword = 'baza';
    $listType = 'forme de bază';
    break;
  case 'inflected':
    $keyword = 'flexiuni';
    $listType = 'forme flexionare';
    break;
  case 'reduced':
    $keyword = 'reduse';
    $listType = 'forme reduse';
    break;
  default:
    FlashMessage::add('Ați introdus o listă incorectă.');
    Util::redirect('scrabble');
}

$zipUrl = sprintf('%sdownload/scrabble/loc-dif-%s-%s-%s.zip',
                  Config::get('static.url'), $keyword, $locVersions[0], $locVersions[1]);
$zipFile = tempnam(Config::get('global.tempDir'), 'loc_') . '.zip';
$txtFile = tempnam(Config::get('global.tempDir'), 'loc_') . '.txt';
if (!@copy($zipUrl, $zipFile)) {
  FlashMessage::add('Ați introdus o listă incorectă.');
  Util::redirect('scrabble');
}
OS::executeAndAssert("unzip -p $zipFile > $txtFile");

$diff = array();
foreach (file($txtFile) as $line) {
  $line = trim($line);
  if ($line[0] == '<') {
    $diff[] = array('del', substr($line, 2));
  } else {
    $diff[] = array('ins', substr($line, 2));
  }
}

@unlink($zipFile);
@unlink($txtFile);

SmartyWrap::assign('keyword', $keyword);
SmartyWrap::assign('listType', $listType);
SmartyWrap::assign('locVersions', $locVersions);
SmartyWrap::assign('diff', $diff);
SmartyWrap::assign('zipUrl', $zipUrl);
SmartyWrap::display('scrabble-diferente-loc.tpl');

?>
