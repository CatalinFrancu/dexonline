<?php
User::mustHave(User::PRIV_ADMIN);

$sourceId = Request::get('source');
$editorId = Request::get('editor');
$terminator = PHP_EOL . (Request::get('term') == 1 ? PHP_EOL : "");
$msgType = 'success';
$message = '';

if ($_FILES && $_FILES["file"]) {
  if ($_FILES["file"]["error"] > 0) {
    $msgType = 'danger';
    $message =  "Eroare: " . $_FILES["file"]["error"];
  }
  else {
    $userId = User::getActiveId();
    $ocrLot = Model::factory('OCRLot')->create();
    $ocrLot->userId = $userId;
    $ocrLot->sourceId = $sourceId;
    $ocrLot->fileName = $_FILES["file"]["name"];
    $ocrLot->fileSize = $_FILES["file"]["size"];
    $ocrLot->startedAt = date('Y-m-d H:i:s');

    try {
      $ocrLot->save();
    }
    catch (Exception $e) {
      $msgType = 'danger';
      $message = "<div> Eroare: " . $e->getMessage() . "</div>";
    }

    if ($msgType != 'danger') {
      $lotId = $ocrLot->id();
      $errCount = 0;
      $lineCount = 0;

      $fp = fopen($_FILES["file"]["tmp_name"],'r');
      //while ($line = fgets($fp)) {
      while (!feof($fp)) {
        $line = stream_get_line($fp, 1000000, $terminator);
        $line = trim($line);
        if (!empty($line)) {
          $lineCount++;
          $ocr = Model::factory('OCR')->create();
          $ocr->lotId = $lotId;
          $ocr->userId = $userId;
          $ocr->sourceId = $sourceId;
          if ($editorId) {
            $ocr->editorId = $editorId;
          }
          $ocr->ocrText = $line;
          $ocr->dateAdded = date('Y-m-d H:i:s');
          try {
            $ocr->save();
          }
          catch (Exception $e) {
            $errCount++;
            $msgType = 'danger';
            $message .= "<div> Eroare: " . $e->getMessage() . "</div>";
          }
        }
      }

      $ocrLot->status = 'done';
      $ocrLot->save();
      $message .= "Fișierul " . $_FILES["file"]["name"] .
        " ({$lineCount} linii) a fost salvat" .
        ($msgType == 'danger' ? " cu {$errCount} erori." : "!");
    }

  }
}

const OCR_EDITOR_STATS =
  "SELECT
  U.nick Utilizator,
  SUM(IF(X.status='published', X.cnt, 0)) Număr_de_definiții_publicate,
  SUM(IF(X.status='raw', X.cnt, 0)) Număr_de_definiții_alocate,
  SUM(IF(X.status='published', X.tsize, 0)) Număr_de_caractere_publicate,
  SUM(IF(X.status='raw', X.tsize, 0)) Număr_de_caractere_alocate
FROM (
  SELECT editorId, status, count(*) cnt, sum(char_length(ocrText)) tsize
  FROM OCR GROUP BY editorId, status
) X
JOIN User U on X.editorId=U.id
GROUP BY U.nick";

const OCR_PREP_STATS =
  "SELECT
  U.nick Utilizator,
  S.shortName Dicționar,
  SUM(IF(X.status='published', X.cnt, 0)) Definiții_publicate,
  SUM(IF(X.status='raw', X.cnt, 0)) Definiții_în_lucru,
  SUM(IF(X.status='published', X.size, 0)) Nr_caractere_publicate,
  SUM(IF(X.status='raw', X.size, 0)) Nr_caractere_în_lucru
FROM (
  SELECT userId, sourceId, status, count(*) cnt, sum(char_length(ocrText)) size
  FROM OCR GROUP BY userId, sourceId, status
) X
JOIN User U ON X.userId=U.id
JOIN Source S ON X.sourceId=S.id
GROUP BY U.nick, S.shortName";

$allModeratorSources = Model::factory('Source')
  ->where('canModerate', true)
  ->order_by_asc('displayOrder')
  ->find_many();

$allOcrModerators = Model::factory('User')
  ->where_raw('moderator & 4')
  ->order_by_asc('id')
  ->find_many();

Smart::assign([
  'msgType' => $msgType,
  'message' => $message,
  'allModeratorSources' => $allModeratorSources,
  'allOCRModerators' => $allOcrModerators,
  'statsPrep' => DB::execute(OCR_PREP_STATS),
  'statsEditors' => DB::execute(OCR_EDITOR_STATS),
]);
Smart::addResources('admin');
Smart::display('definition/ocrUpload.tpl');
