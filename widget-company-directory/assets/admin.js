(function($){
  function sortableLists() {
    $("#wcd-available, #wcd-selected").sortable({
      connectWith: ".wcd-list",
      placeholder: "wcd-placeholder"
    }).disableSelection();
  }

  function saveOrder() {
    $('#wcd-save').on('click', function(e){
      e.preventDefault();
      var listId = $(this).data('list');
      var ids = [];
      $('#wcd-selected li').each(function(){ ids.push( parseInt($(this).data('id'), 10) ); });

      $('#wcd-status').text('Saving...');
      $.post(WCDAdmin.ajaxUrl, {
        action: 'wcd_save_list',
        nonce: WCDAdmin.nonce,
        list_id: listId,
        ids: ids
      }).done(function(resp){
        if (resp && resp.success) {
          $('#wcd-status').text('Saved (' + resp.data.count + ')');
        } else {
          $('#wcd-status').text('Error');
        }
      }).fail(function(){
        $('#wcd-status').text('Error');
      });
    });
  }

  // Rename current list
  function bindRename() {
    $('#wcd-rename-list').on('click', function(e){
      e.preventDefault();
      var listId = $(this).data('list');
      var title  = $('#wcd-list-name').val().trim();
      if (!title) { $('#wcd-name-status').text('Enter a name'); return; }

      $('#wcd-name-status').text('Saving...');
      $.post(WCDAdmin.ajaxUrl, {
        action: 'wcd_rename_list',
        nonce: WCDAdmin.nonce,
        list_id: listId,
        title: title
      }).done(function(resp){
        if (resp && resp.success) {
          $('#wcd-name-status').text('Saved');
          var $opt = $('#wcd-list-select option[value="'+listId+'"]');
          if ($opt.length) $opt.text(title);
        } else {
          $('#wcd-name-status').text('Error');
        }
      }).fail(function(){
        $('#wcd-name-status').text('Error');
      });
    });
  }

  // Create a new list
  function bindCreate() {
    $('#wcd-create-list').on('click', function(e){
      e.preventDefault();
      var title = window.prompt('New list name:');
      if (!title) return;

      $('#wcd-status').text('Creating...');
      $.post(WCDAdmin.ajaxUrl, {
        action: 'wcd_create_list',
        nonce: WCDAdmin.nonce,
        title: title.trim()
      }).done(function(resp){
        if (resp && resp.success) {
          var id = resp.data.id;
          // Robust redirect (works with subdirs/custom admin)
          window.location = WCDAdmin.adminBase + 'admin.php?page=wcd-lists&list_id=' + id;
        } else {
          $('#wcd-status').text('Error creating list');
        }
      }).fail(function(){
        $('#wcd-status').text('Error creating list');
      });
    });
  }

  // Auto-submit when changing the list selector
  function bindSelectAutoLoad() {
    $('#wcd-list-select').on('change', function(){
      $(this).closest('form').trigger('submit');
    });
  }

  $(function(){
    sortableLists();
    saveOrder();
    bindRename();
    bindCreate();
    bindSelectAutoLoad();
  });
})(jQuery);
