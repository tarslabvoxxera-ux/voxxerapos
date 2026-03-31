<?php
/**
 * Debug version to see what data is available
 */

use Picqer\Barcode\BarcodeGeneratorSVG;

$attribute = model('Attribute');
$generator = new BarcodeGeneratorSVG();

echo "<h2>DEBUG: Items Data</h2>";
echo "<pre>";
print_r($items);
echo "</pre>";

echo "<h2>DEBUG: Barcode Config</h2>";
echo "<pre>";
print_r($barcode_config);
echo "</pre>";

if (isset($items[0])) {
    $item = $items[0];
    $item_id = $item['item_id'] ?? 0;
    
    echo "<h2>DEBUG: Attributes for Item {$item_id}</h2>";
    $attrs = $attribute->get_attributes_by_item($item_id);
    echo "<pre>";
    print_r($attrs);
    echo "</pre>";
}
?>

