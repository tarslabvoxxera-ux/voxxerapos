<?php
/**
 * @var array $receipts
 * @var array $stats
 * @var string $current_year
 * @var string|null $current_month
 * @var array $years
 */
?>

<?= view('partial/header') ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">
                        <span class="glyphicon glyphicon-file"></span>
                        <?= lang('Common.receipts') ?? 'Saved Receipts' ?>
                    </h3>
                </div>
                <div class="panel-body">
                    
                    <!-- Statistics -->
                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <h4><?= $stats['total_receipts'] ?></h4>
                                <small>Total Receipts</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="well well-sm text-center">
                                <h4><?= $stats['total_size_formatted'] ?></h4>
                                <small>Storage Used</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="well well-sm">
                                <strong>Storage Path:</strong> 
                                <code><?= esc($stats['path']) ?></code>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="row" style="margin-bottom: 20px;">
                        <div class="col-md-12">
                            <form method="get" class="form-inline">
                                <div class="form-group">
                                    <label for="year">Year:</label>
                                    <select name="year" id="year" class="form-control" onchange="this.form.submit()">
                                        <?php foreach ($years as $year): ?>
                                            <option value="<?= $year ?>" <?= $year == $current_year ? 'selected' : '' ?>>
                                                <?= $year ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group" style="margin-left: 15px;">
                                    <label for="month">Month:</label>
                                    <select name="month" id="month" class="form-control" onchange="this.form.submit()">
                                        <option value="">All Months</option>
                                        <?php 
                                        $months = [
                                            '01' => 'January', '02' => 'February', '03' => 'March',
                                            '04' => 'April', '05' => 'May', '06' => 'June',
                                            '07' => 'July', '08' => 'August', '09' => 'September',
                                            '10' => 'October', '11' => 'November', '12' => 'December'
                                        ];
                                        foreach ($months as $num => $name): ?>
                                            <option value="<?= $num ?>" <?= $num == $current_month ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <a href="<?= site_url('receipts/generateAll?limit=500') ?>" class="btn btn-success" style="margin-left: 15px;">
                                    <span class="glyphicon glyphicon-refresh"></span> Generate Missing PDFs
                                </a>
                            </form>
                        </div>
                    </div>

                    <!-- Receipts Table -->
                    <?php if (empty($receipts)): ?>
                        <div class="alert alert-info">
                            <span class="glyphicon glyphicon-info-sign"></span>
                            No receipts found for the selected period. Receipts are automatically saved after each sale.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Date</th>
                                        <th>Filename</th>
                                        <th>Size</th>
                                        <th>Created</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($receipts as $receipt): ?>
                                        <tr>
                                            <td>
                                                <strong>POS <?= esc($receipt['sale_id']) ?></strong>
                                            </td>
                                            <td><?= $receipt['year'] . '-' . $receipt['month'] ?></td>
                                            <td><code><?= esc($receipt['filename']) ?></code></td>
                                            <td><?= number_format($receipt['size'] / 1024, 1) ?> KB</td>
                                            <td><?= date('Y-m-d H:i:s', $receipt['created']) ?></td>
                                            <td class="text-center">
                                                <a href="<?= site_url('receipts/pdf/' . $receipt['sale_id']) ?>" 
                                                   class="btn btn-info btn-xs" target="_blank"
                                                   title="View PDF">
                                                    <span class="glyphicon glyphicon-eye-open"></span> View
                                                </a>
                                                <a href="<?= site_url('receipts/download/' . $receipt['sale_id']) ?>" 
                                                   class="btn btn-primary btn-xs"
                                                   title="Download PDF">
                                                    <span class="glyphicon glyphicon-download"></span> Download
                                                </a>
                                                <a href="<?= site_url('sales/receipt/' . $receipt['sale_id']) ?>" 
                                                   class="btn btn-default btn-xs"
                                                   title="View Original Receipt">
                                                    <span class="glyphicon glyphicon-print"></span> Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="text-muted">
                            Showing <?= count($receipts) ?> receipt(s)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= view('partial/footer') ?>

