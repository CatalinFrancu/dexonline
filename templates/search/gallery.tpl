{if !empty($images)}
  {include "bits/galleryCanvas.tpl"}

  <div id="gallery">
    <div class="panel panel-default">
      <div class="panel-heading">{'images'|_}</div>
      <div class="panel-body">
        {foreach $images as $i}
          <a class="gallery"
             href="{$i->getImageUrl()}"
             data-visual-id="{$i->id}"
             title="Imagine: {$i->getTitle()}">
            <img src="{$i->getThumbUrl()}" alt="imagine pentru acest cuvânt">
          </a>
        {/foreach}
      </div>
    </div>
  </div>

{/if}
