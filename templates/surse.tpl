{extends "layout-admin.tpl"}

{block "title"}Surse{/block}

{block "content"}

  <h3>Surse</h3>

  <div class="alert alert-info">
    <p>Duceți cursorul deasupra unui nume de dicționar pentru a vedea mai multe detalii</p>
    {if $sUser && $sUser->moderator & User::PRIV_EDIT}
      <p>Pentru a reordona sursele, apucați de un rând, dar nu de o zonă cu text. La sfârșit, nu uitați să salvați.</p>
    {/if}
  </div>

  <form method="post" action="surse">
    <table id="sources" class="table table-striped ">
      <thead>
        <tr>
          <th class="abbreviation">Nume scurt</th>
          {if $sUser && $sUser->moderator & User::PRIV_EDIT}
          <th class="type">Categorie</th>
          <th class="manager">Manager</th>
          {/if}
          <th class="nick">Nume</th>
          <th>% utilizat</th>
          {if $sUser && $sUser->moderator & User::PRIV_EDIT}
            <th>Acțiuni</th>
          {/if}
        </tr>
      </thead>
      <tbody>
        {foreach $src as $s}
          {if $s->isActive || ($sUser && $sUser->moderator & User::PRIV_EDIT)}
            <tr>
              <td class="abbreviation">
                {if $s->link && $sUser && $sUser->moderator & User::PRIV_EDIT}
                  <a href="{$s->link}" target="_blank"><span class="sourceShortName">{$s->shortName}</span></a>
                {else}
                  <span class="sourceShortName">{$s->shortName}</span>
                {/if}
              </td>
              {if $sUser && $sUser->moderator & User::PRIV_EDIT}
              <td class="type">
                <span class="sourceType">{if $s->sourceTypeId}{SourceType::getField('name', $s->sourceTypeId)}{else}{/if}</span>
              </td>
              <td class="manager">
                <span class="sourceManager">{if $s->managerId}{User::getField('name', $s->managerId)}{else}{/if}</span>
              </td>
              {/if}
              <td class="nick">
                <input type="hidden" name="ids[]" value="{$s->id}"/>
                <span class="sourceName">
                  {$s->name}
                  <div class="popover bottom">
                    <div class="arrow"></div>
                    <h3 class="popover-title">{$s->name}</h3>
                    <div class="popover-content">
                      Autor: {$s->author}<br/>
                      Editură: {$s->publisher}<br/>
                      Anul apariției: {$s->year}<br/>
                      {if $sUser && $sUser->moderator & User::PRIV_EDIT}
                        {if $s->importType}
                      <br/>
                      Import {$s->getImportTypeLabel()}
                        {/if}
                      {/if}
                    </div>
                  </div>
                </span>
              </td>
              <td data-text="{$s->percentComplete}">{include "bits/sourcePercentComplete.tpl" s=$s}</td>
              {if $sUser && $sUser->moderator & User::PRIV_EDIT}
                <td><a href="editare-sursa?id={$s->id}">editează</a></td>
              {/if}
            </tr>
          {/if}
        {/foreach}
      </tbody>
    </table>

    {if $sUser && $sUser->moderator & User::PRIV_EDIT}
      <button class="btn btn-success" type="submit" name="saveButton">
        <i class="glyphicon glyphicon-floppy-disk"></i>
        <u>s</u>alvează
      </button>

      <a class="btn btn-default" href="editare-sursa">
        <i class="glyphicon glyphicon-plus"></i>
        adaugă o sursă
      </a>
      <a class="btn btn-link" href="">renunță</a>
    {/if}

  </form>

  <script>
   $(document).ready(function() {
     $("#sources").tablesorter();
   });
  </script>

  {* Drag-and-drop reordering of rows, only for admins *}
  {if $sUser && $sUser->moderator & User::PRIV_EDIT}
    <script>
     jQuery(document).ready(function() {
       $("#sources").tableDnD();
     });
    </script>
  {/if}
{/block}
