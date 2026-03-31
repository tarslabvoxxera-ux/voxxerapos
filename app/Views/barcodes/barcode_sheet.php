<?php
/**
 * Barcode Sheet - Optimized for Sticker Label Printing
 * Standard sizes: 50mm x 25mm, 38mm x 25mm
 * @var array $barcode_config
 * @var array $items
 */

use Picqer\Barcode\BarcodeGeneratorSVG;
use Config\Database;

$generator = new BarcodeGeneratorSVG();
$db = Database::connect();
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
            background: #f5f5f5;
        }
        
        .print-controls {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #ddd;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .print-controls h3 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .print-controls button {
            padding: 12px 30px;
            font-size: 16px;
            margin: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            border: none;
        }
        
        .btn-print {
            background: #28a745;
            color: white;
        }
        
        .btn-print:hover {
            background: #218838;
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .size-selector {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .size-selector label {
            margin-right: 20px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .size-selector input[type="radio"] {
            margin-right: 5px;
        }
        
        .label-count {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        
        .labels-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8mm;
            justify-content: flex-start;
            background: #fff;
            padding: 10mm;
            border-radius: 5px;
        }
        
        /* Default: 50mm x 30mm labels */
        .label {
            border: 1px solid #000;
            width: 50mm;
            height: 30mm;
            padding: 2mm;
            background: #fff;
            page-break-inside: avoid;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }
        
        .product-name {
            font-size: 8pt;
            font-weight: bold;
            text-align: center;
            line-height: 1.1;
            max-height: 5mm;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .barcode-section {
            text-align: center;
            flex-grow: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1mm 0;
        }
        
        .barcode-section svg {
            height: 12mm;
            width: auto;
            max-width: 46mm;
        }
        
        .barcode-number {
            font-family: 'Courier New', monospace;
            font-size: 7pt;
            font-weight: bold;
            text-align: center;
            letter-spacing: 1px;
        }
        
        .mrp {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            padding: 1mm 0;
            background: #000;
            color: #fff;
            margin-top: 1mm;
        }
        
        /* Size option: Small (38mm x 25mm) */
        .size-small .label {
            width: 38mm;
            height: 25mm;
            padding: 1.5mm;
        }
        
        .size-small .product-name {
            font-size: 7pt;
            max-height: 4mm;
        }
        
        .size-small .barcode-section svg {
            height: 10mm;
            max-width: 34mm;
        }
        
        .size-small .barcode-number {
            font-size: 6pt;
        }
        
        .size-small .mrp {
            font-size: 8pt;
        }
        
        /* Size option: Large (60mm x 40mm) */
        .size-large .label {
            width: 60mm;
            height: 40mm;
            padding: 3mm;
        }
        
        .size-large .product-name {
            font-size: 10pt;
            max-height: 7mm;
        }
        
        .size-large .barcode-section svg {
            height: 18mm;
            max-width: 54mm;
        }
        
        .size-large .barcode-number {
            font-size: 9pt;
        }
        
        .size-large .mrp {
            font-size: 11pt;
        }
        
        /* Layout: 2 columns */
        .layout-2col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8mm;
            justify-items: center;
        }
        
        /* Layout: 3 columns */
        .layout-3col {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6mm;
            justify-items: center;
        }
        
        @media print {
            @page {
                margin: 2mm;
            }
            
            body {
                padding: 0;
                background: #fff;
            }
            
            .print-controls {
                display: none !important;
            }
            
            .labels-container {
                padding: 2mm;
                gap: 5mm;
                row-gap: 6mm;
            }
            
            .label {
                border: 0.5pt solid #000;
                page-break-inside: avoid;
                margin: 1mm;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls">
        <h3>🏷️ Barcode Label Printing</h3>
        
        <div class="size-selector">
            <strong>Label Size:</strong>&nbsp;&nbsp;
            <label>
                <input type="radio" name="labelSize" value="small" onclick="setLabelSize('small')">
                Small (38×25mm)
            </label>
            <label>
                <input type="radio" name="labelSize" value="medium" onclick="setLabelSize('medium')" checked>
                Medium (50×30mm)
            </label>
            <label>
                <input type="radio" name="labelSize" value="large" onclick="setLabelSize('large')">
                Large (60×40mm)
            </label>
        </div>
        
        <div class="size-selector">
            <strong>Layout:</strong>&nbsp;&nbsp;
            <label>
                <input type="radio" name="layout" value="auto" onclick="setLayout('auto')" checked>
                Auto (Fit as many)
            </label>
            <label>
                <input type="radio" name="layout" value="2col" onclick="setLayout('2col')">
                2 Labels Per Row
            </label>
            <label>
                <input type="radio" name="layout" value="3col" onclick="setLayout('3col')">
                3 Labels Per Row
            </label>
        </div>
        
        <button class="btn-print" onclick="window.print();">
            🖨️ Print Labels
        </button>
        <button class="btn-back" onclick="window.history.back();">
            ← Back to Items
        </button>
        
        <div class="label-count">
            Total Labels: <strong><?= count($items) ?></strong>
        </div>
    </div>
    
    <div class="labels-container" id="labelsContainer">
<?php foreach ($items as $item): 
    $item_id = $item['item_id'] ?? 0;
    $barcode_num = $item['item_number'] ?? '';
    
    // Generate barcode SVG - optimized for label size
    $barcode_svg = '';
    if (!empty($barcode_num)) {
        try {
            // Code 128 with 2px bar width, 50px height for compact labels
            $barcode_svg = $generator->getBarcode($barcode_num, $generator::TYPE_CODE_128, 2, 50);
        } catch (Exception $e) {
            $barcode_svg = '';
        }
    }
?>
        <div class="label">
            <div class="product-name"><?= esc(mb_substr($item['name'] ?? 'N/A', 0, 30)) ?></div>
            <div class="barcode-section"><?= $barcode_svg ?></div>
            <div class="barcode-number"><?= esc($barcode_num) ?></div>
            <div class="mrp">₹<?= number_format($item['unit_price'] ?? 0, 0) ?></div>
        </div>
<?php endforeach; ?>
    </div>
    
    <script>
        function setLabelSize(size) {
            var container = document.getElementById('labelsContainer');
            container.classList.remove('size-small', 'size-medium', 'size-large');
            if (size !== 'medium') {
                container.classList.add('size-' + size);
            }
        }
        
        function setLayout(layout) {
            var container = document.getElementById('labelsContainer');
            container.classList.remove('layout-2col', 'layout-3col');
            if (layout === '2col') {
                container.classList.add('layout-2col');
            } else if (layout === '3col') {
                container.classList.add('layout-3col');
            }
        }
    </script>
</body>
</html>
