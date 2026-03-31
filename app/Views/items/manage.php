<?php
/**
 * @var string $controller_name
 * @var string $table_headers
 * @var array $filters
 * @var array $stock_locations
 * @var int $stock_location
 * @var array $config
 */

use App\Models\Employee;
?>

<?= view('partial/header') ?>

<script type="text/javascript">
    $(document).ready(function() {
        $('#generate_barcodes').click(function() {
            window.open(
                'index.php/items/generateBarcodes/' + table_support.selected_ids().join(':'),
                '_blank'
            );
        });

        // When any filter is clicked and the dropdown window is closed
        $('#filters').on('hidden.bs.select', function(e) {
            table_support.refresh();
        });

        // Load the preset daterange picker
        <?= view('partial/daterangepicker') ?>
        // Set the beginning of time as starting date and end date to today
        $('#daterangepicker').data('daterangepicker').setStartDate("<?= date($config['dateformat'], mktime(0, 0, 0, 01, 01, 2010)) ?>");
        $('#daterangepicker').data('daterangepicker').setEndDate("<?= date($config['dateformat'], mktime(0, 0, 0, 12, 31, 2030)) ?>");
        // Update the hidden inputs with the selected dates before submitting the search data
        var start_date = "<?= date('Y-m-d', mktime(0, 0, 0, 01, 01, 2010)) ?>";
        var end_date = "<?= date('Y-m-d', mktime(0, 0, 0, 12, 31, 2030)) ?>";
        $("#daterangepicker").on('apply.daterangepicker', function(ev, picker) {
            table_support.refresh();
        });

        $("#stock_location").change(function() {
            table_support.refresh();
        });

        <?php
        echo view('partial/bootstrap_tables_locale');
        $employee = model(Employee::class);
        ?>

        var currentPageSize = <?= $config['lines_per_page'] ?>;
        var showAllMode = false;
        var totalRows = 0;
        var ALL_PAGE_SIZE = 999999;  // Special value for "Show All"
        
        table_support.init({
            employee_id: <?= $employee->get_logged_in_employee_info()->person_id ?>,
            resource: '<?= esc($controller_name) ?>',
            headers: <?= $table_headers ?>,
            pageSize: currentPageSize,
            uniqueId: 'items.item_id',
            pageList: [10, 25, 50, 100, 200, 500, ALL_PAGE_SIZE],  // Add large number for "Show All"
            queryParams: function(params) {
                var queryParams = $.extend({}, params, {
                    "start_date": start_date,
                    "end_date": end_date,
                    "stock_location": $("#stock_location").val(),
                    "filters": $("#filters").val()
                });
                
                // Check if current pageSize indicates "Show All"
                var tableOptions = $('#table').bootstrapTable('getOptions');
                if (tableOptions && (tableOptions.pageSize >= ALL_PAGE_SIZE || showAllMode)) {
                    queryParams.limit = 999999;
                    queryParams.offset = 0;
                }
                
                return queryParams;
            },
            onLoadSuccess: function(response) {
                // Store total rows for "Show All" functionality
                if (response && response.total !== undefined) {
                    totalRows = response.total;
                }
                
                $('a.rollover').imgPreview({
                    imgCSS: {
                        width: 200
                    },
                    distanceFromCursor: {
                        top: 10,
                        left: -210
                    }
                });
            }
        });
        
        // After table loads, change "999999" display to "Show All" in the dropdown
        $('#table').on('post-body.bs.table', function() {
            // Find and update the page size dropdown text
            $('.page-size select option, .page-size .dropdown-menu a').each(function() {
                var $this = $(this);
                if ($this.text() == ALL_PAGE_SIZE || $this.attr('value') == ALL_PAGE_SIZE) {
                    $this.text('Show All');
                }
            });
        });
        
        // Hook into Bootstrap Table's page size change
        $('#table').on('page-size-change.bs.table', function (e, pageSize) {
            // Check if it's the "Show All" value
            if (pageSize >= ALL_PAGE_SIZE) {
                showAllMode = true;
                // Set to total rows or very large number
                var pageSizeToSet = totalRows > 0 ? totalRows : ALL_PAGE_SIZE;
                if (pageSizeToSet !== pageSize) {
                    $('#table').bootstrapTable('refreshOptions', {
                        pageSize: pageSizeToSet
                    });
                }
                $('#table').bootstrapTable('selectPage', 1);
                setTimeout(function() {
                    $('#table').bootstrapTable('refresh');
                }, 100);
            } else {
                showAllMode = false;
            }
        });
        
        // Handle the custom dropdown in toolbar
        $('#page_size_selector').change(function() {
            var selectedSize = $(this).val();
            if (selectedSize === 'all') {
                showAllMode = true;
                var pageSizeToSet = totalRows > 0 ? totalRows : ALL_PAGE_SIZE;
                $('#table').bootstrapTable('refreshOptions', {
                    pageSize: pageSizeToSet,
                    pagination: true
                });
                $('#table').bootstrapTable('selectPage', 1);
                setTimeout(function() {
                    $('#table').bootstrapTable('refresh');
                }, 100);
            } else {
                showAllMode = false;
                currentPageSize = parseInt(selectedSize);
                $('#table').bootstrapTable('refreshOptions', {
                    pageSize: currentPageSize,
                    pagination: true
                });
                $('#table').bootstrapTable('selectPage', 1);
                $('#table').bootstrapTable('refresh');
            }
        });
    });
