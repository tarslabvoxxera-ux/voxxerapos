<?php

namespace App\Libraries;

use Config\Services;
use Config\OSPOS;

/**
 * Receipt PDF Library
 * Handles automatic saving of receipt PDFs to server storage
 */
class Receipt_pdf
{
    protected $config;
    protected $receipts_path;

    public function __construct()
    {
        $this->config = config(OSPOS::class)->settings;
        $this->receipts_path = WRITEPATH . 'receipts/';
        $this->ensure_directory_exists();
    }

    /**
     * Ensure the receipts directory exists
     */
    private function ensure_directory_exists(): void
    {
        if (!is_dir($this->receipts_path)) {
            mkdir($this->receipts_path, 0755, true);
        }
    }

    /**
     * Get the path for a receipt file
     * Organizes by Year/Month
     */
    public function get_receipt_path(int $sale_id, ?string $date = null): string
    {
        $date = $date ?? date('Y-m-d');
        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        $path = $this->receipts_path . $year . '/' . $month . '/';
        
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        return $path . 'SALE-' . $sale_id . '.pdf';
    }

    /**
     * Save a receipt PDF
     */
    public function save_receipt(int $sale_id, array $sale_data): bool
    {
        helper(['dompdf', 'file']);
        
        try {
            // Always load fresh config from database
            $appConfig = model('Appconfig');
            $config_rows = $appConfig->get_all()->getResultArray();
            $sale_data['config'] = array_column($config_rows, 'value', 'key');
            
            // Generate barcode if not present
            if (!isset($sale_data['barcode'])) {
                $barcode_lib = new \App\Libraries\Barcode_lib();
                $sale_data['barcode'] = $barcode_lib->generate_receipt_barcode($sale_data['sale_id'] ?? 'POS ' . $sale_id);
            }
            
            // Ensure we have invoice_number
            if (!isset($sale_data['invoice_number'])) {
                $sale_data['invoice_number'] = $sale_data['sale_id'] ?? 'POS ' . $sale_id;
            }
            
            // Get transaction date/time
            if (!isset($sale_data['transaction_time'])) {
                $sale_data['transaction_time'] = date('Y-m-d H:i:s');
            }
            
            // Render the receipt HTML
            $html = $this->generate_receipt_html($sale_data);
            
            // Create PDF
            $pdf_content = create_pdf($html);
            
            // Get the file path
            $filepath = $this->get_receipt_path($sale_id, $sale_data['transaction_time'] ?? null);
            
            // Save the PDF
            if (file_put_contents($filepath, $pdf_content) !== false) {
                log_message('info', "Receipt PDF saved: {$filepath}");
                return true;
            }
            
            log_message('error', "Failed to save receipt PDF: {$filepath}");
            return false;
            
        } catch (\Exception $e) {
            log_message('error', "Error saving receipt PDF: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate receipt HTML for PDF - Custom Format matching receipt_custom.php
     */
    private function generate_receipt_html(array $data): string
    {
        $config = $data['config'];
        
        // Get invoice number
        $invoice_number = $data['invoice_number'] ?? $data['sale_id'] ?? 'POS ' . ($data['sale_id_num'] ?? '');
        
        // Parse transaction time
        $transaction_time = $data['transaction_time'] ?? date('Y-m-d H:i:s');
        $transaction_date = date('d/m/Y', strtotime($transaction_time));
        $transaction_time_only = date('H:i:s', strtotime($transaction_time));
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: "Courier New", monospace; 
            font-size: 12px; 
            line-height: 1.3;
            width: 80mm;
            margin: 0 auto;
            padding: 8mm;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .receipt-header h2 {
            margin: 0 0 4px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .receipt-header p {
            margin: 1px 0;
            font-size: 11px;
            line-height: 1.4;
        }
        .invoice-type {
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            margin: 6px 0;
            text-decoration: underline;
            letter-spacing: 1px;
        }
        .section {
            margin: 8px 0;
        }
        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }
        .info-row {
            margin: 2px 0;
            overflow: hidden;
        }
        .info-label {
            font-weight: bold;
            float: left;
            width: 45%;
        }
        .info-value {
            float: right;
            width: 55%;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 11px;
        }
        table.items-table {
            border: 1px solid #000;
        }
        table.items-table th {
            background-color: #e0e0e0;
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
        }
        table.items-table td {
            border: 1px solid #000;
            padding: 4px 3px;
            text-align: right;
            font-size: 11px;
        }
        table.items-table td:first-child {
            text-align: left;
        }
        .tax-table {
            margin-top: 8px;
            font-size: 11px;
            border: 1px solid #000;
        }
        .tax-table th, .tax-table td {
            border: 1px solid #000;
            padding: 4px 5px;
            text-align: right;
        }
        .tax-table th {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .total-row {
            font-weight: bold;
            font-size: 11px;
        }
        .amount-words {
            margin: 5px 0;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .terms {
            margin-top: 10px;
            border-top: 1px solid #000;
            padding-top: 5px;
            font-size: 9px;
        }
        .terms-title {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 3px;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
            font-weight: bold;
            border-top: 2px solid #000;
            padding-top: 5px;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>
<body>';

        // Header
        $html .= '<div class="receipt-header">';
        $html .= '<h2>' . esc($config['company'] ?? 'Store Name') . '</h2>';
        if (!empty($config['address'])) {
            $html .= '<p>' . nl2br(esc($config['address'])) . '</p>';
        }
        if (!empty($config['phone'])) {
            $html .= '<p>Contact: ' . esc($config['phone']) . '</p>';
        }
        if (!empty($config['tax_id'])) {
            $html .= '<p><strong>GSTIN No.: ' . esc($config['tax_id']) . '</strong></p>';
        }
        $html .= '</div>';

        // Invoice Type
        $html .= '<div class="invoice-type">RETAIL INVOICE</div>';

        // Invoice Details
        $html .= '<div class="section">';
        $html .= '<div class="info-row clearfix"><span class="info-label">Invoice No:</span><span class="info-value">' . esc($invoice_number) . '</span></div>';
        $html .= '<div class="info-row clearfix"><span class="info-label">Date:</span><span class="info-value">' . $transaction_date . '</span></div>';
        $html .= '<div class="info-row clearfix"><span class="info-label">Time:</span><span class="info-value">' . $transaction_time_only . '</span></div>';
        $html .= '</div>';

        // Customer Details
        if (!empty($data['customer'])) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Customer Details</div>';
            $html .= '<div class="info-row clearfix"><span class="info-label">Name:</span><span class="info-value">' . esc($data['customer']) . '</span></div>';
            if (!empty($data['customer_info'])) {
                $html .= '<div class="info-row clearfix"><span class="info-label">Contact:</span><span class="info-value">' . esc(strip_tags($data['customer_info'])) . '</span></div>';
            }
            $html .= '</div>';
        }

        // Items Table
        $html .= '<div class="section">';
        $html .= '<table class="items-table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width: 40%;">Product</th>';
        $html .= '<th style="width: 10%;">Qty</th>';
        $html .= '<th style="width: 20%;">MRP</th>';
        $html .= '<th style="width: 15%;">Disc%</th>';
        $html .= '<th style="width: 25%;">Net Amt</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        
        if (isset($data['cart']) && is_array($data['cart'])) {
            foreach ($data['cart'] as $item) {
                $print_option = $item['print_option'] ?? PRINT_YES;
                if ($print_option == PRINT_YES) {
                    $item_name = substr($item['name'] ?? 'Item', 0, 25);
                    $quantity = $item['quantity'] ?? 1;
                    $price = $item['price'] ?? 0;
                    $discount = $item['discount'] ?? 0;
                    $discounted_total = $item['discounted_total'] ?? ($item['total'] ?? 0);
                    
                    $html .= '<tr>';
                    $html .= '<td>' . esc($item_name) . '</td>';
                    $html .= '<td style="text-align:center;">' . $quantity . '</td>';
                    $html .= '<td>' . number_format($price, 2) . '</td>';
                    $html .= '<td style="text-align:center;">' . number_format($discount, 1) . '</td>';
                    $html .= '<td>' . number_format($discounted_total, 2) . '</td>';
                    $html .= '</tr>';
                }
            }
        }
        $html .= '</tbody></table>';
        $html .= '</div>';

        // Tax Breakdown
        $subtotal = $data['subtotal'] ?? 0;
        $total_tax = 0;
        $cgst_total = 0;
        $sgst_total = 0;
        
        if (isset($data['taxes']) && is_array($data['taxes'])) {
            foreach ($data['taxes'] as $tax) {
                $tax_amount = $tax['sale_tax_amount'] ?? 0;
                $total_tax += $tax_amount;
                $cgst_total += $tax_amount / 2;
                $sgst_total += $tax_amount / 2;
            }
        }
        
        $total = $data['total'] ?? ($subtotal + $total_tax);

        $html .= '<div class="section">';
        $html .= '<table class="tax-table">';
        $html .= '<thead><tr>';
        $html .= '<th>Taxable Amt</th>';
        $html .= '<th>CGST</th>';
        $html .= '<th>SGST</th>';
        $html .= '<th>Total Tax</th>';
        $html .= '</tr></thead>';
        $html .= '<tbody>';
        $html .= '<tr>';
        $html .= '<td>' . number_format($subtotal, 2) . '</td>';
        $html .= '<td>' . number_format($cgst_total, 2) . '</td>';
        $html .= '<td>' . number_format($sgst_total, 2) . '</td>';
        $html .= '<td>' . number_format($total_tax, 2) . '</td>';
        $html .= '</tr>';
        $html .= '<tr class="total-row">';
        $html .= '<td colspan="3" style="text-align:left;"><strong>Bill Amount:</strong></td>';
        $html .= '<td><strong>' . number_format($total, 2) . '</strong></td>';
        $html .= '</tr>';
        $html .= '</tbody></table>';
        $html .= '</div>';

        // Amount in Words
        $amount_words = $this->convert_number_to_words(floor($total));
        if ($amount_words) {
            $html .= '<div class="amount-words">Rupees ' . $amount_words . ' Only</div>';
        }

        // Payment Details
        if (isset($data['payments']) && is_array($data['payments']) && !empty($data['payments'])) {
            $html .= '<div class="section">';
            $html .= '<div class="section-title">Payment Details</div>';
            foreach ($data['payments'] as $payment) {
                $payment_type = $payment['payment_type'] ?? 'Cash';
                $payment_amount = $payment['payment_amount'] ?? 0;
                $html .= '<div class="info-row clearfix"><span class="info-label">' . esc($payment_type) . ':</span><span class="info-value">' . number_format($payment_amount, 2) . '</span></div>';
            }
            $html .= '</div>';
        }

        // Terms & Conditions
        if (!empty($config['return_policy'])) {
            $html .= '<div class="terms">';
            $html .= '<div class="terms-title">Terms & Conditions:</div>';
            $html .= nl2br(esc($config['return_policy']));
            $html .= '</div>';
        }

        // Footer
        $html .= '<div class="footer">Thank You for Your Visit</div>';

        $html .= '</body></html>';

        return $html;
    }
    
    /**
     * Convert number to words (Indian format)
     */
    private function convert_number_to_words(int $number): string
    {
        if ($number == 0) return 'Zero';
        
        $words = [
            '', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'
        ];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
        
        if ($number < 20) {
            return $words[$number];
        }
        
        if ($number < 100) {
            return $tens[floor($number / 10)] . ($number % 10 ? ' ' . $words[$number % 10] : '');
        }
        
        if ($number < 1000) {
            return $words[floor($number / 100)] . ' Hundred' . ($number % 100 ? ' ' . $this->convert_number_to_words($number % 100) : '');
        }
        
        if ($number < 100000) {
            return $this->convert_number_to_words(floor($number / 1000)) . ' Thousand' . ($number % 1000 ? ' ' . $this->convert_number_to_words($number % 1000) : '');
        }
        
        if ($number < 10000000) {
            return $this->convert_number_to_words(floor($number / 100000)) . ' Lakh' . ($number % 100000 ? ' ' . $this->convert_number_to_words($number % 100000) : '');
        }
        
        return $this->convert_number_to_words(floor($number / 10000000)) . ' Crore' . ($number % 10000000 ? ' ' . $this->convert_number_to_words($number % 10000000) : '');
    }

    /**
     * Get all saved receipts
     */
    public function get_all_receipts(?string $year = null, ?string $month = null): array
    {
        $receipts = [];
        $base_path = $this->receipts_path;
        
        if ($year) {
            $base_path .= $year . '/';
            if ($month) {
                $base_path .= $month . '/';
            }
        }
        
        if (!is_dir($base_path)) {
            return $receipts;
        }
        
        $this->scan_receipts_recursive($base_path, $receipts);
        
        // Sort by date descending
        usort($receipts, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return $receipts;
    }

    /**
     * Recursively scan for receipt files
     */
    private function scan_receipts_recursive(string $path, array &$receipts): void
    {
        $items = scandir($path);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $full_path = $path . $item;
            
            if (is_dir($full_path)) {
                $this->scan_receipts_recursive($full_path . '/', $receipts);
            } elseif (pathinfo($item, PATHINFO_EXTENSION) === 'pdf') {
                // Extract sale ID from filename
                preg_match('/SALE-(\d+)\.pdf/', $item, $matches);
                $sale_id = $matches[1] ?? null;
                
                // Extract date from path
                preg_match('/(\d{4})\/(\d{2})\//', $full_path, $date_matches);
                $year = $date_matches[1] ?? date('Y');
                $month = $date_matches[2] ?? date('m');
                
                $receipts[] = [
                    'sale_id' => $sale_id,
                    'filename' => $item,
                    'filepath' => $full_path,
                    'date' => $year . '-' . $month . '-01',
                    'year' => $year,
                    'month' => $month,
                    'size' => filesize($full_path),
                    'created' => filemtime($full_path)
                ];
            }
        }
    }

    /**
     * Get a specific receipt file path
     */
    public function get_receipt_file(int $sale_id): ?string
    {
        // Search for the file in all directories
        $receipts = $this->get_all_receipts();
        
        foreach ($receipts as $receipt) {
            if ($receipt['sale_id'] == $sale_id) {
                return $receipt['filepath'];
            }
        }
        
        return null;
    }

    /**
     * Delete a receipt
     */
    public function delete_receipt(int $sale_id): bool
    {
        $filepath = $this->get_receipt_file($sale_id);
        
        if ($filepath && file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }

    /**
     * Get storage statistics
     */
    public function get_storage_stats(): array
    {
        $receipts = $this->get_all_receipts();
        $total_size = 0;
        
        foreach ($receipts as $receipt) {
            $total_size += $receipt['size'];
        }
        
        return [
            'total_receipts' => count($receipts),
            'total_size' => $total_size,
            'total_size_formatted' => $this->format_bytes($total_size),
            'path' => $this->receipts_path
        ];
    }

    /**
     * Format bytes to human readable
     */
    private function format_bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

