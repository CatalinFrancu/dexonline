<?php

require_once '../../lib/Core.php';
User::mustHave(User::PRIV_STRUCT | User::PRIV_EDIT);

$query = Request::get('term');
$query = addslashes($query);

// Latin alphabet comparisons - allow Ş or S instead of Ș
$sources = Model::factory('Source')
  ->where_raw("shortName collate utf8mb4_general_ci like '{$query}%'")
  ->limit(10)
  ->find_many();

$resp = [ 'results' => [] ];
foreach ($sources as $s) {
  $resp['results'][] = [
    'id' => $s->id,
    'text' => $s->shortName,
  ];
}

header('Content-Type: application/json');
echo json_encode($resp);
