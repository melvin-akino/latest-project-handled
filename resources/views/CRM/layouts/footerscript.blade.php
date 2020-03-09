
<img id="img-holder" class="img-thumb drift-demo-trigger"  width="300" height="150" hidden>
<script src="{{ asset("js/viewer.js", env('IS_HTTPS', false)) }}"></script>
<script>


    $('.btntoggle').click(function (e) {

        var form = $('#modal-details');
        var tr = $(this).closest('tr');
        var html_string = '';
        var has_attachment = false;
        jQuery($(tr)).find('td').each(function (td) {
            var key = Object.keys(jQuery($(this)).data())[0];
            var value = $(this).data(key);
            var label = "";

            if (key == "bankInfoId") {
                return true;
            }

            var wallet_withdrawal_id = 0;
            if (key != undefined) {
                $.each(key.split('_'), function (key, value) {
                    label += value.toUpperCase() + " ";
                });
                html_string += "<div class='row' style='margin: 5px;'>";
                if (label.trim() == "ATTACHMENT" || label.trim() == "WALLET WITHDRAWAL ID") {
                    var additional_button = "";
                    if (label.trim() == "WALLET WITHDRAWAL ID") {
                        id = value;
                        value = $(this).attr("data-attachment");
                        label = "ATTACHMENT";
                        //add the checking if the user is allowed to attached attachment
                        additional_button = "<span class=\"pull-right col-sm-2\">\n" +

                            "                                                    <span class=\"pull-right\">\n" +
                            "                                                <button type=\"button\" role=\"button\" class=\"btn btn-default btn-xs\"\n" +
                            "                                                        onclick=\"upload(this);\">\n" +
                            "                                                    <i class=\"fa fa-edit\"></i>\n" +
                            "                                                </button>\n" +
                            "                                                <input id='attachment' type=\"file\" style=\"display:none\"\n" +
                            "                                                       data-wallet-withdrawal-id=\"" + id + "\"\n" +
                            "                                                       onchange='upload_attachment(this)' class=\"attachments\">\n" +
                            "                                              </span>\n" +
                            "                                                    </span>";
                    }
                    html_string += " <label for=\"edit-currency-name-input\" class=\"col-sm-3 control-label\">" + (label.trim() == "WALLET WITHDRAWAL ID" ? "ATTACHMENT" : label.trim()) + "</label>";
                    html_string += "  <div class=\"col-sm-9\">";

                    if (value == "") {
                        html_string += "<input type=\"text\" class=\"form-control @if(Auth::guard('crm')->user()->canAccess('edit', 'wallet.withdrawal.set_attachments')) col-sm-10 @else col-sm-12 @endif\" value='NO ATTACHMENT' disabled></span>";
                        html_string += additional_button;
                    }
                    else {
                        html_string += "<input type=\"button\" class=\"btn btn-primary @if(Auth::guard('crm')->user()->canAccess('edit', 'wallet.withdrawal.set_attachments')) col-sm-10 @else col-sm-12 @endif\" value='WITH ATTACHMENT'  data-toggle=\"tooltip\" data-placement=\"top\"" +
                            "  title=\"<img class='img-responsive' src='" + value + "'>\" onclick='$(\"#img-attachment\").click();'>";
                        html_string += additional_button;
                        html_string += "<img id=\"img-attachment\" class=\"img-thumb drift-demo-trigger\"  width=\"300\" height=\"150\" src=\"" + value + "\" hidden>";
                        has_attachment = true;
                    }

                } else {
                    html_string += " <label for=\"edit-currency-name-input\" class=\"col-sm-3 control-label\">" + (label.trim() == "WALLET WITHDRAWAL ID" ? "ATTACHMENT" : label.trim()) + "</label>";
                    html_string += "  <div class=\"col-sm-9\">";
                    if ((value.toString()).trim().length >= 40) {
                        html_string += "<textarea type=\"text\" cols='30' style=\"resize: none; overflow-y: scroll\" class=\"form-control\" disabled> " + value.trim() + "</textarea>";
                    } else
                        html_string += "<input type=\"text\" class=\"form-control\" value='" + (value.toString()).trim() + "' disabled>";
                }

                html_string += "</div>";
                html_string += "</div>";
            }
        });
        $('#details_body').html(html_string);
        if (has_attachment) {
            var $image = $('#img-attachment');
            $image.viewer({
                inline: false,
                toolbar: false,
                fullscreen: false,
                viewed: function () {
                    $image.viewer('maxZoomRatio', .5);
                }
            });

            // Get the Viewer.js instance after initialized
            var viewer = $image.data('viewer');

            // View a list of images

            $('[data-toggle="tooltip"]').tooltip({
                    animated: 'fade',
                    placement: 'bottom',
                    html: true
                }
            );
        }

        form.modal('show')
    });


    $('[data-toggle="tooltip"]').tooltip({
            animated: 'fade',
            placement: 'bottom',
            html: true
        }
    );

    function display_preview(src){
        document.getElementById("img-holder").src =src;
        var $image = $('#img-holder');
        $image.viewer({
            inline: false,
            toolbar: false,
            fullscreen: false,
            viewed: function () {
                $image.viewer('maxZoomRatio', .5);
            }
        });
        var viewer = $image.data('viewer');
        $('#img-holder').click();
    }


    function setModalMaxHeight(element) {
        this.$element     = $(element);
        this.$content     = this.$element.find('.modal-content');
        var borderWidth   = this.$content.outerHeight() - this.$content.innerHeight();
        var dialogMargin  = $(window).width() < 768 ? 20 : 60;
        var contentHeight = $(window).height() - (dialogMargin + borderWidth);
        var headerHeight  = this.$element.find('.modal-header').outerHeight() || 0;
        var footerHeight  = this.$element.find('.modal-footer').outerHeight() || 0;
        var maxHeight     = contentHeight - (headerHeight + footerHeight);

        this.$content.css({
            'overflow': 'hidden'
        });

        this.$element
            .find('.modal-body').css({
            'max-height': maxHeight,
            'overflow-y': 'auto'
        });
    }

    $('.modal').on('show.bs.modal', function() {
        $(this).show();
        setModalMaxHeight(this);
    });

    $(window).resize(function() {
        if ($('.modal.in').length != 0) {
            setModalMaxHeight($('.modal.in'));
        }
    });

</script>
