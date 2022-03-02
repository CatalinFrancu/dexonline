<?php

require_once __DIR__ . '/../lib/Core.php';

// Make sure we are in test mode.
Config::TEST_MODE
  or die("Please set TEST_MODE = true in Config.php.\n");

// Make sure we are in development mode. We need fake logins.
Config::DEVELOPMENT_MODE
  or die("Please set DEVELOPMENT_MODE = true in Config.php.\n");

// Drop and recreate the test DB.
// Execute this at PDO level, since idiorm cannot connect to a non-existing DB.
$gdsn = DB::splitDsn(Config::DATABASE);
$tdsn = DB::splitDsn(Config::TEST_DATABASE);

$pdo = new PDO('mysql:host=' . $tdsn['host'], $tdsn['user'], $tdsn['password']);
$pdo->query('drop database if exists ' . $tdsn['database']);
$pdo->query('create database if not exists ' . $tdsn['database']);

// Warning about passwords on command line.
if ($gdsn['password'] || $tdsn['password']) {
  print "This script needs to run some mysqldump and mysql shell commands.\n";
  print "However, your DB DSN includes a password. We cannot add plaintext passwords\n";
  print "to MySQL commands. Please specify your username/password in ~/.my.cnf like so:\n";
  print "\n";
  print "[client]\n";
  print "user=your_username\n";
  print "password=your_password\n";
}

// Copy the schema from the regular DB.
// Use sed to remove AUTO_INCREMENT values - we want to start at 1.
$command = sprintf('mysqldump -h %s -u %s %s -d ' .
                   '| sed -e "s/AUTO_INCREMENT=[[:digit:]]* //" ' .
                   '| mysql -h %s -u %s %s',
                   $gdsn['host'], $gdsn['user'], $gdsn['database'],
                   $tdsn['host'], $tdsn['user'], $tdsn['database']);
exec($command);

// Create some data.

// users
$john = Model::factory('User')->create();
$john->email = 'john@x.com';
$john->nick = 'john';
$john->name = 'John Smith';
$john->save();

// sources
$klingon = Model::factory('Source')->create();
$klingon->shortName = 'Source 1';
$klingon->urlName = 'source1';
$klingon->name = 'English - Klingon Dictionary';
$klingon->author = 'Worf';
$klingon->publisher = 'The Klingon Academy';
$klingon->year = '2010';
$klingon->normative = true;
$klingon->displayOrder = 1;
$klingon->save();

$devil = Model::factory('Source')->create();
$devil->shortName = 'Source 2';
$devil->urlName = 'source2';
$devil->name = "The Devil's Dictionary";
$devil->author = 'Ambrose Bierce';
$devil->publisher = 'Neale Publishing Co.';
$devil->year = '1911';
$devil->normative = false;
$devil->displayOrder = 2;
$devil->save();

// model types
createModelType('T', 'T', 'temporar');
createModelType('F', 'F', 'substantiv feminin');
createModelType('AF', 'F', 'adjectiv feminin');
createModelType('N', 'N', 'substantiv neutru');

// inflections
createInflections('T', [
  'formă unică',
]);
$descriptions = [
  'nominativ, singular, nearticulat',
  'genitiv, singular, nearticulat',
  'nominativ, plural, nearticulat',
  'genitiv, plural, nearticulat',
  'nominativ, singular, articulat',
  'genitiv, singular, articulat',
  'nominativ, plural, articulat',
  'genitiv, plural, articulat',
  'vocativ, singular',
  'vocativ, plural',
]; // reuse these
createInflections('F', $descriptions);
createInflections('N', $descriptions);

// models, transforms and model descriptions
createModelDeep('T', '1', '', 'invariabil', [
  [ 'invariabil' ],
]);
createModelDeep('F', '35', '+et', "br'ânză", [
  [ "br'ânză" ],
  [ "br'ânze" ],
  [ "brânz'eturi" ],
  [ "brânz'eturi" ],
  [ "br'ânza" ],
  [ "br'ânzei" ],
  [ "brânz'eturile" ],
  [ "brânz'eturilor" ],
  [ "br'ânză", "br'ânzo" ],
  [ "brânz'eturilor" ],
]);
createModelDeep('F', '62', '', "str'adă", [
  [ "str'adă" ],
  [ "str'ăzi" ],
  [ "str'ăzi" ],
  [ "str'ăzi" ],
  [ "str'ada" ],
  [ "str'ăzii" ],
  [ "str'ăzile" ],
  [ "str'ăzilor" ],
  [ "str'adă", "str'ado" ],
  [ "str'ăzilor" ],
]);
createModelDeep('N', '1', '', "f'ir", [
  [ "f'ir" ],
  [ "f'ir" ],
  [ "f'ire" ],
  [ "f'ire" ],
  [ "f'irul" ],
  [ "f'irului" ],
  [ "f'irele" ],
  [ "f'irelor" ],
  [ "f'irule", "f'ire" ],
  [ "f'irelor" ],
]);
createModelDeep('F', '107', '', "spați'ere", [
  [ "spați'ere" ],
  [ "spați'eri" ],
  [ "spați'eri" ],
  [ "spați'eri" ],
  [ "spați'erea" ],
  [ "spați'erii" ],
  [ "spați'erile" ],
  [ "spați'erilor" ],
]);

