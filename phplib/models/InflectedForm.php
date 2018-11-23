<?php

class InflectedForm extends BaseObject {
  public static $_table = 'InflectedForm';

  function setForm($form) {
    $this->form = $form;
    $this->formNoAccent = preg_replace("/(?<!\\\\)'/", '', $form);
    $this->formNoAccent = str_replace("\\'", "'", $this->formNoAccent);
    $this->formUtf8General = $this->formNoAccent;
  }

  static function create($form = null, $lexemeId = null, $inflectionId = null,
                                $variant = null, $recommended = 1) {
    $if = Model::factory('InflectedForm')->create();
    $if->setForm($form);
    $if->lexemeId = $lexemeId;
    $if->inflectionId = $inflectionId;
    $if->variant = $variant;
    $if->recommended = $recommended;
    return $if;
  }

  function getHtmlForm() {
    $s = Str::highlightAccent($this->form);
    $s = str_replace("\\'", "'", $s);
    if ($this->apheresis) {
      $s = "&#x2011{$s}"; // non-breaking hyphen
    }
    return $s;
  }

  static function mapByInflectionRank($ifs) {
    $result = [];
    foreach ($ifs as $if) {
      $inflection = Inflection::get_by_id($if->inflectionId);
      if (!array_key_exists($inflection->rank, $result)) {
        $result[$inflection->rank] = [];
      }
      $result[$inflection->rank][] = $if;
    }
    return $result;
  }

  // Returns an inflected form of $lexeme that has the same formNoAccent as
  // $chunk. Throws ParadigmException if non exists. Prefers lower-rank
  // inflections, e.g.  înc'urcă-lume over încurc'ă-lume.
  static function getByLexemeChunk($lexeme, $chunk, $inflection) {
    $if = Model::factory('InflectedForm')
      ->table_alias('if')
      ->select('if.*')
      ->join('Inflection', ['if.inflectionId', '=', 'i.id'], 'i')
      ->where('if.lexemeId', $lexeme->id)
      ->where('if.formNoAccent', $chunk)
      ->order_by_asc('i.rank')
      ->find_one();

    if (!$if) {
      throw new ParadigmException(
        $inflection->id,
        "Lexemul „{$lexeme->form}” nu generează forma „{$chunk}”."
      );
    }

    return $if;
  }

  // The inflection ID implies the correct canonical model type
  static function deleteByModelNumberInflectionId($modelNumber, $inflId) {
    // Idiorm doesn't support deletes with joins
    DB::execute(sprintf("
      delete i
      from InflectedForm i
      join Lexeme l on i.lexemeId = l.id
      where l.modelNumber = '%s' and i.inflectionId = %d
    ", addslashes($modelNumber), $inflId));
  }

  static function isStopWord($field, $form) {
    return Model::factory('InflectedForm')
      ->table_alias('i')
      ->join('Lexeme', 'i.lexemeId = l.id', 'l')
      ->where("i.{$field}", $form)
      ->where('l.stopWord', 1)
      ->count();
  }

  function save() {
    $this->formUtf8General = $this->formNoAccent;
    parent::save();
  }
}
