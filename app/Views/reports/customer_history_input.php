<?php
/**
 * Simple Customer Purchase History Report
 * Just search/select customer by name or phone to see their purchase history
 * @var array $customers
 * @var array $config
 */
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    dialog_support.init("a.modal-dlg");
</script>

<div id="page_title">Customer Purchase History</div>

<?= form_open('#', ['id' => 'customer_history_form', 'class' => 'form-horizontal']) ?>

    <div class="form-group form-group-sm">
        <?= form_label('Search Customer', 'customer_search_label', ['class' => 'control-label col-xs-2 required']) ?>
        <div class="col-xs-4">
            <input type="text" id="customer_search" class="form-control input-sm" placeholder="Type customer name or phone number...">
            <input type="hidden" id="customer_id" name="customer_id" value="">
        </div>
    </div>
    
    <div class="form-group form-group-sm">
        <div class="col-xs-offset-2 col-xs-4">
            <div id="selected_customer" style="padding: 10px; background: #f5f5f5; border-radius: 5px; display: none;">
                <strong>Selected:</strong> <span id="customer_name_display"></span>
                <br><small id="customer_phone_display"></small>
            </div>
        </div>
    </div>

    <div class="form-group form-group-sm">
        <div class="col-xs-offset-2 col-xs-4">
            <?php
            echo form_button([
                'name'    => 'generate_report',
                'id'      => 'generate_report',
                'content' => '<span class="glyphicon glyphicon-search"></span> View Purchase History',
                'class'   => 'btn btn-primary btn-lg'
            ]);
            ?>
        </div>
    </div>
    
    <div class="form-group form-group-sm">
        <div class="col-xs-offset-2 col-xs-6">
            <div class="alert alert-info">
                <strong>How to use:</strong>
                <ul>
                    <li>Start typing customer name or phone number</li>
                    <li>Select the customer from suggestions</li>
                    <li>Click "View Purchase History" to see all their purchases</li>
                </ul>
            </div>
        </div>
    </div>

<?= form_close() ?>

<?= view('partial/footer') ?>

<script type="text/javascript">
    $(document).ready(function() {
        // Customer autocomplete search
        $('#customer_search').autocomplete({
            source: "<?= site_url('customers/suggest') ?>",
            minLength: 1,
            delay: 300,
            select: function(event, ui) {
                // Parse the customer info from the suggestion
                var parts = ui.item.value.split(' ');
                var customerId = parts[0]; // First part is usually the ID or identifier
                
                // Set the hidden field
                $('#customer_id').val(ui.item.value);
                
                // Show selected customer
                $('#customer_name_display').text(ui.item.label || ui.item.value);
                $('#selected_customer').show();
                
                return true;
            }
        });
        
        // Generate report
        $("#generate_report").click(function() {
            var customerId = $('#customer_id').val();
            
            if (!customerId) {
                alert('Please select a customer first');
                return false;
            }
            
            // Navigate to the customer history report
            window.location = '<?= site_url("reports/customer_history") ?>/' + encodeURIComponent(customerId);
        });
    });
</script>

