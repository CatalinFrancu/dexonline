<?php
require_once("../../phplib/Core.php");
User::require(User::PRIV_VISUAL);
Util::assertNotMirror();
RecentLink::add('Dicționarul vizual');

SmartyWrap::addCss('elfinder', 'jqueryui', 'admin');
SmartyWrap::addJs('elfinder', 'jqueryui');
SmartyWrap::display('admin/visual.tpl');
?>
