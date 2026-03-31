<?php

namespace App\Controllers;

use App\Libraries\Receipt_pdf;

/**
 * Receipts Controller
 * Handles viewing and managing saved receipt PDFs
 */
class Receipts extends Secure_Controller
{
    private Receipt_pdf $receipt_pdf;

    public function __construct()
    {
        parent::__construct('reports');  // Use reports permission

        $this->receipt_pdf = new Receipt_pdf();
    }

    /**
     * Display the receipts browser
     */
    public function getIndex(): void
    {
        $year = $this->request->getGet('year') ?? date('Y');
        $month = $this->request->getGet('month');

        $data = [
            'receipts' => $this->receipt_pdf->get_all_receipts($year, $month),
            'stats' => $this->receipt_pdf->get_storage_stats(),
            'current_year' => $year,
            'current_month' => $month,
            'years' => $this->get_available_years(),
        ];

        echo view('receipts/index', $data);
    }

    /**
     * Get available years from receipts directory
     */
    private function get_available_years(): array
    {
        $years = [];
        $path = WRITEPATH . 'receipts/';
        
        if (is_dir($path)) {
            $items = scandir($path);
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..' && is_dir($path . $item) && is_numeric($item)) {
                    $years[] = $item;
                }
            }
        }
        
        if (empty($years)) {
            $years[] = date('Y');
        }
        
        rsort($years);
        return $years;
    }

    /**
     * View/download a specific receipt PDF
     */
    public function getPdf(int $sale_id): void
    {
        $filepath = $this->receipt_pdf->get_receipt_file($sale_id);

        if ($filepath && file_exists($filepath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="SALE-' . $sale_id . '.pdf"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

        // If PDF not found, try to generate it
        $sale = model('Sale');
        $sale_info = $sale->get_info($sale_id);
        
        if (!empty($sale_info)) {
            // Generate the receipt on the fly
            $sale_data = $this->_load_sale_data($sale_id);
            if ($this->receipt_pdf->save_receipt($sale_id, $sale_data)) {
                $filepath = $this->receipt_pdf->get_receipt_file($sale_id);
                if ($filepath && file_exists($filepath)) {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="SALE-' . $sale_id . '.pdf"');
                    header('Content-Length: ' . filesize($filepath));
                    readfile($filepath);
                    exit;
                }
            }
        }

        echo "Receipt not found for Sale ID: " . $sale_id;
    }

    /**
     * Download a receipt
     */
    public function getDownload(int $sale_id): void
    {
        $filepath = $this->receipt_pdf->get_receipt_file($sale_id);

        if ($filepath && file_exists($filepath)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="SALE-' . $sale_id . '.pdf"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }

        echo "Receipt not found for Sale ID: " . $sale_id;
    }

    /**
     * Load sale data for PDF generation
     */
    private function _load_sale_data(int $sale_id): array
    {
        $sale = model('Sale');
        $customer = model('Customer');
        $employee = model('Employee');
        
        $sale_info = $sale->get_info($sale_id)->getRowArray();
        $data = [];
        
        if ($sale_info) {
            $data['sale_id'] = 'POS ' . $sale_id;
            $data['sale_id_num'] = $sale_id;
            $data['transaction_time'] = $sale_info['sale_time'] ?? date('Y-m-d H:i:s');
            
            // Get employee info
            if (!empty($sale_info['employee_id'])) {
                $emp_info = $employee->get_info($sale_info['employee_id']);
                $data['employee'] = $emp_info->first_name . ' ' . $emp_info->last_name;
            }
            
            // Get customer info
            if (!empty($sale_info['customer_id'])) {
                $cust_info = $customer->get_info($sale_info['customer_id']);
                if (!empty($cust_info->company_name)) {
                    $data['customer'] = $cust_info->company_name;
                } else {
                    $data['customer'] = $cust_info->first_name . ' ' . $cust_info->last_name;
                }
            }
            
            // Get cart items
            $items = $sale->get_sale_items($sale_id)->getResultArray();
            $data['cart'] = [];
            $subtotal = 0;
            
            foreach ($items as $item) {
                $item_data = [
                    'name' => $item['name'] ?? 'Item',
                    'quantity' => $item['quantity_purchased'] ?? 1,
                    'price' => $item['item_unit_price'] ?? 0,
                    'total' => ($item['quantity_purchased'] ?? 1) * ($item['item_unit_price'] ?? 0),
                    'discounted_total' => $item['item_sub_total'] ?? 0,
                    'print_option' => PRINT_YES,
                    'discount' => $item['discount_percent'] ?? 0,
                    'discount_type' => PERCENT,
                ];
                $data['cart'][] = $item_data;
                $subtotal += $item_data['discounted_total'];
            }
            
            $data['subtotal'] = $subtotal;
            $data['discount'] = $sale_info['discount_amount'] ?? 0;
            $data['total'] = $subtotal - ($sale_info['discount_amount'] ?? 0);
            
            // Get payments
            $payments = $sale->get_sale_payments($sale_id)->getResultArray();
            $data['payments'] = [];
            $total_paid = 0;
            
            foreach ($payments as $payment) {
                $data['payments'][] = [
                    'payment_type' => $payment['payment_type'] ?? 'Cash',
                    'payment_amount' => $payment['payment_amount'] ?? 0,
                ];
                $total_paid += $payment['payment_amount'] ?? 0;
            }
            
            $data['amount_change'] = $total_paid - $data['total'];
            
            // Get taxes
            $data['taxes'] = $sale->get_sale_taxes($sale_id)->getResultArray();
        }
        
        return $data;
    }

    /**
     * Get storage statistics as JSON
     */
    public function getStats(): void
    {
        $this->response->setJSON($this->receipt_pdf->get_storage_stats());
    }

    /**
     * Delete a receipt
     */
    public function postRemoveReceipt(int $sale_id): void
    {
        $result = $this->receipt_pdf->delete_receipt($sale_id);
        $this->response->setJSON([
            'success' => $result,
            'message' => $result ? 'Receipt deleted' : 'Failed to delete receipt'
        ]);
    }

    /**
     * Generate PDFs for all past sales that don't have one
     */
    public function getGenerateAll(): void
    {
        $sale = model('Sale');
        $limit = $this->request->getGet('limit') ?? 100;
        
        // Get recent sales
        $builder = $sale->builder();
        $builder->select('sale_id');
        $builder->where('sale_status', COMPLETED);
        $builder->orderBy('sale_id', 'DESC');
        $builder->limit($limit);
        $sales = $builder->get()->getResultArray();
        
        $generated = 0;
        $skipped = 0;
        $errors = 0;
        
        foreach ($sales as $sale_row) {
            $sale_id = $sale_row['sale_id'];
            
            // Check if receipt already exists
            if ($this->receipt_pdf->get_receipt_file($sale_id)) {
                $skipped++;
                continue;
            }
            
            // Generate receipt
            $sale_data = $this->_load_sale_data($sale_id);
            if ($this->receipt_pdf->save_receipt($sale_id, $sale_data)) {
                $generated++;
            } else {
                $errors++;
            }
        }
        
        echo json_encode([
            'success' => true,
            'generated' => $generated,
            'skipped' => $skipped,
            'errors' => $errors,
            'message' => "Generated: $generated, Skipped (already exist): $skipped, Errors: $errors"
        ]);
    }
}

