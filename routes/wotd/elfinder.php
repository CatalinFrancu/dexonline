<?php

require_once __DIR__ . '/../../lib/third-party/elfinder/autoload.php';

#$opts = ElfinderUtil::getOptions('img/wotd/', 'Imagini cuvântul zilei');
$opts = ElfinderUtil::getOptionsMultiRoot();

// run elFinder
$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
