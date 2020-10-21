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
        
      }
    }).done(function(data) {
      if(data.code === 'success') {
        console.log(data);
      }
    });
  });
})(jQuery);
