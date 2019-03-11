{extends "layout.tpl"}

{block "title"}Site Clones{/block}

{block "content"}
  <h3>Site Clones </h3>

  <p> {$definition} </p>
  <h4>All results </h4>
  <ul>
    {foreach $listAll as $list}
	    <li><a href="{$list}" >{$list}</a></li> 
    {/foreach}
  </ul>

  <h4>Message Alert </h4> 
  <ul>
    {foreach $alert as $msg}
	    <li> {$msg} </li>
    {/foreach}
  </ul>

  <h4>BlackList </h4>
  <ul>
    {foreach $blackList as $url}
	    <li><a href="{$url}" >{$url}</a></li>
    {/foreach}
  </ul>
{/block}
