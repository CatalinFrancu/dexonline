{extends "layout-admin.tpl"}

{block "title"}Lexeme neetichetate{/block}

{block "content"}

  <h3>{$lexems|count} lexeme neetichetate</h3>

  {include "bits/lexemList.tpl"}

{/block}
