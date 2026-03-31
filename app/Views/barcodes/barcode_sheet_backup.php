<?php
/**
 * Barcode Sheet - Exact format requested
 * Product, Sub-Division, Brand, Style, Color, Size, GST, Barcode, MRP
 * @var array $barcode_config
 * @var array $items
 */

use Picqer\Barcode\BarcodeGeneratorSVG;

$attribute = model('Attribute');
$generator = new BarcodeGeneratorSVG();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Barcode Labels</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: Arial, sans-serif;
            padding: 10px;
        }
        
        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .label {
            border: 2px solid #333;
            width: 300px;
            padding: 12px;
            background: #fff;
        }
        
        .product-name {
            font-size: 16px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 4px;
        }
        
        .sub-division {
            font-size: 11px;
            text-align: center;
            color: #555;
            margin-bottom: 10px;
        }
        
        .detail-row {
            font-size: 11px;
            margin: 3px 0;
            padding-left: 10px;
        }
        
        .detail-row strong {
            display: inline-block;
            width: 55px;
        }
        
        .barcode-section {
            text-align: center;
            margin: 12px 0 8px 0;
        }
        
        .barcode-section svg {
            height: 50px;
            max-width: 260px;
        }
        
        .barcode-number {
            font-family: monospace;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            margin: 5px 0;
        }
        
        .mrp {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding: 6px;
            background: #f0f0f0;
        }
        
        @media print {
            body { padding: 0; }
            .label { border: 1px solid #000; page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="labels-container">
<?php foreach ($items as $item): 
    // Get attributes for this item
    $item_id = $item['item_id'] ?? 0;
    $attrs = [];
    if ($item_id > 0) {
        $attr_data = $attribute->get_attributes_by_item($item_id);
        foreach ($attr_data as $a) {
            if (!empty($a['attribute_value'])) {
                $attrs[$a['definition_name']] = $a['attribute_value'];
            }
        }
    }
    
    // Barcode value
    $barcode_num = $item['item_number'] ?? '';
    
    // Generate barcode SVG
    $barcode_svg = '';
    if (!empty($barcode_num)) {
        try {
            $barcode_svg = $generator->getBarcode($barcode_num, $generator::TYPE_CODE_128, 2, 50);
        } catch (Exception $e) {
            $barcode_svg = '';
        }
    }
?>
        <div class="label">
            <!-- Product Name -->
            <div class="product-name"><?= esc($item['name'] ?? 'N/A') ?></div>
            
            <!-- Sub-Division -->
            <div class="sub-division"><?= esc($item['category'] ?? '') ?></div>
            
            <!-- Brand -->
            <?php if (!empty($attrs['Brand'])): ?>
            <div class="detail-row"><strong>Brand:</strong> <?= esc($attrs['Brand']) ?></div>
            <?php endif; ?>
            
            <!-- Style -->
            <?php if (!empty($attrs['Style'])): ?>
            <div class="detail-row"><strong>Style:</strong> <?= esc($attrs['Style']) ?></div>
            <?php endif; ?>
            
            <!-- Color -->
            <?php if (!empty($attrs['Color'])): ?>
            <div class="detail-row"><strong>Color:</strong> <?= esc($attrs['Color']) ?></div>
            <?php endif; ?>
            
            <!-- Size -->
            <?php if (!empty($attrs['Size'])): ?>
            <div class="detail-row"><strong>Size:</strong> <?= esc($attrs['Size']) ?></div>
            <?php endif; ?>
            
            <!-- GST Group -->
            <?php if (!empty($attrs['GST Group'])): ?>
            <div class="detail-row"><strong>GST:</strong> <?= esc($attrs['GST Group']) ?></div>
            <?php endif; ?>
            
            <!-- Barcode -->
            <div class="barcode-section"><?= $barcode_svg ?></div>
            
            <!-- Barcode Number -->
            <div class="barcode-number"><?= esc($barcode_num) ?></div>
            
            <!-- MRP -->
            <div class="mrp">MRP: ₹<?= number_format($item['unit_price'] ?? 0, 2) ?></div>
        </div>
<?php endforeach; ?>
    </div>
</body>
</html>
