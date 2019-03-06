// The "Upload" button
$('#upload_background_button').click(function() {
    wp.media.editor.open(button);
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    wp.media.editor.send.attachment = function(props, attachment) {
        //console.log(attachment);
        $('#background_preview').attr('src',attachment.url);
        $('#voucher_background').val(attachment.id);
        //$(button).parent().prev().attr('src', attachment.url);
        //$(button).prev().val(attachment.id);
        wp.media.editor.send.attachment = send_attachment_bkp;
    }
    return false;
});

// The "Remove" button (remove the value from input type='hidden')
$('#remove_background_button').click(function() {
    var answer = confirm('Are you sure?');
    if (answer == true) {
        //var src = $(this).parent().prev().attr('data-src');
        //$(this).parent().prev().attr('src', src);
        //$(this).prev().prev().val('');
        $('#background_preview').attr('src','');
        $('#voucher_background').val('');
    }
    return false;
});

$('#upload_logo_button').click(function() {
    wp.media.editor.open(button);
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    wp.media.editor.send.attachment = function(props, attachment) {
        //console.log(attachment);
        $('#logo_preview').attr('src',attachment.url);
        $('#voucher_logo').val(attachment.id);
        //$(button).parent().prev().attr('src', attachment.url);
        //$(button).prev().val(attachment.id);
        wp.media.editor.send.attachment = send_attachment_bkp;
    }
    return false;
});

// The "Remove" button (remove the value from input type='hidden')
$('#remove_logo_button').click(function() {
    var answer = confirm('Are you sure?');
    if (answer == true) {
        //var src = $(this).parent().prev().attr('data-src');
        //$(this).parent().prev().attr('src', src);
        //$(this).prev().prev().val('');
        $('#logo_preview').attr('src','');
        $('#logo_background').val('');
    }
    return false;
});