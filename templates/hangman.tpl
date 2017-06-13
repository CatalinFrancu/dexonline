{extends "layout.tpl"}

{block "title"}Spânzurătoarea{/block}

{block "search"}{/block}

{block "content"}
  <script>
   var word = "{$word}";
   var difficulty = "{$difficulty}";
  </script>

  <div class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Spânzurătoarea</h3>
    </div>
    <div class="panel-body">
      <form id="hangman" action="">

        <div class="graphics">
          <label>Vieți rămase: <span id="livesLeft">6</span></label>
          <div class="hangmanPic"> </div>
          <div class="imageLicense">imagini © dexonline.ro</div>

          <div class="output">
            {section name="ignored" start=0 loop=$wordLength}
              <input style="width:15pt" class="letters" name="out[]" type="text" readonly size="1">
            {/section}
          </div>
        </div>

        <div class="controls">
          {foreach $letters as $letter}
            <input class="letterButtons btn" type="button" value="{$letter|mb_strtoupper}">
          {/foreach}
          <input id="hintButton" type="button" value="Dă-mi un indiciu" class="btn">
        </div>

        <div class="newGameControls">
          <label>Joc nou:</label>
          <button class="btn btn-info" type="button" data-level="1">ușor</button>
          <button class="btn btn-info" type="button" data-level="2">mediu</button><br>
          <button class="btn btn-info" type="button" data-level="3">dificil</button>
          <button class="btn btn-info" type="button" data-level="4">expert</button>
        </div>
      </form>
    </div>
  </div>

  <div id="resultsWrapper" class="panel panel-default">
    <div class="panel-heading">
      <h3 class="panel-title">Definiții</h3>
    </div>
    <div class="panel-body">
      {foreach $searchResults as $row}
        {include "bits/definition.tpl" showBookmark=1}
      {/foreach}
    </div>
  </div>

  <div id="endModal" class="modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-body">
          <div class="win text-success">
            Felicitări, ai câștigat!
          </div>
          <div class="lose text-danger">
            Ne pare rău, ai pierdut.
          </div>
        </div>
      </div>
    </div>
  </div>

{/block}
