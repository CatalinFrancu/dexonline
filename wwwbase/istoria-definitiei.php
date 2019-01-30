<?php
require_once '../phplib/Core.php';

User::mustHave(User::PRIV_EDIT);

$id = Request::get('id');
$def = Definition::get_by_id($id);

if (!$def) {
  FlashMessage::add("Nu există nicio definiție cu ID-ul {$id}.");
  Util::redirect("index.php");
}

$prev = null;
$changeSets = [];

$dvs = Model::factory('DefinitionVersion')
     ->where('definitionId', $id)
     ->order_by_asc('id')
     ->find_many();

foreach ($dvs as $dv) {
  if ($prev) {
    $changeSets[] = DefinitionVersion::compare($prev, $dv, $changeSets);
  }
  $prev = $dv;
}

// And once more for the current version
if ($prev) {
  $current = DefinitionVersion::current($def);
  $changeSets[] = DefinitionVersion::compare($prev, $current, $changeSets);
}

$changeSets = array_filter($changeSets); // remove NOPs
$changeSets = array_reverse($changeSets); // newest changes first

SmartyWrap::assign('def', $def);
SmartyWrap::assign('changeSets', $changeSets);
SmartyWrap::addCss('diff');
SmartyWrap::display('istoria-definitiei.tpl');
