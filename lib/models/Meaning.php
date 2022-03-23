<?php

class Meaning extends BaseObject implements DatedObject {
  public static $_table = 'Meaning';

  const TYPE_MEANING = 0;
  const TYPE_ETYMOLOGY = 1;
  const TYPE_EXAMPLE = 2;
  const TYPE_COMMENT = 3;
  const TYPE_DIFF = 4;
  const TYPE_EXPRESSION = 5;

  const DISPLAY_NAMES = [
    self::TYPE_MEANING => '',
    self::TYPE_ETYMOLOGY => 'etimologie',
    self::TYPE_EXAMPLE => '',
    self::TYPE_COMMENT => 'comentariu',
    self::TYPE_DIFF => 'diferențiere',
    self::TYPE_EXPRESSION => '',
  ];

  const CSS_CLASSES = [
    self::TYPE_MEANING => '',
    self::TYPE_ETYMOLOGY => 'type-etymology',
    self::TYPE_EXAMPLE => 'type-example',
    self::TYPE_COMMENT => '',
    self::TYPE_DIFF => '',
    self::TYPE_EXPRESSION => 'type-expression',
  ];

  const FIELD_NAMES = [
    self::TYPE_MEANING => 'sens',
    self::TYPE_ETYMOLOGY => 'etimologie',
    self::TYPE_EXAMPLE => 'exemplu',
    self::TYPE_COMMENT => 'comentariu (public)',
    self::TYPE_DIFF => 'diferențiere',
    self::TYPE_EXPRESSION => 'expresie',
  ];

  const ICONS = [
    self::TYPE_MEANING => '',
    self::TYPE_ETYMOLOGY => '',
    self::TYPE_EXAMPLE => 'format_quote',
    self::TYPE_COMMENT => '',
    self::TYPE_DIFF => '',
    self::TYPE_EXPRESSION => 'chat_bubble',
  ];

  private $tree = null;

  function getDisplayTypeName() {
    return self::DISPLAY_NAMES[$this->type];
  }

  function getCssClass() {
    return self::CSS_CLASSES[$this->type];
  }

  function getTree() {
    if ($this->tree === null) {
      $this->tree = Tree::get_by_id($this->treeId);
    }
    return $this->tree;
  }

  function getRelations() {
    return Preload::getMeaningRelations($this->id);
  }

  function getSources() {
    return Preload::getMeaningSources($this->id);
  }

  function getTags() {
    return Preload::getMeaningTags($this->id);
  }

  function getIcon() {
    return self::ICONS[$this->type];
  }

  // Single entry point for sanitize()
  // $flash (boolean): if true, set flash messages for warnings
  // Simpler version of Definition->process()
  function process($flash = false) {
    $warnings = [];

    // sanitize
    list($this->internalRep, $ignored)
      = Str::sanitize($this->internalRep, 0, $warnings);

    if ($flash) {
      FlashMessage::bulkAdd($warnings, 'warning');
    }
  }

  /**
   * If necessary, returns a comma-separated list of synonyms to display
   * alongside the meaning. This happens when:
   * - The meaning is empty;
   * - The meaning is a parenthesis;
   * - The meaning ends in '=' or ':'.
   **/
  function getDisplaySynonyms() {
    $r = $this->internalRep;

    $isEmpty = !$r;
    $isParent = Str::startsWith($r, '(') && Str::endsWith($r, ')');
    $isEqual = Str::endsWith($r, '=');
    $isColon = Str::endsWith($r, ':');

    if ($isEmpty || $isParent || $isEqual || $isColon) {

      $synonyms = $this->getRelations()[Relation::TYPE_SYNONYM];

      if (!empty($synonyms)) {
        $parts = [];
        foreach ($synonyms as $s) {
          $parts[] = $s->getShortDescription();
        }

        $list = implode(', ', $parts);

        if ($isEmpty || $isParent) {
          $list = Str::capitalize($list);
        }

        return $list . '.';
      }
    }

    return '';
  }

  /**
   * Increases the first part of the breadcrumb by $x, so 3.5.1 increased by 7 becomes 10.5.1.
   **/
  function increaseBreadcrumb($x) {
    $parts = explode('.', $this->breadcrumb);
    if ($parts[0]) {
      $parts[0] += $x;
      $this->breadcrumb = implode('.', $parts);
    }
  }

  /**
   * Fills in the displayOrder and breadcrumb fields for an array of meanings.
   * Assumes the parentId and type fields are filled in. The ID may be empty
   * (in which case that meaning should not have children).
   **/
  static function renumber($meanings) {
    $order = 0;
    $numChildren = [ 0 => 0 ];  // number of children seen so far for each meaningId
    $breadcrumb = [ 0 => '' ];  // breadcrumbs for meanings seen so far

    foreach ($meanings as $m) {
      $m->displayOrder = ++$order;

      if ($m->type == Meaning::TYPE_MEANING) {
        $m->breadcrumb = $breadcrumb[$m->parentId] . (++$numChildren[$m->parentId]) . '.';
      } else {
        $m->breadcrumb = '';
      }

      $breadcrumb[$m->id] = $m->breadcrumb;
      $numChildren[$m->id] = 0;
    }
  }

