<?php

class Abbreviation extends BaseObject implements DatedObject {
  public static $_table = 'Abbreviation';

  static function create(
    $sourceId, $short, $internalRep, $ambiguous, $caseSensitive, $enforced, $html, $modUserId
  ) {
    $a = Model::factory('Abbreviation')->create();
    $a->sourceId = $sourceId;
    $a->short = $short;
    $a->internalRep = $internalRep;
    $a->ambiguous = $ambiguous;
    $a->caseSensitive = $caseSensitive;
    $a->enforced = $enforced;
    $a->html = $html;
    $a->modUserId = $modUserId;
    return $a;
  }

  static function countAvailable($sourceId) {
    return Model::factory('Abbreviation')
      ->where('sourceID', $sourceId)
      ->count();
  }

  /**
   * Returns, with constraints, first find abbreviation of form $short
   *
   * @param   string  $excludedId all others
   * @param   string  $short      abbreviation short form
   * @param   int     $sourceId   source to search
   * @return  ORMWrapper
   */
  static function getDuplicate($excludedId, $short, $sourceId) {
    return Model::factory('Abbreviation')
        ->where_raw('short = binary ?', $short)
        ->where('sourceId', $sourceId)
        ->where_not_equal('id', $excludedId)
        ->find_one();
  }

}
