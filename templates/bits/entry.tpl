{$editLink=$editLink|default:false}
{$boxed=$boxed|default:false}
{$target=$target|default:'_self'}
{$editLinkClass=$editLinkClass|default:''}
{$link=$link|default:false}
{$tagList=$tagList|default:false}

{strip}
{if $boxed}<span class="linkBox">{/if}
{if $editLink}
  <a
    href="{Router::link('entry/edit')}?id={$entry->id}"
    class="{$editLinkClass}"
    title="editează"
    target="{$target}">
    {$entry->description}
  </a>
{elseif $link}
  <a href="{Config::URL_PREFIX}intrare/{$entry->getShortDescription()}/{$entry->id}">
    {$entry->description}
  </a>
{else}
  <span class="entryName">{$entry->description}</span>
{/if}
{if $boxed}</span>{/if}
{/strip}

{if $tagList}
  <span class="tagList">
    {foreach $entry->getTags() as $t}
      {include "bits/tag.tpl"}
    {/foreach}
  </span>
{/if}
