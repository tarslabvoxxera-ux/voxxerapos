<?php
/**
 * Comprehensive Sales Report Input Form
 * @var array $config
 */
?>

<?= view('partial/header') ?>

<div id="page_title" style="margin-bottom: 20px"><?= lang('Reports.sales_report') ?> - Comprehensive Export</div>

<?= form_open('#', ['id' => 'comprehensive_sales_form', 'class' => 'form-horizontal']) ?>
    <div class="form-group form-group-sm">
        <?= form_label(lang('Reports.date_range'), 'report_date_range_label', ['class' => 'control-label col-xs-2 required']) ?>
        <div class="col-xs-3">
            <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']) ?>
        </div>
    </div>
    
    <div class="form-group form-group-sm">
        <div class="col-xs-offset-2 col-xs-3">
            <button type="button" id="export_excel" class="btn btn-primary btn-lg">
                <span class="glyphicon glyphicon-download-alt"></span>
                Export to Excel
            </button>
        </div>
    </div>
    
    <div class="form-group form-group-sm">
        <div class="col-xs-offset-2 col-xs-8">
            <div class="alert alert-info">
                <strong>What's Included:</strong>
                <ul>
                    <li>✓ Sale ID & Invoice Number</li>
                    <li>✓ Date & Time of each sale</li>
                    <li>✓ Employee who billed it</li>
                    <li>✓ Customer details (Name, Email)</li>
                    <li>✓ All items sold with quantities</li>
                    <li>✓ Prices, Discounts, Taxes</li>
                    <li>✓ Payment types</li>
                    <li>✓ Profit & Cost details</li>
                    <li>✓ Comments</li>
                    <li>✓ Summary totals</li>
                </ul>
                <p><strong>This report captures EVERYTHING - No records are missed!</strong></p>
            </div>
        </div>
    </div>
<?= form_close() ?>

<?= view('partial/footer') ?>

<script type="text/javascript">
    $(document).ready(function() {
        var start_date, end_date;
        
        <?= view('partial/daterangepicker') ?>
        
        $("#export_excel").click(function() {
            if (!start_date || !end_date) {
                alert('Please select both start and end dates');
                return false;
            }
            
            window.location.href = '<?= site_url("reports/comprehensive_sales") ?>/' + start_date + '/' + end_date + '/all/all';
        });
    });
</script>


