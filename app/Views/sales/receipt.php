<?php
/**
 * @var int    $sale_id_num
 * @var bool   $print_after_sale
 * @var array  $config
 * @var array  $cart
 * @var float  $total
 * @var float  $subtotal
 * @var array  $taxes
 * @var string $customer_phone
 * @var string $customer_email
 * @var string $customer
 * @var string $transaction_time
 * @var string $sale_id
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

<?php
// ── Build WhatsApp message ──────────────────────────────────────────────────
$wa_phone_raw = $customer_phone ?? '';
$wa_phone     = preg_replace('/[^0-9+]/', '', $wa_phone_raw);

// Add India country code if local number (10 digits, no country code)
if (strlen($wa_phone) === 10 && substr($wa_phone, 0, 1) !== '+') {
    $wa_phone = '91' . $wa_phone;
}

$company      = $config['company'] ?? 'Store';
$currency     = $config['currency_symbol'] ?? '₹';
$cust_name    = $customer ?? 'Valued Customer';
$sale_ref     = $sale_id ?? ('POS ' . $sale_id_num);
$sale_time    = $transaction_time ?? date('d M Y, h:i A');

// Build itemized list
$items_text = '';
if (!empty($cart)) {
    foreach ($cart as $item) {
        if (empty($item['name'])) continue;
        $qty   = isset($item['quantity_purchased']) ? (int)$item['quantity_purchased'] : 1;
        $amt   = isset($item['discounted_total'])   ? $item['discounted_total']
               : (isset($item['total'])             ? $item['total'] : 0);
        $items_text .= sprintf("  • %s x%d — %s%.2f\n", $item['name'], $qty, $currency, (float)$amt);
    }
}

$grand_total = isset($total) ? number_format((float)$total, 2) : '0.00';

// Tax summary (GST etc.)
$tax_text = '';
if (!empty($taxes)) {
    foreach ($taxes as $tax) {
        if (!empty($tax['name']) && isset($tax['amount']) && $tax['amount'] > 0) {
            $tax_text .= sprintf("  %s: %s%.2f\n", $tax['name'], $currency, (float)$tax['amount']);
        }
    }
}

$bill_msg = "🧾 *Bill from {$company}*\n";
$bill_msg .= "━━━━━━━━━━━━━━━━━━\n";
$bill_msg .= "👤 Customer: {$cust_name}\n";
$bill_msg .= "🆔 Sale: {$sale_ref}\n";
$bill_msg .= "🕐 Date: {$sale_time}\n";
$bill_msg .= "━━━━━━━━━━━━━━━━━━\n";
$bill_msg .= "*Items:*\n";
$bill_msg .= $items_text ?: "  (items not listed)\n";
$bill_msg .= "━━━━━━━━━━━━━━━━━━\n";
if ($tax_text) {
    $bill_msg .= $tax_text;
}
$bill_msg .= "💰 *Total: {$currency}{$grand_total}*\n";
$bill_msg .= "━━━━━━━━━━━━━━━━━━\n";
$bill_msg .= "📄 *Your E-Receipt:* {$pdf_link}\n";
$bill_msg .= "━━━━━━━━━━━━━━━━━━\n";
$bill_msg .= "🙏 Thank you for shopping at *{$company}*!\n";
$bill_msg .= "We look forward to seeing you again. 😊";

$points_text = '';
if (!empty($customer_rewards) && isset($customer_rewards['points'])) {
    $pts = (int)$customer_rewards['points'];
    if ($pts > 0) {
        $points_text  = "\n━━━━━━━━━━━━━━━━━━\n";
        $points_text .= "🏅 *Reward Points Balance: {$pts} pts*\n";
        if ($pts >= 1000) {
            $points_text .= "🎉 You've unlocked a 5% discount on your next purchase!";
        } else {
            $points_text .= (1000 - $pts) . " more points to unlock 5% off!";
        }
    }
}

$thank_msg  = "Dear " . strtoupper($cust_name) . ",\n\n";
$thank_msg .= "Thank you for shopping at {$company}.\n\n";
$thank_msg .= "We appreciate you for being our valued customer and look forward to serving you again.\n\n";
$thank_msg .= "📄 *Your E-Receipt:* {$pdf_link}\n\n";
$thank_msg .= "Team {$company}.";

$wa_bill_url  = !empty($wa_phone) ? 'https://wa.me/' . $wa_phone . '?text=' . rawurlencode($bill_msg . $points_text) : '';
$wa_thank_url = !empty($wa_phone) ? 'https://wa.me/' . $wa_phone . '?text=' . rawurlencode($thank_msg . $points_text) : '';
?>

<?= view('partial/print_receipt', ['print_after_sale' => $print_after_sale, 'selected_printer' => 'receipt_printer']) ?>

<!-- ── Control Buttons ──────────────────────────────────────────────────── -->
<div class="print_hide" id="control_buttons" style="text-align: right; margin-bottom: 8px;">
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
    <button class="btn btn-sm" id="show_whatsapp_button"
            data-toggle="modal" data-target="#whatsappModal"
            style="background-color:#25D366; border-color:#128C7E; color:#fff; font-weight:600;">
        <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
             style="width:15px;height:15px;vertical-align:middle;margin-right:4px;" alt="WA">
        Send on WhatsApp<?php if (empty($wa_phone)): ?> 📲<?php endif; ?>
    </button>
    <?= anchor('sales', '<span class="glyphicon glyphicon-shopping-cart">&nbsp;</span>' . lang('Sales.register'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_sales_button']) ?>
    <?php
    $employee = model(Employee::class);
    if ($employee->has_grant('reports_sales', session('person_id'))): ?>
        <?= anchor('sales/manage', '<span class="glyphicon glyphicon-list-alt">&nbsp;</span>' . lang('Sales.takings'), ['class' => 'btn btn-info btn-sm', 'id' => 'show_takings_button']) ?>
    <?php endif; ?>
</div>

<!-- ── WhatsApp Modal ───────────────────────────────────────────────────── -->
<div class="modal fade" id="whatsappModal" tabindex="-1" role="dialog" aria-labelledby="whatsappModalLabel">
    <div class="modal-dialog" role="document" style="max-width:500px;">
        <div class="modal-content" style="border-radius:12px; overflow:hidden; border:none; box-shadow:0 8px 40px rgba(0,0,0,0.18);">

            <!-- Header -->
            <div class="modal-header" style="background: linear-gradient(135deg,#128C7E,#25D366); border:none; padding:18px 24px;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;opacity:1;font-size:22px;">&times;</button>
                <h4 class="modal-title" id="whatsappModalLabel" style="color:#fff;font-weight:700;margin:0;">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                         style="width:22px;height:22px;vertical-align:middle;margin-right:8px;" alt="">
                    Send via WhatsApp
                </h4>
                <?php if (!empty($wa_phone)): ?>
                <p style="color:rgba(255,255,255,0.88); margin:4px 0 0; font-size:13px;">
                    <?= esc($cust_name) ?> &nbsp;·&nbsp; <strong>+<?= esc($wa_phone) ?></strong>
                </p>
                <?php else: ?>
                <div style="margin-top:10px;">
                    <p style="color:rgba(255,255,255,0.9);font-size:12px;margin:0 0 5px;">No phone on record — enter number to send:</p>
                    <div class="input-group" style="max-width:320px;">
                        <span class="input-group-addon" style="background:#fff;border:none;">🇮🇳 +91</span>
                        <input type="tel" id="wa_manual_phone" class="form-control" placeholder="10-digit mobile number"
                               maxlength="10" style="border:none;border-radius:0 6px 6px 0;font-size:14px;">
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tabs -->
            <div class="modal-body" style="padding:20px 24px 10px;">
                <ul class="nav nav-tabs" id="waTabs" style="margin-bottom:14px;border-bottom:2px solid #e0e0e0;">
                    <li class="active">
                        <a href="#tab-bill" data-toggle="tab" style="font-weight:600;color:#128C7E;">
                            🧾 Bill
                        </a>
                    </li>
                    <li>
                        <a href="#tab-thankyou" data-toggle="tab" style="font-weight:600;color:#128C7E;">
                            🙏 Thank You
                        </a>
                    </li>
                    <li>
                        <a href="#tab-custom" data-toggle="tab" style="font-weight:600;color:#128C7E;">
                            ✏️ Custom
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <!-- Bill Tab -->
                    <div class="tab-pane active" id="tab-bill">
                        <p style="font-size:12px;color:#888;margin-bottom:6px;">Preview (editable):</p>
                        <textarea id="wa_bill_text" class="form-control"
                                  style="height:200px;font-family:monospace;font-size:12px;border-radius:8px;border:1px solid #c3e6cb;background:#f0fff4;resize:vertical;"
                                  ><?= htmlspecialchars($bill_msg) ?></textarea>
                        <a id="btn_send_bill" href="<?= esc($wa_bill_url) ?>" target="_blank"
                           class="btn btn-block" onclick="triggerPdfDownload();"
                           style="margin-top:12px;background:#25D366;border-color:#128C7E;color:#fff;font-weight:700;border-radius:8px;padding:10px;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                                 style="width:17px;height:17px;vertical-align:middle;margin-right:5px;" alt="">
                            Open WhatsApp &amp; Send Bill
                        </a>
                    </div>

                    <!-- Thank You Tab -->
                    <div class="tab-pane" id="tab-thankyou">
                        <p style="font-size:12px;color:#888;margin-bottom:6px;">Preview (editable):</p>
                        <textarea id="wa_thank_text" class="form-control"
                                  style="height:200px;font-family:monospace;font-size:12px;border-radius:8px;border:1px solid #c3e6cb;background:#f0fff4;resize:vertical;"
                                  ><?= htmlspecialchars($thank_msg) ?></textarea>
                        <a id="btn_send_thank" href="<?= esc($wa_thank_url) ?>" target="_blank"
                           onclick="triggerPdfDownload();"
                           class="btn btn-block"
                           style="margin-top:12px;background:#25D366;border-color:#128C7E;color:#fff;font-weight:700;border-radius:8px;padding:10px;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                                 style="width:17px;height:17px;vertical-align:middle;margin-right:5px;" alt="">
                            Open WhatsApp &amp; Send Thank You
                        </a>
                    </div>

                    <!-- Custom Tab -->
                    <div class="tab-pane" id="tab-custom">
                        <p style="font-size:12px;color:#888;margin-bottom:6px;">Write your own message:</p>
                        <textarea id="wa_custom_text" class="form-control"
                                  placeholder="Type your custom message here..."
                                  style="height:200px;font-size:13px;border-radius:8px;resize:vertical;"
                                  ></textarea>
                        <a id="btn_send_custom" href="#" target="_blank"
                           onclick="return buildCustomLink(this)"
                           class="btn btn-block"
                           style="margin-top:12px;background:#25D366;border-color:#128C7E;color:#fff;font-weight:700;border-radius:8px;padding:10px;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/6/6b/WhatsApp.svg"
                                 style="width:17px;height:17px;vertical-align:middle;margin-right:5px;" alt="">
                            Open WhatsApp &amp; Send Custom Message
                        </a>
                    </div>
                </div>
            </div>

        <!-- Footer -->
            <div class="modal-footer" style="border:none;padding:10px 24px 18px;text-align:center;background:#f9f9f9;border-top:1px solid #eee;">
                <p style="color:#666;font-size:12px;margin:0;font-weight:600;">
                    💡 How to send the PDF file:
                </p>
                <p style="color:#aaa;font-size:11px;margin:4px 0 0;">
                    WhatsApp prevents websites from attaching files automatically. When you click send, the <b>PDF will download to your computer</b>. Simply drag and drop the downloaded PDF directly into the WhatsApp chat!
                </p>
            </div>

        </div>
    </div>
</div>

<script>
var WA_PHONE = '<?= esc($wa_phone) ?>';
var PDF_URL  = '<?= site_url("sales/downloadPdf/$sale_id_num") ?>';
var HAS_PHONE = WA_PHONE.length > 0;

function getPhone() {
    if (HAS_PHONE) return WA_PHONE;
    var el = document.getElementById('wa_manual_phone');
    var num = el ? el.value.trim().replace(/[^0-9]/g,'') : '';
    if (num.length === 10) return '91' + num;
    if (num.length === 12) return num;
    return null;
}

function triggerPdfDownload() {
    var iframe = document.createElement('iframe');
    iframe.style.display = 'none';
    iframe.src = PDF_URL;
    document.body.appendChild(iframe);
    setTimeout(function(){ document.body.removeChild(iframe); }, 3000);
}

function updateLink(btnId, textareaId) {
    var phone = getPhone();
    if (!phone) return;
    var msg = document.getElementById(textareaId).value;
    var url = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
    var btn = document.getElementById(btnId);
    btn.href = url;
    // Add onclick trigger to download PDF
    btn.onclick = function() {
        triggerPdfDownload();
    };
}

function buildCustomLink(el) {
    var phone = getPhone();
    if (!phone) { alert('Please enter a valid 10-digit mobile number above.'); return false; }
    var msg = document.getElementById('wa_custom_text').value.trim();
    if (!msg) { alert('Please type a message first.'); return false; }
    el.href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(msg);
    triggerPdfDownload();
    return true;
}

$(document).ready(function() {
    // Live textarea preview
    $('#wa_bill_text').on('input', function()  { updateLink('btn_send_bill',   'wa_bill_text');   });
    $('#wa_thank_text').on('input', function() { updateLink('btn_send_thank', 'wa_thank_text'); });

    // When manual phone is updated, refresh all send links
    $('#wa_manual_phone').on('input', function() {
        updateLink('btn_send_bill',   'wa_bill_text');
        updateLink('btn_send_thank', 'wa_thank_text');
    });
});
</script>

<?= view('sales/' . $config['receipt_template']) ?>

<?= view('partial/footer') ?>
