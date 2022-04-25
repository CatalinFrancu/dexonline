{extends "layout.tpl"}

{block "title"}{$wa->title|default:'Articol inexistent'}{/block}

{block "content"}
  {assign var="wa" scope=global value=$wa|default:null}
  {assign var="title" value=$wa->title|default:'Articol inexistent'}

  <h3>{$wa->title|default:''}</h3>

  <div>
    {$wa->htmlContents|default:'Articolul pe care îl căutați nu există.'}
  </div>

  <hr>

  <h3>{t}Other linguistics articles{/t}</h3>

  {foreach $wikiTitles as $section => $articles}
    <h4>{$section|escape:'html'}</h4>
    <ul>
      {foreach $articles as $wa}
        {if $wa->title != $title}
          <li>
            <a href="{$wa->getUrlTitle()}">{$wa->title}</a>
          </li>
        {/if}
      {/foreach}
    </ul>
  {/foreach}

{/block}
