<?php
/**
 * Customer Purchase History Report View
 * @var object $customer
 * @var array $sales
 * @var int $total_purchases
 * @var float $total_amount
 * @var int $total_items
 * @var array $config
 */

helper('number');
?>

<?= view('partial/header') ?>

<div id="page_title">
    Customer Purchase History
</div>

<div class="panel panel-info">
    <div class="panel-heading">
        <h4 class="panel-title">
            <strong><?= esc($customer->first_name . ' ' . $customer->last_name) ?></strong>
            <?php if (!empty($customer->phone_number)): ?>
                &nbsp;|&nbsp; <?= esc($customer->phone_number) ?>
            <?php endif; ?>
            <?php if (!empty($customer->email)): ?>
                &nbsp;|&nbsp; <?= esc($customer->email) ?>
            <?php endif; ?>
        </h4>
    </div>
    <div class="panel-body">
        <div class="row">
            <div class="col-md-4">
                <div class="well well-sm text-center">
                    <h2 style="margin: 0; color: #337ab7;"><?= $total_purchases ?></h2>
                    <small>Total Purchases</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="well well-sm text-center">
                    <h2 style="margin: 0; color: #5cb85c;"><?= to_currency($total_amount) ?></h2>
                    <small>Total Amount Spent</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="well well-sm text-center">
                    <h2 style="margin: 0; color: #f0ad4e;"><?= number_format($total_items) ?></h2>
                    <small>Total Items Bought</small>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-md-6">
                <h4 class="panel-title">Purchase History</h4>
            </div>
            <div class="col-md-6 text-right">
                <button class="btn btn-success btn-sm" onclick="exportToCSV()">
                    <span class="glyphicon glyphicon-download-alt"></span> Export to Excel
                </button>
                <button class="btn btn-default btn-sm" onclick="window.print()">
                    <span class="glyphicon glyphicon-print"></span> Print
                </button>
                <a href="<?= site_url('reports/customer_history_input') ?>" class="btn btn-primary btn-sm">
                    <span class="glyphicon glyphicon-search"></span> Search Another Customer
                </a>
            </div>
        </div>
    </div>
    <div class="panel-body">
        <?php if (empty($sales)): ?>
            <div class="alert alert-warning">
                No purchase history found for this customer.
            </div>
        <?php else: ?>
            <table class="table table-striped table-bordered table-hover" id="history_table">
                <thead>
                    <tr style="background: #333; color: #fff;">
                        <th>#</th>
                        <th>Date & Time</th>
                        <th>Invoice No.</th>
                        <th>Items</th>
                        <th>Amount</th>
                        <th>Billed By</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $sno = 1; foreach ($sales as $sale): ?>
                    <tr>
                        <td><?= $sno++ ?></td>
                        <td><?= date('d/m/Y h:i A', strtotime($sale['sale_time'])) ?></td>
                        <td><strong><?= esc($sale['invoice_number'] ?: 'POS ' . $sale['sale_id']) ?></strong></td>
                        <td><?= number_format($sale['total_items']) ?></td>
                        <td><strong><?= to_currency($sale['subtotal']) ?></strong></td>
                        <td><?= esc($sale['employee_name']) ?></td>
                        <td>
                            <a href="<?= site_url('sales/receipt/' . $sale['sale_id']) ?>" 
                               class="btn btn-xs btn-info" target="_blank">
                                <span class="glyphicon glyphicon-eye-open"></span> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f5f5f5; font-weight: bold;">
                        <td colspan="3">TOTAL</td>
                        <td><?= number_format($total_items) ?></td>
                        <td><?= to_currency($total_amount) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>

<?= view('partial/footer') ?>

<script>
function exportToCSV() {
    var csv = [];
    var rows = document.querySelectorAll("#history_table tr");
    
    // Add customer info header
    csv.push(["Customer Purchase History"]);
    csv.push(["Customer: <?= esc($customer->first_name . ' ' . $customer->last_name) ?>"]);
    csv.push(["Phone: <?= esc($customer->phone_number ?? 'N/A') ?>"]);
    csv.push(["Total Purchases: <?= $total_purchases ?>", "Total Amount: <?= $total_amount ?>", "Total Items: <?= $total_items ?>"]);
    csv.push([]);
    
    for (var i = 0; i < rows.length - 1; i++) { // Exclude action column
        var row = [], cols = rows[i].querySelectorAll("td, th");
        
        for (var j = 0; j < cols.length - 1; j++) { // Exclude action column
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(","));
    }
    
    // Download
    var csvContent = csv.join("\n");
    var blob = new Blob(["\ufeff" + csvContent], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "Customer_History_<?= preg_replace('/[^a-zA-Z0-9]/', '_', $customer->first_name . '_' . $customer->last_name) ?>_<?= date('Y-m-d') ?>.csv";
    link.click();
}
</script>

<style>
@media print {
    .btn, .panel-heading .text-right { display: none !important; }
    .panel { border: none !important; box-shadow: none !important; }
}
</style>

