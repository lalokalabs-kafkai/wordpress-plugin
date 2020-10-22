/**
 * Admin JS
 */
(function($) {
  'use strict';

  $('.import-article').on('click', function(e) {
    e.preventDefault();

    // Article ID for fetching specific article
    var article_id = $(this).data('id');

    // Fetch info via an AJAX call
    $.ajax({
      type: 'get',
      url: ajaxurl,
      data: {
        action: kafkaiwp_admin_l10n.prefix + 'fetch_article',
        article_id: article_id,
        _nonce: kafkaiwp_admin_l10n.nonce
      },
      error: function(xhr, status, error) {
        if(status === 'error') {
          $('#inline-article-container .error-response').html(kafkaiwp_admin_l10n.error_text);
        }
      }
    }).done(function(data) {
      if(data.code === 'success') {
        $('#inline-article-container .article-body').html(data.response.body);

        // Open lightbox
        tb_show(kafkaiwp_admin_l10n.window_title, '#TB_inline?height=600&width=800&inlineId=inline-article-container&modal=true');
      } else {
        $('#inline-article-container .error-response').html(data.error);
      }
    });
  });
})(jQuery);
