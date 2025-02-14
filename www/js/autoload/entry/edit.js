$(function() {

  var isStructurist;

  function init() {

    isStructurist = $('#isStructurist').text() != '0';

    checkEntryWikiPage();

    initSelect2('#mainLexemeIds, #variantLexemeIds', 'ajax/getLexemesById.php', {
      ajax: { url: wwwRoot + 'ajax/getLexemes.php' },
      minimumInputLength: 1,
      templateSelection: formatLexemeWithEditLink,
    }, 'lexeme-group');
    $('.btn-move-lexemes').click(moveLexemes);

    var entryAjax = {
      url: wwwRoot + 'ajax/getEntries.php',
      data: function(params) {
        params['exclude'] = $('#entryId').val();
        if (!isStructurist) {
          params['unstructured'] = true;
        };
        return params;
      },
    }

    $('#mergeEntryId').select2({
      ajax: entryAjax,
      dropdownParent: $('#mergeModal'),
      minimumInputLength: 1,
      placeholder: 'alegeți o intrare',
      width: '100%',
    });

    $('#associateEntryIds').select2({
      ajax: entryAjax,
      dropdownParent: $('#associateModal'),
      minimumInputLength: 1,
      placeholder: 'alegeți una sau mai multe intrări',
      width: '100%',
    });

    $('#description').on('change input paste', showRenameDiv);

    $('.toggleRepLink').click(toggleRepClick);
    $('.toggleTypoLink').click(toggleTypoClick);
    $('.toggleRepSelect').click(toggleRepChange);
    $('.toggleStructuredLink').click(toggleStructuredClick);
    $('#defFilterSelect, #structurableFilter').click(defFilterChange);
    $('#treeFilterSelect').change(treeFilterChange);

    $('button[name="delete"]').click(function() {
      return confirm('Confirmați ștergerea acestei intrări?');
    });

    $('#mergeModal').on('shown.bs.modal', function () {
      $('#mergeEntryId').select2('open');
    });

    $('#associateModal').on('shown.bs.modal', associateModalShown);

    $('#dissociateButton').click(function () {
      return confirm('Confirmați disocierea?');
    });
  }

  function moveLexemes() {
    var from = $(this).data('from');
    var to = $(this).data('to');
    $(from).find('option').appendTo($(to));
  }

  function showRenameDiv() {
    $('#renameDiv').removeAttr('hidden');
  }

  /* Definitions can be shown as internal or HTML notation, with abbreviations expanded
   * or collapsed. This gives rise to four combinations, coded on two bits each.
   * Clicking on the "show / hide HTML" and show / hide abbreviations" fiddles some bits
   * and sets the "visible" class on the appropriate element. */
  function toggleRepClick() {
    // Hide the old definition
    var oldActive = $(this).closest('.defWrapper').find('[data-active]');
    oldActive.stop().hide().removeAttr('data-active');

    // Recalculate the code and show the new definition
    var code = oldActive.attr('data-code') ^ $(this).attr('data-order');
    var newActive = $(this).closest('.defWrapper').find('[data-code=' + code + ']');
    newActive.stop().show().attr('data-active', '');

    // Toggle the data-value attribute
    $(this).attr('data-value', 1 - $(this).attr('data-value'));
    return false;
  }

  /* User has selected a value from the text/html select. Toggle all definitions that
   * aren't in that state already. */
  function toggleRepChange() {
    var order = $(this).attr('data-order');
    var value = 1 - $(this).val(); // Links that have this BAD value need to be clicked.
    $('.toggleRepLink[data-order=' + order + '][data-value=' + value + ']').click();
  }

  function toggleStructuredClick() {
    var parent = $(this).closest('.defWrapper');
    var id = parent.attr('id').split('_')[1];
    if (parent.hasClass('structured')) {
      parent.removeClass('structured');
      var value = 0;
    } else {
      parent.addClass('structured');
      var value = 1;
    }
    $.get(wwwRoot + 'ajax/setDefinitionStructured.php?id=' + id + '&value=' + value);

    $(this).siblings().toggle();
    $(this).toggle();

    return false;
  }

  /* Definitions has typos :)
   * easy clicking report sending. */
  function toggleTypoClick() {
    // Get definition id
    var parent = $(this).closest('.defWrapper');
    var id = parent.attr('id').split('_')[1];

    // text of link is changing before this fires, so we need to check the changed value
    if ($(this).attr('data-other-text') === 'anulează'){
      $('#typo_' + id).slideToggle('normal', function() {
        $(this).remove();
        $('#def_'+ id+' .rep').off('click', prepareTypo);
      });
    } else {
      // clone the sending form and attach it to the def_[id] div
      $('#typo').clone(true).appendTo(parent).prop('id', 'typo_' + id ).slideToggle('normal', function(){
        $(this).find("[id]").each(function() {
          this.id += '_' + id;
        });

        // hooking needed events
        $('#typoSend_'+ id).on('click', { param: id }, submitTypo);
        $('#def_'+ id +' .rep').on('click', { param: id }, prepareTypo);
      });
    }

    return false;
  }

  function submitTypo(evt) {
    var id = evt.data.param;
    var text = $('#typoText_' + id).val();

    $.post(
      wwwRoot + 'ajax/typo.php',
      { definitionId: id, text: text },
      function(data) {
        if (data.success === true) {
          $('#def_' + id).find('.toggleTypoLink').trigger('click');
        } else {
          $('#typoResponse_' + id).removeAttr('hidden');
        }
      });

    return false;
  }

  function prepareTypo(evt) {
    var id = evt.data.param;

    var sel = searchClickedWord(evt, false);
    if (!sel) {
      return;
    }

    var txt = $('#typoText_'+id).val();
    txt += txt ? ' • ' : '';
    $('#typoText_'+id).val(txt + sel);

  }

  function treeFilterChange() {
    $('.tree').stop().slideDown();
    if ($(this).val() != -1) {
      $('.tree:not(.tree-status-' + $(this).val()).stop().slideUp();
    }
  }

  function defFilterChange() {
    var status = $('#defFilterSelect').val();
    var structurable = $('#structurableFilter').is(':checked');

    $('.defWrapper').each(function() {
      var show = true;
      if (((status == 'structured') && $(this).is(':not(.structured)')) ||
          ((status == 'unstructured') && $(this).is('.structured')) ||
          (structurable && !$(this).is('.structurable'))) {
        show = false;
      }

      $(this).toggle(show);
    });
  }

  function associateModalShown() {
    // copy definition ids from checked checkboxes
    var checkboxes = $('input[name="selectedDefIds[]"]:checked');
    var ids = checkboxes.map(function() {return $(this).val(); });
    var idString = ids.get().join();

    $('input[name="associateDefinitionIds"]').val(idString);
    $('#associateEntryIds').select2('open');
  }

  function checkEntryWikiPage() {
    var entryId = $('#entryId').val();
    ifWikiPageExists('Intrare:' + entryId, function() {
      $('#wikiLink')
        .attr('title', 'intrarea are o pagină wiki')
        .toggleClass('btn-outline-secondary btn-warning');
    });
  }

  init();
});
