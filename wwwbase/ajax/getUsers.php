<?php
require_once("../../phplib/Core.php");

$id = Request::get('id');
$term = Request::get('term');
$priv = Request::get('priv');

if ($id) {
  $users = [User::get_by_id($id)];
} else if ($term) {
  $users = Model::factory('User')
         ->where_any_is([['nick' => "%{$term}%"],
                         ['name' => "%{$term}%"],
                         ['email' => "%{$term}%"]],
                        'like');
  if ($priv) {
    $users = $users->where_raw("moderator & {$priv}");
  }

  $users = $users
         ->order_by_asc('nick')
         ->limit(10)
         ->find_many();
} else {
  $users = [];
}

$resp = ['results' => []];
foreach ($users as $u) {
  $resp['results'][] = ['id' => $u->id,
                        'text' => "{$u->nick} ({$u->name})"];
}

header('Content-Type: application/json');
print json_encode($resp);
