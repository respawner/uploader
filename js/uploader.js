$('#files').fileinput({
  maxFileSize: 2097152,
  maxFilesNum: 10,
  previewFileType: 'any',
  mainTemplate: '<div class="input-group {class}">{caption}<div class="input-group-btn">{remove}{upload}{browse}</div></div>{preview}'
});

$(document).ready(function() {
  // Close the alert bar
  $('.close').click(function() {
    $('.alert').slideUp();
  });

  // Popover for help
  $('.popover-dismiss').popover({
    trigger: 'focus'
  });

  // Trigger error message if needed
  if (typeof noupload !== 'undefined') {
    $('#error-text').html('Sorry the upload <strong>'
                          + noupload + '</strong> does not exist :(');
    $('.alert').slideDown();
  }

  if (typeof nofile !== 'undefined') {
    $('#error-text').html('Sorry the file <strong>'
                          + nofile + '</strong> does not exist :(');
    $('.alert').slideDown();
  }
});

$('form').ajaxForm({
  beforeSubmit: function() {
    $('#expiration').attr('disabled', '');
    $('#files').fileinput('disable');
    $('.loading').removeClass('hide');
  },
  uploadProgress: function(event, position, total, percent) {
    $('#progress').css('width', percent+'%').html(percent+'%');
  },
  success: function(response) {
    $('#expiration').removeAttr('disabled');
    $('#files').fileinput('enable');
    $('.loading').addClass('hide');
    $('#progress').css('width', '0%').html('0%');

    var result = $.parseJSON(response);

    if (result.error) {
      $('#files').fileinput('reset');
      $('#error-text').html(result.error);
      $('.alert').slideDown();
    } else {
      var url = document.URL;
      var last = url.substr(-1);

      if (last != '/') {
        url += '/' + result.success;
      } else {
        url += result.success;
      }

      $(location).attr('href', url);
    }
  }
});
