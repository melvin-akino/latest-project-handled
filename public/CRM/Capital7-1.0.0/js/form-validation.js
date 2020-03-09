$(function () {
    assocErr = function (errs, form) {
        clearErr(form);

        $.each(errs, function (key, value) {
            var tags = ['input', 'select', 'textarea'];
            var input = null;

            for (var i = 0; i < tags.length; i++) {
                if (form.find(tags[i] + '[name=' + key + ']').length) {
                    input = form.find(tags[i] + '[name=' + key + ']');
                    break;
                }
            }

            // if (input.prop('type') == 'hidden') {
            //     return true;
            // }

            input.closest('.form-group').addClass('has-error');

            if (errs[key].length > 1) {

                $.each(errs[key], function (key, value) {
                    if (input.parent().hasClass('input-group')) {
                        input.parent().parent().append('<p class="help-block"><strong>' + value + '</strong></p>');
                    } else {
                        input.parent().append('<p class="help-block"><strong>' + value + '</strong></p>');
                    }
                });
            } else {
                if (input.parent().hasClass('input-group')) {
                    input.parent().parent().append('<span class="help-block"><strong>' + value + '</strong></span>');
                } else {
                    input.parent().append('<span class="help-block"><strong>' + value + '</strong></span>');
                }
            }
        });
    };

    clearErr = function (form) {
        form.find('.form-group').removeClass('has-error').find('.help-block').remove();
    };

    precise = function(elem, i) {
        elem.value = Number(elem.value).toFixed(i);
    };
});