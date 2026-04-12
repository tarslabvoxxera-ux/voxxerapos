<?php
/**
 * Custom Receipt Template - Portrait Format with CGST/SGST
 * @var array $config
 * @var array $cart
 * @var string $company_info
 * @var string $customer
 * @var string $customer_address
 * @var string $customer_email
 * @var string $customer_info
 * @var int $sale_id
 * @var string $transaction_date
 * @var string $transaction_time
 * @var array $taxes
 * @var float $subtotal
 * @var float $total
 * @var float $amount_due
 * @var array $payments
 * @var string $invoice_number
 */

helper('number');
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt - <?= $invoice_number ?></title>
    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 2mm;
            }
            body {
                margin: 0;
                padding: 0;
                width: 76mm;
                font-size: 11px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .receipt-container {
                padding: 3mm;
                box-shadow: none;
                width: 76mm;
            }
            table { font-size: 10px; }
            .receipt-header h2 { font-size: 16px; }
            .receipt-header p { font-size: 10px; }
            .terms { font-size: 9px; }
            .no-print {
                display: none !important;
            }
        }
        
        @media screen {
            body {
                background: #f0f0f0;
                padding: 20px;
                font-size: 14px;
            }
            .receipt-container {
                width: 80mm;
                padding: 8mm;
            }
        }
        
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            line-height: 1.4;
            margin: 0 auto;
            font-weight: 600;
            color: #000;
        }
        
        .receipt-container {
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 10px;
        }
        
        .receipt-header h2 {
            margin: 0 0 6px 0;
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .receipt-header p {
            margin: 3px 0;
            font-size: 12px;
            line-height: 1.5;
            font-weight: 600;
        }
        
        .invoice-type {
            text-align: center;
            font-size: 16px;
            font-weight: 900;
            margin: 10px 0;
            text-decoration: underline;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        
        .section {
            margin: 10px 0;
        }
        
        .section-title {
            font-weight: 900;
            font-size: 13px;
            border-bottom: 2px solid #000;
            padding-bottom: 4px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 12px;
        }
        
        .info-label {
            font-weight: 800;
            width: 45%;
        }
        
        .info-value {
            width: 55%;
            text-align: right;
            font-weight: 700;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 12px;
        }
        
        table.items-table {
            border: 2px solid #000;
        }
        
        table.items-table th {
            background-color: #000;
            color: #fff;
            border: 2px solid #000;
            padding: 6px 4px;
            text-align: center;
            font-weight: 900;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        table.items-table td {
            border: 1px solid #000;
            padding: 5px 4px;
            text-align: right;
            font-size: 11px;
            font-weight: 700;
        }
        
        table.items-table td:first-child {
            text-align: left;
            font-weight: 600;
        }
        
        .tax-table {
            margin-top: 10px;
            font-size: 11px;
            border: 2px solid #000;
        }
        
        .tax-table th, .tax-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            text-align: right;
            font-weight: 700;
        }
        
        .tax-table th {
            background-color: #000;
            color: #fff;
            font-weight: 900;
            font-size: 10px;
            text-transform: uppercase;
        }
        
        .total-row {
            font-weight: 900;
            font-size: 13px;
            background-color: #f0f0f0;
        }
        
        .total-row td {
            font-weight: 900 !important;
        }
        
        .amount-words {
            margin: 8px 0;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 11px;
            padding: 5px;
            background: #f5f5f5;
            border: 1px solid #000;
        }
        
        .terms {
            margin-top: 12px;
            border-top: 2px solid #000;
            padding-top: 8px;
            font-size: 10px;
            white-space: pre-line;
            font-weight: 600;
        }
        
        .terms-title {
            font-weight: 900;
            text-decoration: underline;
            margin-bottom: 5px;
            font-size: 11px;
            text-transform: uppercase;
        }
        
        .footer {
            text-align: center;
            margin-top: 12px;
            font-weight: 900;
            font-size: 14px;
            border-top: 3px double #000;
            padding-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>

<div class="receipt-container">
<div class="receipt-header">
    <h2><?= esc($config['company']) ?></h2>
    <p><?= nl2br(esc($config['address'])) ?></p>
    <p>Contact: <?= esc($config['phone']) ?></p>
    <?php if (!empty($config['tax_id'])): ?>
    <p><strong>GSTIN No.: <?= esc($config['tax_id']) ?></strong></p>
    <?php endif; ?>
</div>

<div class="invoice-type">RETAIL INVOICE</div>

<div class="section">
    <div class="info-row">
        <span class="info-label">Invoice No:</span>
        <span class="info-value"><?= esc($invoice_number) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Date:</span>
        <span class="info-value"><?= esc($transaction_date) ?></span>
    </div>
    <div class="info-row">
        <span class="info-label">Time:</span>
        <span class="info-value"><?= esc($transaction_time) ?></span>
    </div>
</div>

<?php if (isset($customer) && !empty($customer)): ?>
<div class="section">
    <div class="section-title">Customer Details</div>
    <div class="info-row">
        <span class="info-label">Name:</span>
        <span class="info-value"><?= esc($customer) ?></span>
    </div>
    <?php if (!empty($customer_info)): ?>
    <div class="info-row">
        <span class="info-label">Contact:</span>
        <span class="info-value"><?= esc(strip_tags($customer_info)) ?></span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="section">
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 40%;">Product</th>
                <th style="width: 10%;">Qty</th>
                <th style="width: 20%;">MRP</th>
                <th style="width: 15%;">Disc%</th>
                <th style="width: 25%;">Net Amt</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart as $line => $item): ?>
            <tr>
                <td><?= esc(character_limiter($item['name'], 25)) ?></td>
                <td><?= to_quantity_decimals($item['quantity']) ?></td>
                <td><?= to_currency($item['price']) ?></td>
                <td><?= to_decimals($item['discount']) ?></td>
                <td><?= to_currency($item['discounted_total']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
// Calculate tax breakdown
$taxable_amount = $subtotal;
$cgst_total = 0;
$sgst_total = 0;
$total_tax = 0;

if (!empty($taxes)) {
    foreach ($taxes as $tax) {
        $tax_amount = $tax['sale_tax_amount'];
        $total_tax += $tax_amount;
        // Split equally between CGST and SGST
        $cgst_total += $tax_amount / 2;
        $sgst_total += $tax_amount / 2;
    }
}
?>

<div class="section">
    <table class="tax-table">
        <thead>
            <tr>
                <th>Taxable Amount</th>
                <th>CGST</th>
                <th>SGST</th>
                <th>Total Tax</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><?= to_currency($taxable_amount) ?></td>
                <td><?= to_currency($cgst_total) ?></td>
                <td><?= to_currency($sgst_total) ?></td>
                <td><?= to_currency($total_tax) ?></td>
            </tr>
            <tr class="total-row">
                <td colspan="3">Bill Amount:</td>
                <td><?= to_currency($total) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="amount-words">
    <?php
    $words = '';
    $amount = floor($total);
    if (function_exists('convert_number_to_words')) {
        $words = convert_number_to_words($amount);
    }
    echo $words ? $words . ' ONLY' : '';
    ?>
</div>

<?php if (!empty($payments)): ?>
<div class="section">
    <div class="section-title">Payment Details</div>
    <?php foreach ($payments as $payment_type => $payment): ?>
    <div class="info-row">
        <span class="info-label"><?= esc($payment_type) ?>:</span>
        <span class="info-value"><?= to_currency($payment['payment_amount']) ?></span>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($config['return_policy'])): ?>
<div class="terms">
    <div class="terms-title">Terms & Conditions:</div>
    <?= nl2br(esc($config['return_policy'])) ?>
</div>
<?php endif; ?>

<div class="footer">
    Thank You for Your Visit
</div>

</div><!-- end receipt-container -->

</body>
</html>