</script>

<div id="title_bar" class="btn-toolbar print_hide">
    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/csvImport" ?>" title="<?= lang('Items.import_items_csv') ?>">
        <span class="glyphicon glyphicon-import">&nbsp;</span><?= lang('Common.import_csv') ?>
    </button>

    <button class="btn btn-info btn-sm pull-right modal-dlg" data-btn-new="<?= lang('Common.new') ?>" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "$controller_name/view" ?>" title="<?= lang(ucfirst($controller_name) . '.new') ?>">
        <span class="glyphicon glyphicon-tag">&nbsp;</span><?= lang(ucfirst($controller_name) . '.new') ?>
    </button>
</div>

<div id="toolbar">
    <div class="pull-left form-inline" role="toolbar">
        <button id="delete" class="btn btn-default btn-sm print_hide">
            <span class="glyphicon glyphicon-trash">&nbsp;</span><?= lang('Common.delete') ?>
        </button>
        <button id="bulk_edit" class="btn btn-default btn-sm modal-dlg print_hide" data-btn-submit="<?= lang('Common.submit') ?>" data-href="<?= "items/bulkEdit" ?>" title="<?= lang('Items.edit_multiple_items') ?>">
            <span class="glyphicon glyphicon-edit">&nbsp;</span><?= lang('Items.bulk_edit') ?>
        </button>
        <button id="generate_barcodes" class="btn btn-default btn-sm print_hide" data-href="<?= "$controller_name/generateBarcodes" ?>" title="<?= lang('Items.generate_barcodes') ?>">
            <span class="glyphicon glyphicon-barcode">&nbsp;</span><?= lang('Items.generate_barcodes') ?>
        </button>
        <?= form_input(['name' => 'daterangepicker', 'class' => 'form-control input-sm', 'id' => 'daterangepicker']) ?>
        <?= form_multiselect('filters[]', $filters, [''], [
            'id'                        => 'filters',
            'class'                     => 'selectpicker show-menu-arrow',
            'data-none-selected-text'   => lang('Common.none_selected_text'),
            'data-selected-text-format' => 'count > 1',
            'data-style'                => 'btn-default btn-sm',
            'data-width'                => 'fit'
        ]) ?>
        <?php
        if (count($stock_locations) > 1) {
            echo form_dropdown(
                'stock_location',
                $stock_locations,
                $stock_location,
                [
                    'id'         => 'stock_location',
                    'class'      => 'selectpicker show-menu-arrow',
                    'data-style' => 'btn-default btn-sm',
                    'data-width' => 'fit'
                ]
            );
        }
        ?>
        <?php
        $page_size_options = [
            '10'   => '10 per page',
            '25'   => '25 per page',
            '50'   => '50 per page',
            '100'  => '100 per page',
            'all'  => 'Show All'
        ];
        $default_page_size = (string)$config['lines_per_page'];
        // If current page size is not in options, use closest or default to 25
        if (!isset($page_size_options[$default_page_size])) {
            $default_page_size = '25';
        }
        echo form_dropdown(
            'page_size_selector',
            $page_size_options,
            $default_page_size,
            [
                'id'         => 'page_size_selector',
                'class'      => 'selectpicker show-menu-arrow',
                'data-style' => 'btn-default btn-sm',
                'data-width' => 'fit',
                'title'      => 'Rows per page'
            ]
        );
        ?>
    </div>
</div>

<div id="table_holder">
    <table id="table"></table>
</div>

<?= view('partial/footer') ?>