// inflection constraints
createConstraints('S', '%plural%', '%', -1);
createConstraints('W', '%vocativ, singular%', 'F', 1);
createConstraints('w', '%vocativ, singular%', 'F', 0);

// lexemes
$l1 = createLexemeDeep("br'ânză", 'F', '35', '');
$l2 = createLexemeDeep("c'adă", 'F', '62', '');
$l3 = createLexemeDeep("met'al", 'N', '1', '');
$l4 = createLexemeDeep("d'in", 'T', '1', '');
$l5 = createLexemeDeep("d'in", 'N', '1', ''); // fictitious
$l6 = createLexemeDeep("l'adă", 'F', '62', 'S');
$l7 = createLexemeDeep("ogr'adă", 'F', '62', 'W');
// for testing whitespace preservation when minifying
$l8 = createLexemeDeep("spați'ere", 'F', '107', '');
$l1->frequency = 0.95; // for the Hangman game
$l1->save();

// definitions
$d1 = createDefinition(
  'Produs alimentar{{Foarte foarte gustos/1}} obținut prin coagularea și prelucrarea laptelui.',
  'brânză', $john->id, $klingon->id, Definition::ST_ACTIVE);
$d2 = createDefinition(
  'Recipient mare, deschis, din lemn, din metal, din beton etc.',
  'cadă', $john->id, $klingon->id, Definition::ST_ACTIVE);
$d3 = createDefinition(
  'prepoziție etc.',
  'din', $john->id, $klingon->id, Definition::ST_ACTIVE);
$d4 = createDefinition(
  'O dină, două dine, definiție fictivă pentru a avea lexeme omonime.',
  'din', $john->id, $klingon->id, Definition::ST_ACTIVE);
$d5 = createDefinition(
  'Definiție
        pe mai multe
linii    pentru a    testa spațierea.',
  'spațiere', $john->id, $klingon->id, Definition::ST_ACTIVE);

// lexeme-definition maps
EntryDefinition::associate($l1->getEntries()[0]->id, $d1->id);
EntryDefinition::associate($l2->getEntries()[0]->id, $d2->id);
EntryDefinition::associate($l4->getEntries()[0]->id, $d3->id);
EntryDefinition::associate($l5->getEntries()[0]->id, $d4->id);
EntryDefinition::associate($l8->getEntries()[0]->id, $d5->id);

// lexeme sources
$ls = Model::factory('LexemeSource')->create();
$ls->lexemeId = $l3->id;
$ls->sourceId = $devil->id;
$ls->save();

// AdsLink
$al = Model::factory('AdsLink')->create();
$al->skey = 'wikipedia';
$al->name = 'wikipedia';
$al->url= 'http://wikipedia.org';
$al->save();

// WotD artists
$artist1 = createWotdArtist('artist1', 'Geniu Neînțeles', 'geniu@example.com', '© Geniu Neînțeles');
$artist2 = createWotdArtist('artist2', 'Luceafărul grafittiului românesc', 'luceafar@example.com', '© Luceafărul');

// Wiki articles, sections and keywords
$article1 = createWikiArticle(17, 123, 'Niciun sau nici un', 'Conținutul articolului 1.', 'Exprimare corectă');
$article2 = createWikiArticle(27, 345, 'Ghid de exprimare', 'Conținutul articolului 2.', 'Exprimare corectă');
$article3 = createWikiArticle(37, 567, 'Articol fără secțiune', 'Conținutul articolului 3.', '');
createWikiKeyword($article1->id, 'metal');
createWikiKeyword($article2->id, 'metal');
createWikiKeyword($article1->id, 'din');

