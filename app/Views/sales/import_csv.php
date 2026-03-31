<?php echo view('partial/header'); ?>

<div id="page_title" style="margin-bottom: 8px;">Import Legacy Sales (CSV)</div>
<div id="page_subtitle" style="margin-bottom: 8px;">Bulk import older sales records into the new system.</div>

<div class="box box-solid">
    <div class="box-body">
        <p><strong>Required CSV Format:</strong></p>
        <p><code>Invoice Number, Date, Customer Name, Item Name, Quantity, Price, Total, Payment Method</code></p>
        
        <br>

        <form enctype="multipart/form-data" id="import_form" method="post" action="<?php echo site_url('sales/import_csv'); ?>">
            <div style="border: 2px dashed #ccc; padding: 40px; text-align: center; border-radius: 8px; background: #fafafa; margin-bottom: 20px;">
                <input type="file" name="file_path" id="file_path" accept=".csv" style="display: block; margin: 0 auto 15px;">
                <p>Drag and drop a legacy Sales CSV file here, or click to select.</p>
            </div>
            
            <button type="submit" class="btn btn-primary" id="submit_import">
                <span class="glyphicon glyphicon-import"></span> Import Legacy Records
            </button>
        </form>

        <div id="import_response" style="margin-top: 20px;"></div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#import_form').submit(function(e) {
        e.preventDefault();
        
        if(!$('#file_path').val()) {
            alert("Please select a CSV file first.");
            return;
        }

        $('#submit_import').prop('disabled', true).html('Importing... Please wait.');

        var formData = new FormData(this);

        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: formData,
            success: function (data) {
                var response = JSON.parse(data);
                var alertClass = response.success ? 'alert-success' : 'alert-danger';
                $('#import_response').html('<div class="alert ' + alertClass + '">' + response.message + '</div>');
                $('#submit_import').prop('disabled', false).html('<span class="glyphicon glyphicon-import"></span> Import Legacy Records');
                $('#import_form')[0].reset();
            },
            cache: false,
            contentType: false,
            processData: false
        });
    });
});
</script>

<?php echo view('partial/footer'); ?>