  /**
   * Convert a tree produced by the tree editor to the format used by loadTree.
   * We need this in case validation fails and we cannot save the tree, so we need to display it again.
   **/
  static function convertTree($meanings) {
    $meaningStack = [];
    $results = [];

    foreach ($meanings as $tuple) {
      $row = [];
      $m = $tuple->id
         ? self::get_by_id($tuple->id)
         : Model::factory('Meaning')->create();

      $m->type = $tuple->type;
      $m->internalRep = $tuple->internalRep;
      $m->process();

      $mention = Mention::get_by_objectType_objectId(Mention::TYPE_MEANING, $m->id);
      $row = [
        'meaning' => $m,
        'sources' => Source::loadByIds($tuple->sourceIds),
        'tags' => Tag::loadByIds($tuple->tagIds),
        'relations' => Relation::loadRelatedTrees($tuple->relationIds),
        'children' => [],
        'canDelete' => !$mention,
      ];

      if ($tuple->level) {
        $meaningStack[$tuple->level - 1]['children'][] = &$row;
      } else {
        $results[] = &$row;
      }
      $meaningStack[$tuple->level] = &$row;
      unset($row);
    }

    return $results;
  }

  /* Save a tree produced by the tree editor in tree/edit.php */
  static function saveTree($meanings, $tree) {
    $seenMeaningIds = [];

    // Keep track of meanings that have incoming mentions
    $meaningsHavingMentions = Mention::getMeaningsHavingMentions($tree->id);
    $meaningIdsHavingMentions = Util::objectProperty($meaningsHavingMentions, 'id');

    // Keep track of original reps, mapped by id.
    // Then we can give warnings when meanings with mentions change.
    $original = Model::factory('Meaning')
              ->where('treeId', $tree->id)
              ->order_by_asc('displayOrder')
              ->find_many();
    $map = [];
    foreach ($original as $m) {
      $map[$m->id] = $m;
    }
    $modifiedMeaningsWithMentions = [];

    // Keep track of the previous meaning ID at each level. This allows us
    // to populate the parentId field
    $meaningStack = [];
    $displayOrder = 1;
    foreach ($meanings as $tuple) {
      $m = $tuple->id
         ? self::get_by_id($tuple->id)
         : Model::factory('Meaning')->create();
      $m->type = $tuple->type;
      $m->parentId = $tuple->level ? $meaningStack[$tuple->level - 1] : 0;
      $m->displayOrder = $displayOrder++;
      $m->breadcrumb = $tuple->breadcrumb;
      $m->userId = User::getActiveId();
      $m->treeId = $tree->id;
      $m->internalRep = $tuple->internalRep;
      $m->process(true);
      $m->save();
      $meaningStack[$tuple->level] = $m->id;

      if (in_array($m->id, $meaningIdsHavingMentions) &&
          ($map[$m->id]->internalRep != $m->internalRep)) {
        $modifiedMeaningsWithMentions[] = $m->breadcrumb;
      }

      MeaningSource::update($m->id, $tuple->sourceIds);
      ObjectTag::wipeAndRecreate($m->id, ObjectTag::TYPE_MEANING, $tuple->tagIds);
      foreach ($tuple->relationIds as $type => $treeIds) {
        if ($type) {
          Relation::updateList(['meaningId' => $m->id, 'type' => $type],
                               'treeId', $treeIds);
        }
      }
      $seenMeaningIds[] = $m->id;
    }
    self::deleteNotInSet($seenMeaningIds, $tree->id);

    if (count($modifiedMeaningsWithMentions)) {
      FlashMessage::add(sprintf('Ați modificat unele sensuri despre care există mențiuni: ' .
                                '<strong>%s</strong> Vă rugăm să consultați lista de mențiuni ' .
                                'ca să vă asigurați că ele sunt bine plasate.',
                                implode(', ', $modifiedMeaningsWithMentions)),
                        'warning');
    }
  }

  /* Deletes all the meanings associated with $treeId that aren't in the $meaningIds set */
  static function deleteNotInSet($meaningIds, $treeId) {
    $meanings = self::get_all_by_treeId($treeId);
    foreach ($meanings as $m) {
      if (!in_array($m->id, $meaningIds)) {
        $m->delete();
      }
    }
  }

  function save() {
    parent::save();

    // extract and save all mentions contained in this meaning

    preg_match_all("/\\[\\[(\d+)\\]\\]/", $this->internalRep, $m);
    $u = array_unique($m[1]);
    Mention::wipeAndRecreate($this->id, Mention::TYPE_TREE, $u);

    preg_match_all("/(?<!\\[)\\[(\d+)\\](?!\\])/", $this->internalRep, $m);
    $u = array_unique($m[1]);
    Mention::wipeAndRecreate($this->id, Mention::TYPE_MEANING, $u);
  }

  function delete() {
    MeaningSource::delete_all_by_meaningId($this->id);
    ObjectTag::delete_all_by_objectId_objectType($this->id, ObjectTag::TYPE_MEANING);
    Relation::delete_all_by_meaningId($this->id);

    // Reprocess meanings mentioning this one to remove said mentions
    $mentions = Mention::getMeaningMentions($this->id);
    foreach ($mentions as $ment) {
      $m = Meaning::get_by_id($ment->meaningId);
      $m->internalRep = str_replace("[{$this->id}]", '', $m->internalRep);
      $m->process();
      $m->save();
    }

    // Delete mentions containing this meaning on either side
    Mention::delete_all_by_meaningId($this->id);
    Mention::delete_all_by_objectId_objectType($this->id, Mention::TYPE_MEANING);
    parent::delete();
  }
}