// Tags
$tag1 = createTag('expresie', 0);
$tag2 = createTag('registru stilistic', 0);
$tag21 = createTag('argou', $tag2->id);
$tag22 = createTag('familiar', $tag2->id);

// run some preprocessing
require_once __DIR__ . '/../tools/rebuildFullTextIndex.php';

/**************************************************************************/

function createModelType($code, $canonical, $description) {
  $mt = Model::factory('ModelType')->create();
  $mt->code = $code;
  $mt->description = $description;
  $mt->canonical = $canonical;
  $mt->save();
}

function createInflections($modelType, $descriptions) {
  foreach ($descriptions as $i => $d) {
    $infl = Model::factory('Inflection')->create();
    $infl->description = $d;
    $infl->modelType = $modelType;
    $infl->rank = $i + 1;
    $infl->save();
  }
}

function createModelDeep($type, $number, $description, $exponent, $paradigm) {
  $m = Model::factory('FlexModel')->create();
  $m->modelType = $type;
  $m->number = $number;
  $m->description = $description;
  $m->exponent = $exponent;
  $m->save();

  foreach ($paradigm as $i => $forms) {
    $infl = Inflection::get_by_modelType_rank($type, $i + 1);

    foreach ($forms as $variant => $form) {
      $transforms = FlexStr::extractTransforms($m->exponent, $form, false);

      $accentShift = array_pop($transforms);
      if ($accentShift != ModelDescription::UNKNOWN_ACCENT_SHIFT &&
          $accentShift != ModelDescription::NO_ACCENT_SHIFT) {
        $accentedVowel = array_pop($transforms);
      } else {
        $accentedVowel = '';
      }

      $order = count($transforms);
      foreach ($transforms as $t) {
        $t = Transform::createOrLoad($t->transfFrom, $t->transfTo);
        $md = Model::factory('ModelDescription')->create();
        $md->modelId = $m->id;
        $md->inflectionId = $infl->id;
        $md->variant = $variant;
        $md->applOrder = --$order;
        $md->transformId = $t->id;
        $md->accentShift = $accentShift;
        $md->vowel = $accentedVowel;
        $md->recommended = true;
        $md->save();
      }
    }
  }
}

function createConstraints($code, $inflectionRegexp, $modelTypeRegexp, $variant) {
  $inflections = Model::factory('Inflection')
               ->where_like('description', $inflectionRegexp)
               ->where_like('modelType', $modelTypeRegexp)
               ->find_many();
  foreach ($inflections as $i) {
    $c = Model::factory('ConstraintMap')->create();
    $c->code = $code;
    $c->inflectionId = $i->id;
    $c->variant = $variant;
    $c->save();
  }
}

function createLexemeDeep($form, $modelType, $modelNumber, $restriction) {
  $l = Lexeme::create($form, $modelType, $modelNumber, $restriction);
  $l->deepSave();
  $e = Entry::createAndSave($l->formNoAccent);
  EntryLexeme::associate($e->id, $l->id);

  // reload to flush the $entryLexemes field
  return Lexeme::get_by_id($l->id);
}

function createDefinition($rep, $lexicon, $userId, $sourceId, $status) {
  $d = Model::factory('Definition')->create();
  $d->userId = $userId;
  $d->sourceId = $sourceId;
  $d->internalRep = $rep;
  $d->status = $status;
  $d->process();
  $d->lexicon = $lexicon; // overwrite extracted lexicon
  $d->save();

  return $d;
}

function createWotdArtist($label, $name, $email, $credits) {
  $a = Model::factory('WotdArtist')->create();
  $a->label = $label;
  $a->name = $name;
  $a->email = $email;
  $a->credits = $credits;
  $a->save();
  return $a;
}

function createWikiArticle($pageId, $revId, $title, $body, $section) {
  $a = Model::factory('WikiArticle')->create();
  $a->pageId = $pageId;
  $a->revId = $revId;
  $a->title = $title;
  $a->section = $section;
  $a->fullUrl = '';
  $a->wikiContents = $body;
  $a->htmlContents = $body;
  $a->save();

  return $a;
}

function createWikiKeyword($wikiArticleId, $keyword) {
  $wk = Model::factory('WikiKeyword')->create();
  $wk->wikiArticleId = $wikiArticleId;
  $wk->keyword = $keyword;
  $wk->save();
}

function createTag($value, $parentId) {
  $t = Model::factory('Tag')->create();
  $t->value = $value;
  $t->parentId = $parentId;
  $t->save();
  return $t;
}
