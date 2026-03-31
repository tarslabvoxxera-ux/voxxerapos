<?php
/**
 * @var array $config
 */
?>

<script type="text/javascript">
    // Live clock
    var clock_tick = function clock_tick() {
        setInterval('update_clock();', 1000);
    }

    // Start the clock immediately
    clock_tick();

    var update_clock = function update_clock() {
        document.getElementById('liveclock').innerHTML = moment().format("<?= dateformat_momentjs($config['dateformat'] . ' ' . $config['timeformat']) ?>");
    }

    const notify = $.notify;

    $.notify = function(content, options) {
        const message = typeof content === "object" ? content.message : content;
        const sanitizedMessage = DOMPurify.sanitize(message);
        return notify(sanitizedMessage, options);
    };

    $.notifyDefaults({
        placement: {
            align: "<?= esc($config['notify_horizontal_position'], 'js') ?>",
            from: "<?= esc($config['notify_vertical_position'], 'js') ?>"
        }
    });

    var cookie_name = "<?= esc(config('Cookie')->prefix, 'js') . esc(config('Security')->cookieName, 'js') ?>";

    var csrf_token = function() {
        return Cookies.get(cookie_name);
    };

    var csrf_form_base = function() {
        return {
            <?= esc(config('Security')->tokenName, 'js') ?>: function() {
                return csrf_token()
            }
        }
    };

    var setup_csrf_token = function() {
        $('input[name="<?= esc(config('Security')->tokenName, 'js') ?>"]').val(csrf_token());
    };

    var ajax = $.ajax;

    $.ajax = function() {
        var args = arguments[0];
        if (args['type'] && args['type'].toLowerCase() == 'post' && csrf_token()) {
            if (typeof args['data'] === 'string') {
                args['data'] += '&' + $.param(csrf_form_base());
            } else {
                args['data'] = $.extend(args['data'], csrf_form_base());
            }
        }

        return ajax.apply(this, arguments);
    };

    $(document).ajaxComplete(setup_csrf_token);
    $(document).ready(function() {
        $("#logout").click(function(event) {
            event.preventDefault();
            $.ajax({
                url: "<?= site_url('home/logout'); ?>",
                data: {
                    "<?= esc(config('Security')->tokenName, 'js'); ?>": csrf_token()
                },
                success: function() {
                    window.location.href = '<?= site_url(); ?>';
                },
                method: "POST"
            });
        });
    });

    var submit = $.fn.submit;

    $.fn.submit = function() {
        setup_csrf_token();
        submit.apply(this, arguments);
    };

    // Data Safety: Double Confirmation Override
    $(document).ready(function() {
        if (window.table_support && window.table_support.do_action) {
            var original_do_action = window.table_support.do_action;
            window.table_support.do_action = function(url) {
                var self = this;
                if (url.indexOf('delete') !== -1) {
                    dialog_support.confirm(<?= json_encode('Are you sure you want to delete the selected rows?') ?>, function() {
                        // If first confirmation passes, show a second one for critical safety
                        bootstrap_dialog.confirm({
                            title: <?= json_encode('CRITICAL: Second Confirmation Required') ?>,
                            message: <?= json_encode('This action is permanent and cannot be undone. Please confirm once more to proceed with data removal.') ?>,
                            type: bootstrap_dialog.TYPE_DANGER,
                            closable: true,
                            draggable: true,
                            btnOKClass: 'btn-danger',
                            callback: function(result) {
                                if (result) {
                                    var postData = self.selected_ids();
                                    postData['double_confirm'] = 'confirmed';
                                    $.post(url, postData, function(response) {
                                        if (response.success) {
                                            $.notify({ message: response.message }, { type: 'success' });
                                            self.refresh();
                                        } else {
                                            $.notify({ message: response.message }, { type: 'danger' });
                                        }
                                    }, 'json');
                                }
                            }
                        });
                    });
                } else {
                    original_do_action.apply(this, arguments);
                }
            };
        }
    });
</script>
