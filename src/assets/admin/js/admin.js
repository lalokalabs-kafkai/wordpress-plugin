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

  // Fetching article for viewing and initialize importing
  $('.fetch-article').on('click', function(e) {
    e.preventDefault();

    var article_id, table, parent_tr, first_td, article_body, button;

    // Article ID for fetching specific article
    article_id = $(this).data('id');

    // Main table
    table = $('.wp-list-table');

    // Import form button
    button = $('#' + kafkaiwp_admin_l10n.prefix + 'article_import');

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
        // Ensure button is not disabled
        button.prop('disabled', false);
        button.val(kafkaiwp_admin_l10n.window_title);

        table.append('<div class="' + kafkaiwp_admin_l10n.prefix + 'ajax_overlay"></div>');
        first_td.append('<div class="' + kafkaiwp_admin_l10n.prefix + 'loading"><span class="spinner is-active"></span></div>');
      },
      error: function(xhr, status, error) {
        if(status === 'error') {
          notification(kafkaiwp_admin_l10n.error_text, 'error');

          table.find('.' + kafkaiwp_admin_l10n.prefix + 'ajax_overlay').remove();
          first_td.find('.' + kafkaiwp_admin_l10n.prefix + 'loading').remove();
        }
      }
    }).done(function(data) {
      // Remove added elements for blocker
      table.find('.' + kafkaiwp_admin_l10n.prefix + 'ajax_overlay').remove();
      first_td.find('.' + kafkaiwp_admin_l10n.prefix + 'loading').remove();

      if(data.code === 'success') {
        article_body = data.response.body;

        // Add ID to hidden field to be used later for import
        $('#' + kafkaiwp_admin_l10n.prefix + 'article_id').val(data.response.id);

        // Turn line breaks into <br> and add HTML to article body
        article_body = article_body.replace(/(?:\r\n|\r|\n)/g, '<br>');
        $('#' + kafkaiwp_admin_l10n.prefix + 'inline-article-container .article-body').html(article_body);
        $('#' + kafkaiwp_admin_l10n.prefix + 'inline-article-container .article-title').html(data.response.title);

        // Article meta info
        $('.article-meta-chars').html(data.response.charCount);
        $('.article-meta-words').html(data.response.wordCount);

        // Open lightbox
        tb_show(kafkaiwp_admin_l10n.window_title, '#TB_inline?height=604&width=772&inlineId=' + kafkaiwp_admin_l10n.prefix + 'inline-article-container&modal=true');
      } else {
        notification(data.error, 'error');
      }
    });
  });

  // Initialize article import
  $(document).on('submit', '#' + kafkaiwp_admin_l10n.prefix + 'import_form', function(e) {
    e.preventDefault();

    var formData, button, article_id, keyword, image, video;

    // For fetching the article ID
    formData = new FormData(document.getElementById(kafkaiwp_admin_l10n.prefix + 'import_form'));

    // Import form button
    button = $('#' + kafkaiwp_admin_l10n.prefix + 'article_import');

    // Move if article_id and keyword are not empty
    article_id = formData.get(kafkaiwp_admin_l10n.prefix + 'article_id');
    keyword = formData.get(kafkaiwp_admin_l10n.prefix + 'article-import-keyword');
    image = formData.get(kafkaiwp_admin_l10n.prefix + 'article-import-image');
    video = formData.get(kafkaiwp_admin_l10n.prefix + 'article-import-video');

    if(!article_id) {
      notification(kafkaiwp_admin_l10n.missing_id, 'error');
      return;
    }

    // We only need keyword for image or video
    if(image === 'on' || video === 'on') {
      if(!keyword) {
        notification(kafkaiwp_admin_l10n.missing_keyword, 'error');
        return;
      }
    }

    // Replace null with "off"
    if(image === null) {
      image = 'off';
    }

    if(video === null) {
      video = 'off';
    }

    // We do everything via AJAX (very similar to fetching article for viewing)
    $.ajax({
      type: 'get',
      url: ajaxurl,
      data: {
        action: kafkaiwp_admin_l10n.prefix + 'import_article',
        article_id: article_id,
        article_image: image,
        article_video: video,
        article_keyword: keyword,
        article_author: formData.get(kafkaiwp_admin_l10n.prefix + 'article-import-author'),
        article_status: formData.get(kafkaiwp_admin_l10n.prefix + 'article-import-status'),
        _nonce: kafkaiwp_admin_l10n.nonce
      },
      beforeSend: function() {
        button.prop('disabled', true);
        button.val(kafkaiwp_admin_l10n.importing);
      },
      error: function(xhr, status, error) {
        if(status === 'error') {
          notification(kafkaiwp_admin_l10n.error_text, 'error');

          button.prop('disabled', false);
          button.val(kafkaiwp_admin_l10n.window_title);
        }
      }
    }).done(function(data) {
      if(data.code === 'success') {
        notification(data.response, 'success');
        button.val(kafkaiwp_admin_l10n.import_done);
      } else {
        notification(data.error, 'error');

        button.prop('disabled', false);
        button.val(kafkaiwp_admin_l10n.window_title);
      }
    });
  });

  // Close modal
  $(document).on('click', '.modal-close, .TB_overlayBG', function(e) {
    e.preventDefault();

    tb_remove();
  });
})(jQuery);
