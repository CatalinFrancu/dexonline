<?php
require_once '../../lib/Core.php';
User::mustHave(User::PRIV_EDIT | User::PRIV_STRUCT);

// Lexeme parameters
$lexemeId = Request::get('lexemeId');
$lexemeForm = Request::getWithApostrophes('lexemeForm');
$lexemeNumber = Request::get('lexemeNumber');
$lexemeDescription = Request::get('lexemeDescription');
$needsAccent = Request::has('needsAccent');
$stopWord = Request::has('stopWord');
$hyphenations = Request::get('hyphenations');
$pronunciations = Request::get('pronunciations');
$entryIds = Request::getArray('entryIds');
$tagIds = Request::getArray('tagIds');
$renameRelated = Request::has('renameRelated');

// Paradigm parameters
$compound = Request::has('compound');
$sourceIds = Request::getArray('sourceIds');
$notes = Request::get('notes');
$hasApheresis = Request::has('hasApheresis');
$hasApocope = Request::has('hasApocope');

// Simple lexeme parameters
$modelType = Request::get('modelType');
$modelNumber = Request::get('modelNumber');
$restriction = Request::get('restriction');

// Compound lexeme parameters
$compoundModelType = Request::get('compoundModelType');
$compoundRestriction = Request::get('compoundRestriction');
$partIds = Request::getArray('partIds');
$declensions = Request::getArray('declensions');
$capitalized = Request::get('capitalized');
$accented = Request::get('accented');

// Button parameters
$refreshButton = Request::has('refreshButton');
$saveButton = Request::has('saveButton');
$cloneButton = Request::has('cloneButton');
$deleteButton = Request::has('deleteButton');

$lexeme = Lexeme::get_by_id($lexemeId);
$original = Lexeme::get_by_id($lexemeId); // Keep a copy so we can test whether certain fields have changed

if ($cloneButton) {
  $newLexeme = $lexeme->_clone();
  Log::notice("Cloned lexeme {$lexeme->id} ({$lexeme->formNoAccent}), new id is {$newLexeme->id}");
  Util::redirect("lexemeEdit.php?lexemeId={$newLexeme->id}");
}

if ($deleteButton) {
  $homonym = Model::factory('Lexeme')
           ->where('formNoAccent', $lexeme->formNoAccent)
           ->where_not_equal('id', $lexeme->id)
           ->find_one();
  $lexeme->delete();
  if ($homonym) {
    FlashMessage::add('Am șters lexemul și v-am redirectat la unul dintre omonime.', 'success');
    Util::redirect("?lexemeId={$homonym->id}");
  } else {
    FlashMessage::add('Am șters lexemul.', 'success');
    Util::redirect('index.php');
  }
}

if ($refreshButton || $saveButton) {
  populate($lexeme, $original, $lexemeForm, $lexemeNumber, $lexemeDescription,
           $needsAccent, $stopWord, $hyphenations, $pronunciations,
           $compound, $modelType, $modelNumber, $restriction, $compoundModelType,
           $compoundRestriction, $partIds, $declensions, $capitalized, $accented,
           $notes, $hasApheresis, $hasApocope, $tagIds);

  if (validate($lexeme, $original)) {
    // Case 1: Validation passed
    if ($saveButton) {
      if (($original->modelType == 'VT') && ($lexeme->modelType != 'VT')) {
        $original->deleteParticiple();
      }
      if (in_array($original->modelType, ['V', 'VT']) &&
          !in_array($lexeme->modelType, ['V', 'VT'])) {
        $original->deleteLongInfinitive();
      }
      $lexeme->deepSave();
      $lexeme->regenerateDependentLexemes();
      $lexeme->harmonizeTags();
      LexemeSource::update($lexeme->id, $sourceIds);
      EntryLexeme::update($entryIds, $lexeme->id);

      if ($renameRelated) {
        // Grab all the entries
        foreach ($lexeme->getEntries() as $e) {
          if ($e->description == $original->formNoAccent) {
            FlashMessage::addTemplate('entryRenamed.tpl', [
              'entry' => $e,
              'newDescription' => $lexeme->formNoAccent,
            ], 'warning');
            $e->description = $lexeme->formNoAccent;
            $e->save();
          }
          foreach ($e->getTrees() as $t) {
            if ($t->description == $original->formNoAccent) {
              FlashMessage::addTemplate('treeRenamed.tpl', [
                't' => $t,
                'newDescription' => $lexeme->formNoAccent,
              ], 'warning');
              $t->description = $lexeme->formNoAccent;
              $t->save();
            }
          }
        }
      }

      Log::notice("Saved lexeme {$lexeme->id} ({$lexeme->formNoAccent})");
      Util::redirect("lexemeEdit.php?lexemeId={$lexeme->id}");
    }
  } else {
    // Case 2: Validation failed
  }

  // Case 1-2: Page was submitted
  Smart::assign('renameRelated', $renameRelated);

} else {
  // Case 3: First time loading this page
  $lexeme->loadInflectedFormMap();
  $sourceIds = $lexeme->getSourceIds();
  $entryIds = $lexeme->getEntryIds();

  RecentLink::add("Lexem: $lexeme (ID={$lexeme->id})");
}

$definitions = Definition::loadByEntryIds($entryIds);
$searchResults = SearchResult::mapDefinitionArray($definitions);

