/**
 * Admin JS
 */
(function($) {
  'use strict';

  // For notifications
  function notification(text, type) {
    new Noty({
      text: text,
      type: type,
      theme: 'sunset',
      timeout: 4000,
      progressBar: false
    }).show();
  }

  // Initialize switches
  var elems = Array.prototype.slice.call(document.querySelectorAll('.switch'));

  elems.forEach(function(html) {
    var switchery = new Switchery(html);
  });

  $('.import-article').on('click', function(e) {
    e.preventDefault();

    var article_id, table, parent_tr, first_td;

    // Article ID for fetching specific article
    article_id = $(this).data('id');

    // Main table
    table = $('.wp-list-table');

    // Find parent tr and td
    parent_tr = $(this).parent().parent();
    first_td = parent_tr.find('.column-image');

    // Fetch info via an AJAX call
    $.ajax({
      type: 'get',
      url: ajaxurl,
      data: {
        action: kafkaiwp_admin_l10n.prefix + 'fetch_article',
        article_id: article_id,
        _nonce: kafkaiwp_admin_l10n.nonce
      },
      beforeSend: function() {
        table.append('<div class="' + kafkaiwp_admin_l10n.prefix + 'ajax_overlay"></div>');
        first_td.append('<div class="' + kafkaiwp_admin_l10n.prefix + 'loading"><span class="spinner is-active"></span></div>');
      },
      error: function(xhr, status, error) {
        if(status === 'error') {
          $('#inline-article-container .error-response').html(kafkaiwp_admin_l10n.error_text);
        }
      }
    }).done(function(data) {
      // Remove added elements for blocker
      table.find('.' + kafkaiwp_admin_l10n.prefix + 'ajax_overlay').remove();
      first_td.find('.' + kafkaiwp_admin_l10n.prefix + 'loading').remove();

      if(data.code === 'success') {
        console.log(data.response);
        var article_body = data.response.body;

        // Turn line breaks into <br> and add HTML to article body
        article_body = article_body.replace(/(?:\r\n|\r|\n)/g, '<br>');
        $('#inline-article-container .article-body').html(article_body);

        // Open lightbox
        tb_show(kafkaiwp_admin_l10n.window_title, '#TB_inline?height=604&width=772&inlineId=inline-article-container&modal=true');
      } else {
        notification(data.error, 'error');
      }
    });
  });

  // Close modal
  $('.single-article-container .modal-close, #TB_overlay').on('click', function(e) {
    e.preventDefault();

    tb_remove();
  });
})(jQuery);
