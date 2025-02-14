{extends "layout-admin.tpl"}

{block "title"}Pagina moderatorului{/block}

{block "content"}
  <h3>Pagina moderatorului</h3>

  {if User::can($reportPriv)}
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        Rapoarte

        <form>

          <small class="text-muted">
            calculate acum {($timeAgo/60)|nf} minute, {$timeAgo%60} secunde
          </small>

          <button type="submit" name="recountButton" class="btn btn-secondary btn-sm ms-2">
            {include "bits/icon.tpl" i=repeat}
            recalculează acum
          </button>

        </form>

      </div>

      {assign var="hasVisibleReports" value=false}
      <table class="table table-sm table-hover mb-0">
        {foreach $reports as $r}
          {if $r.count && User::can($r.privilege)}
            {assign var="hasVisibleReports" value=true}
            <tr>
              <td>
                {$r.text}
                {if isset($r.tag)}
                  {include "bits/tag.tpl" t=$r.tag}
                {/if}
              </td>
              <td><a href="{Config::URL_PREFIX}{$r.url}">{$r.count}</a></td>
            </tr>
          {/if}
        {/foreach}
      </table>
      {if !$hasVisibleReports}
        <div class="card-body">
          <p class="text-muted">
            Nu există rapoarte de afișat.
          </p>
        </div>
      {/if}
    </div>
  {/if}

  {if User::can(User::PRIV_EDIT)}
    <div class="card quickNav mb-3">
      <div class="card-header">
        Navigare rapidă
      </div>

      <div class="card-body row">
        <div class="col-12 col-sm-6 col-lg-4">
          <form action="{Router::link('definition/edit')}">
            <select id="definitionId" name="definitionId"></select>
          </form>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
          <form action="{Router::link('entry/edit')}">
            <select id="entryId" name="id"></select>
          </form>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
          <form action="{Router::link('lexeme/edit')}">
            <select id="lexemeId" name="lexemeId"></select>
          </form>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
          <form action="{Router::link('tree/edit')}">
            <select id="treeId" name="id"></select>
          </form>
        </div>

        <div class="col-12 col-sm-6 col-lg-4">
          <form action="{Router::link('tag/edit')}">
            <select id="labelId" name="id"></select>
          </form>
        </div>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_EDIT)}
    <div class="card mb-3">
      <div class="card-header">
        Căutare avansată
      </div>

      <div class="card-body">
        <form
          class="row"
          action="{Router::link('aggregate/advancedSearch')}"
          method="post">

          <div class="col-12 col-md-6 col-lg-4">

            <fieldset>
              <legend>proprietăți intrări</legend>

              <div class="row mb-2">
                <label class="col-4 col-form-label">descriere</label>
                <div class="col-8">
                  <input class="form-control"
                    type="text"
                    name="description"
                    placeholder="acceptă expresii regulate">
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">structurare</label>
                <div class="col-8">
                  <select name="structStatus" class="form-select">
                    <option value="">oricare</option>
                    {foreach Entry::STRUCT_STATUS_NAMES as $i => $s}
                      <option value="{$i}">{$s}</option>
                    {/foreach}
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">structurist</label>
                <div class="col-8">
                  <select name="structuristId" class="form-select">
                    <option value="{Entry::STRUCTURIST_ID_ANY}">oricare</option>
                    <option value="{Entry::STRUCTURIST_ID_NONE}">niciunul</option>
                    {foreach $structurists as $s}
                      <option value="{$s->id}">
                        {$s->nick} ({$s->name})
                      </option>
                    {/foreach}
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">etichete</label>
                <div class="col-8">
                  <select name="entryTagIds[]" class="form-select select2Tags" multiple>
                  </select>
                </div>
              </div>

            </fieldset>
          </div>

          <div class="col-12 col-md-6 col-lg-4">

            <fieldset>
              <legend>proprietăți lexeme</legend>

              <div class="row mb-2">
                <label class="col-4 col-form-label">formă lexem</label>
                <div class="col-8">
                  <input class="form-control"
                    type="text"
                    name="formNoAccent"
                    placeholder="acceptă expresii regulate">
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">paradigmă</label>
                <div class="col-8">
                  <select class="form-select" name="paradigm">
                    <option value="">indiferent</option>
                    <option value="1">da</option>
                    <option value="0">nu</option>
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">etichete</label>
                <div class="col-8">
                  <select name="lexemeTagIds[]" class="form-select select2Tags" multiple>
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">modele</label>
                <div class="col-8">
                  <select
                    id="advSearchModelTypes"
                    name="modelTypes[]"
                    class="form-select"
                    multiple>
                    {foreach $modelTypes as $mt}
                      <option value="{$mt->code}">{$mt->code}</option>
                    {/foreach}
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">restricții</label>
                <div class="col-8">
                  <input class="form-control"
                    type="text"
                    name="restrictions">
                </div>
              </div>

            </fieldset>
          </div>

          <div class="col-12 col-md-6 col-lg-4">

            <fieldset>
              <legend>proprietăți definiții</legend>

              <div class="row mb-2">
                <label class="col-4 col-form-label">lexicon</label>
                <div class="col-8">
                  <input class="form-control"
                    type="text"
                    name="lexicon"
                    placeholder="acceptă expresii regulate">
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">starea</label>
                <div class="col-8">
                  {include "bits/statusDropDown.tpl"
                    name="status"
                    selectedStatus=Definition::ST_ACTIVE
                    anyOption=true}
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">sursa</label>
                <div class="col-8">
                  {include "bits/sourceDropDown.tpl" name="sourceId"}
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">structurată</label>
                <div class="col-8">
                  <select class="form-select" name="structured">
                    <option value="">indiferent</option>
                    <option value="1">da</option>
                    <option value="0">nu</option>
                  </select>
                </div>
              </div>

              <div class="row mb-2">
                <label class="col-4 col-form-label">trimise de</label>
                <div class="col-8">
                  <select name="userId" class="form-select select2Users"></select>
                </div>
              </div>

              <div class="row mb-2">
                <div class="col-2 pe-0">
                  <label class="col-form-label">între</label>
                </div>
                <div class="col ps-0">
                  <input type="text" name="startDate" class="form-control calendar">
                </div>
                <div class="col-1 px-0">
                  <label class="col-form-label">și</label>
                </div>
                <div class="col ps-0">
                  <input type="text" name="endDate" class="form-control calendar">
                </div>
              </div>

            </fieldset>
          </div>

          <div class="d-flex justify-content-center mt-2">
            <label class="col-form-label">afișează</label>
            <div class="btn-group px-2" role="group">
              <input id="radioEntry" type="radio" class="btn-check" name="view" value="Entry" checked>
              <label class="btn btn-outline-secondary" for="radioEntry">
                intrări
              </label>
              <input id="radioLexeme" type="radio" class="btn-check"  name="view" value="Lexeme">
              <label class="btn btn-outline-secondary" for="radioLexeme">
                lexeme
              </label>
              <input id="radioDef" type="radio" class="btn-check"  name="view" value="Definition">
              <label class="btn btn-outline-secondary" for="radioDef">
                definiții
              </label>
            </div>

            <button type="submit" class="btn btn-primary" name="submitButton">
              {include "bits/icon.tpl" i=search}
              caută
            </button>
          </div>
        </form>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_EDIT)}
    <div class="card mb-3">
      <div class="card-header">
        Modele de flexiune
      </div>

      <div class="card-body">

        <form action="{Router::link('model/dispatch')}">

          {include "bits/modelDropDown.tpl" modelTypes=$canonicalModelTypes}

          <button type="submit" class="btn btn-outline-secondary me-1" name="goToModel">
            pagina modelului
          </button>

          <button type="submit" class="btn btn-outline-secondary me-1" name="showLexemes">
            arată toate lexemele
          </button>

          <button type="submit" class="btn btn-outline-secondary me-1" name="cloneModel">
            {include "bits/icon.tpl" i=content_copy}
            clonează
          </button>

          <button type="submit" class="btn btn-danger me-1" name="deleteModel">
            {include "bits/icon.tpl" i=delete}
            șterge
          </button>

        </form>

        <div class="mt-3">
          <a href="{Router::link('lexeme/mergeTool')}">unificare plural-singular</a>

          <span class="text-muted">
            pentru familiile de plante și animale și pentru alte lexeme care apar
            cu restricția „P” într-o sursă, dar fără restricții în altă sursă.
          </span>
        </div>

        <div class="mt-2">
          <a href="{Router::link('lexeme/bulkLabelSelectSuffix')}">
            etichetare în masă a lexemelor
          </a>

          <span class="text-muted">
            pe baza sufixului
          </span>
        </div>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_ADMIN)}
    <div class="card mb-3">
      <div class="card-header">
        Înlocuiește în masă
      </div>

      <div class="card-body">
        <form
          class="row"
          action="{Router::link('aggregate/bulkReplace')}"
          method="post">

          <div class="col-12 col-md-6">

            <div class="row mb-2">
              <label class="col-3 col-form-label">înlocuiește</label>
              <div class="col-9">
                <input class="form-control" type="text" name="search">
              </div>
            </div>

            <div class="row mb-2">
              <label class="col-3 col-form-label">cu</label>
              <div class="col-9">
                <input class="form-control" type="text" name="replace">
              </div>
            </div>

            <div class="row mb-2">
              <label class="col-3 col-form-label">în</label>
              <div class="col-9">
                <select class="form-select" name="target">
                  <option value="1">definiții</option>
                  <option value="2">sensuri</option>
                </select>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">

            <div class="row mb-2">
              <label class="col-3 col-form-label">sursa</label>
              <div class="col-9">
                {include "bits/sourceDropDown.tpl" id="sourceDropDown2" name="sourceId"}
                <small class="text-muted">
                  se aplică numai definițiilor
                </small>
              </div>
            </div>

            <div class="row mb-2">
              <label class="col-3 col-form-label">rezultate</label>
              <div class="col-9">
                <input type="number"
                  name="limit"
                  class="form-control"
                  value="1000"
                  min="100"
                  max="1000"
                  step="100">
              </div>
            </div>
          </div>

          <div class="mb-2">
            <button type="submit" class="btn btn-primary" name="previewButton">
              previzualizează
            </button>
          </div>
        </form>

        <p class="text-muted">
          Folosiți cu precauție această unealtă. Ea înlocuiește primul text cu
          al doilea în toate definițiile, incluzând notele de subsol, făcând
          diferența între litere mari și mici (case-sensitive) și fără
          expresii regulate (textul este căutat ca atare). Vor fi modificate
          maximum 1.000 de rezultate. Veți putea vedea lista de modificări
          propuse și să o acceptați.
        </p>
        {notice type="warning"}
          Evitați pe cât posibil definițiile cu note de subsol și pe cele
          structurate, debifându-le.
        {/notice}
      </div>
    </div>
  {/if}

  {if User::can($linkPriv)}
    <div class="card mb-3">
      <div class="card-header">
        Legături
      </div>

      <div class="card-body">
        <div class="row">
          {foreach $links as $l}
            {if User::can($l.privilege)}
              <div class="col-lg-3 col-md-4 col-sm-6">
                <a href="{$l.url}">{$l.text}</a>
              </div>
            {/if}
          {/foreach}
        </div>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_EDIT + User::PRIV_DONATION)}
    <div class="card mb-3">
      <div class="card-header">
        Unelte diverse
      </div>

      <div class="card-body">
        <ul class="mb-0">
          {if User::can(User::PRIV_EDIT)}
            <li>
              <a href="{Router::link('definition/associateDE')}">reasociere D. Enciclopedic</a>
              <span class="text-muted">
                o interfață mai rapidă pentru asocierea de lexeme și modificarea modelelor
                acestora
              </span>
            </li>

            <li>
              <a href="{Router::link('lexeme/accentTool')}">plasarea asistată a accentelor</a>
              <span class="text-muted">
                pentru lexeme alese la întâmplare
              </span>
            </li>

            <li>
              <a href="{Router::link('accuracy/projects')}">verificarea acurateței editorilor</a>
            </li>
          {/if}

          {if User::can(User::PRIV_DONATION)}
            <li>
              <a href="{Router::link('donation/process')}">procesează donații</a>
            </li>
          {/if}
        </ul>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_STRUCT)}
    <div class="card mb-3">
      <div class="card-header">
        Structurare
      </div>

      <div class="card-body">
        <ul class="mb-0">
          <li>
            <a href="{Router::link('entry/easyStructure')}">intrări ușor de structurat</a>
            <span class="text-muted">
              100 de cuvinte din DEX cu definiții cât mai scurte
            </span>
          </li>
        </ul>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_VISUAL)}
    <div class="card mb-3">
      <div class="card-header">
        Dicționarul vizual
      </div>

      <div class="card-body">
        <a href="{Router::link('visual/list')}">dicționarul vizual</a>
      </div>
    </div>
  {/if}

    {if User::can(User::PRIV_EDIT)}
      <div class="card mb-3">
        <div class="card-header">
          Regenerează top
        </div>

        <div class="card-body">
          <form>
            <button type="submit" class="btn btn-primary" name="syncTopButton">
              regenerează topul
            </button>
          </form>
        </div>
      </div>
    {/if}

  {if User::can(User::PRIV_EDIT)}
    <div class="card mb-3">
      <div class="card-header">
        Articole lingvistice
      </div>

      <div class="card-body">
        <form>
          <button type="submit" class="btn btn-primary" name="syncWikiButton">
            sincronizează articolele
          </button>
        </form>
      </div>
    </div>
  {/if}

  {if User::can(User::PRIV_WOTD)}
    <div class="card mb-3">
      <div class="card-header">
        Cuvântul + imaginea zilei
      </div>

      <div class="card-body">
        <ul class="mb-0">
          <li><a href="{Router::link('wotd/table')}">cuvântul zilei</a></li>
          <li><a href="{Router::link('wotd/images')}">imaginea zilei</a></li>
          <li><a href="{Router::link('artist/list')}">autori</a></li>
          <li><a href="{Router::link('artist/assign')}">alocarea autorilor</a></li>
          <li>
            asistent:
            <ul class="list-inline">
              {foreach $wotdAssistantDates as $timestamp}
                <li class="list-inline-item">
                  <a href="{Router::link('wotd/assistant')}?for={$timestamp|date:'yyyy-MM'}">
                    {$timestamp|date:'LLLL yyyy'}
                  </a>
                </li>
              {/foreach}
            </ul>
          </li>
        </ul>
      </div>
    </div>
  {/if}
{/block}