$canEdit = [
  'general' => User::can(User::PRIV_EDIT),
  'description' => User::can(User::PRIV_EDIT),
  'form' => User::can(User::PRIV_EDIT),
  'hyphenations' => User::can(User::PRIV_STRUCT | User::PRIV_EDIT),
  'paradigm' => User::can(User::PRIV_EDIT),
  'pronunciations' => User::can(User::PRIV_STRUCT | User::PRIV_EDIT),
  'sources' => User::can(User::PRIV_EDIT),
  'stopWord' => User::can(User::PRIV_ADMIN),
  'tags' => User::can(User::PRIV_EDIT),
];

// Prepare a list of model numbers, to be used in the paradigm drop-down.
$models = FlexModel::loadByType($lexeme->modelType);

$homonyms = Model::factory('Lexeme')
          ->where('formNoAccent', $lexeme->formNoAccent)
          ->where_not_equal('id', $lexeme->id)
          ->find_many();

Smart::assign([
  'lexeme' => $lexeme,
  'entryIds' => $entryIds,
  'sourceIds' => $sourceIds,
  'homonyms' => $homonyms,
  'searchResults' => $searchResults,
  'modelTypes' => ModelType::getAll(),
  'models' => $models,
  'canEdit' => $canEdit,
]);
Smart::addCss('paradigm', 'admin');
Smart::addJs('select2Dev', 'modelDropdown', 'cookie', 'frequentObjects');
Smart::display('admin/lexemeEdit.tpl');

/**************************************************************************/

// Populate lexeme fields from request parameters.
function populate(&$lexeme, &$original, $lexemeForm, $lexemeNumber, $lexemeDescription,
                  $needsAccent, $stopWord, $hyphenations, $pronunciations,
                  $compound, $modelType, $modelNumber, $restriction, $compoundModelType,
                  $compoundRestriction, $partIds, $declensions, $capitalized, $accented,
                  $notes, $hasApheresis, $hasApocope, $tagIds) {
  $lexeme->setForm($lexemeForm);
  $lexeme->number = $lexemeNumber;
  $lexeme->description = $lexemeDescription;
  $lexeme->noAccent = !$needsAccent;
  $lexeme->stopWord = $stopWord;
  $lexeme->hyphenations = $hyphenations;
  $lexeme->pronunciations = $pronunciations;

  $lexeme->compound = $compound;
  $lexeme->notes = $notes;
  $lexeme->hasApheresis = $hasApheresis;
  $lexeme->hasApocope = $hasApocope;

  if ($compound) {
    $lexeme->modelType = $compoundModelType;
    $lexeme->modelNumber = 0;
    $lexeme->restriction = $compoundRestriction;
    // create Fragments
    $fragments = [];
    foreach ($partIds as $i => $partId) {
      $fragments[] = Fragment::create(
        $partId, $declensions[$i], $capitalized[$i], $accented[$i], $i);
    }
    $lexeme->setFragments($fragments);
  } else {
    $lexeme->modelType = $modelType;
    $lexeme->modelNumber = $modelNumber;
    $lexeme->restriction = $restriction;
    $lexeme->harmonizeModel($tagIds);
  }

  // create ObjectTags
  $objectTags = [];
  foreach ($tagIds as $tagId) {
    $ot = Model::factory('ObjectTag')->create();
    $ot->objectType = ObjectTag::TYPE_LEXEME;
    $ot->tagId = $tagId;
    $objectTags[] = $ot;
  }
  $lexeme->setObjectTags($objectTags);

  try {
    $lexeme->generateInflectedFormMap();
  } catch (ParadigmException $pe) {
    FlashMessage::add($pe->getMessage());
  }
}

function validate($lexeme, $original) {
  if (!$lexeme->form) {
    FlashMessage::add('Forma nu poate fi vidă.');
  }

  $numAccents = preg_match_all("/(?<!\\\\)'/", $lexeme->form);
  // Note: we allow multiple accents for lexemes like hárcea-párcea
  if ($numAccents && $lexeme->noAccent) {
    FlashMessage::add('Ați indicat că lexemul nu necesită accent, dar forma conține un accent.');
  } else if (!$numAccents && !$lexeme->noAccent) {
    FlashMessage::add('Adăugați un accent sau debifați câmpul „Necesită accent”.');
  }

  // Gather all different restriction - model type pairs
  $pairs = Model::factory('ConstraintMap')
         ->table_alias('cm')
         ->select_expr('binary cm.code', 'restr')
         ->select('mt.code', 'modelType')
         ->distinct()
         ->join('Inflection', ['cm.inflectionId', '=', 'i.id'], 'i')
         ->join('ModelType', ['i.modelType', '=', 'mt.canonical'], 'mt')
         ->find_array();
  $restrMap = [];
  foreach ($pairs as $p) {
    $restrMap[$p['restr']][$p['modelType']] = true;
  }

  for ($i = 0; $i < mb_strlen($lexeme->restriction); $i++) {
    $c = Str::getCharAt($lexeme->restriction, $i);
    if (!isset($restrMap[$c])) {
      FlashMessage::add("Restricția <strong>$c</strong> este nedefinită.");
    } else if (!isset($restrMap[$c][$lexeme->modelType])) {
      FlashMessage::add("Restricția <strong>$c</strong> nu se aplică modelului <strong>{$lexeme->modelType}.</strong>");
    }
  }

  try {
    $ifs = $lexeme->generateInflectedForms();
  } catch (ParadigmException $pe) {
    FlashMessage::add($pe->getMessage());
  }

  return !FlashMessage::hasErrors();
}
