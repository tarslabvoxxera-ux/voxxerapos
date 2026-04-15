<?php
/**
 * @var int $sale_id_num
 * @var bool $print_after_sale
 * @var array $config
 */

use App\Models\Employee;

?>

<?= view('partial/header') ?>

<?php
if (isset($error_message)) {
    echo '<div class="alert alert-dismissible alert-danger">' . $error_message . '</div>';
    exit;
}
?>

<?php if (!empty($customer_email)): ?>
    <script type="text/javascript">
        $(document).ready(function() {
            var send_email = function() {
                $.get('<?= site_url() . esc("/sales/sendPdf/$sale_id_num/receipt") ?>',
                    function(response) {
                        $.notify({
                            message: response.message
                        }, {
                            type: response.success ? 'success' : 'danger'
                        })
                    }, 'json'
                );
            };

            $("#show_email_button").click(send_email);

            <?php if (!empty($email_receipt)): ?>
                send_email();
            <?php endif; ?>
        });
    </script>
<?php endif; ?>

<?= view('partial/print_receipt', ['print_after_sale' => $print_after_sale, 'selected_printer' => 'receipt_printer']) ?>

<div class="print_hide" id="control_buttons" style="text-align: right;">
    <a href="javascript:printdoc();">
        <div class="btn btn-info btn-sm" id="show_print_button"><?= '<span class="glyphicon glyphicon-print">&nbsp;</span>' . lang('Common.print') ?></div>
    </a>
    <?php if (!empty($customer_email)): ?>
        <a href="javascript:void(0);">
            <div class="btn btn-info btn-sm" id="show_email_button"><?= '<span class="glyphicon glyphicon-envelope">&nbsp;</span>' . lang('Sales.send_receipt') ?></div>
        </a>
    <?php endif; ?>
    <a href="<?= site_url("sales/downloadPdf/$sale_id_num") ?>">
        <div class="btn btn-warning btn-sm" id="show_pdf_button">
            <?= '<span class="glyphicon glyphicon-download-alt">&nbsp;</span>Download PDF' ?>
        </div>
    </a>
    <?php if (!empty($customer_phone)): ?>
        <?php
            // Clean phone number (remove non-digits, keep leading + if present)
            $wa_phone = preg_replace('/[^0-9+]/', '', $customer_phone);
            // Example WhatsApp message
            $wa_message = rawurlencode(lang('Sales.receipt') . ' - ' . $config['company'] . "\nThank you for shopping!\nReceipt URL: " . site_url("sales/receipt/$sale_id_num"));
            $wa_url = "https://wa.me/{$wa_phone}?text={$wa_message}";
        ?>
        <a href="<?= $wa_url ?>" target="_blank">
            <div class="btn btn-success btn-sm" id="show_whatsapp_button" style="background-color: #25D366; border-color: #25D366; color: white;">
                <?= '<span class="glyphicon glyphicon-phone">&nbsp;</span>WhatsApp' ?>
            </div>
        </a>
    <?php endif; ?>
    <?= anchor('sales', '<span class="glyphicon glyphicon-shopping-cart">&nbsp;</span>' . lang('Sales.register'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_sales_button']) ?>
    <?php
    $employee = model(Employee::class);
    if ($employee->has_grant('reports_sales', session('person_id'))): ?>
        <?= anchor('sales/manage', '<span class="glyphicon glyphicon-list-alt">&nbsp;</span>' . lang('Sales.takings'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_takings_button']) ?>
    <?php endif; ?>
</div>

<?= view('sales/' . $config['receipt_template']) ?>

<?= view('partial/footer') ?>
