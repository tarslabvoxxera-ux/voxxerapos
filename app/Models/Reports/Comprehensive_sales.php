<?php

namespace App\Models\Reports;

use App\Models\Sale;

/**
 * Comprehensive Sales Report
 * Captures everything: Employee, Customer, Items, Payment Types, Taxes, etc.
 */
class Comprehensive_sales extends Report
{
    /**
     * Create temp tables for the report
     */
    public function create(array $inputs): void
    {
        $sale = model(Sale::class);
        $sale->create_temp_table($inputs);
    }

    /**
     * Get all comprehensive sales data
     */
    public function getData(array $inputs): array
    {
        $builder = $this->db->table('sales_items_temp');
        
        $builder->select('
            sale_id,
            invoice_number,
            DATE_FORMAT(sale_time, "%d/%m/%Y") as sale_date,
            DATE_FORMAT(sale_time, "%H:%i:%s") as sale_time_only,
            sale_time as full_datetime,
            employee_name as billed_by,
            customer_name,
            customer_email,
            customer_phone,
            name as item_name,
            category,
            item_number as item_code,
            quantity_purchased,
            item_cost_price,
            item_unit_price,
            discount as discount_percent,
            subtotal,
            tax,
            total,
            cost,
            profit,
            payment_type,
            comment
        ');
        
        $builder->orderBy('sale_time', 'DESC');
        $builder->orderBy('sale_id', 'DESC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get summary totals
     */
    public function getSummaryData(array $inputs): array
    {
        $builder = $this->db->table('sales_items_temp');
        
        $builder->select('
            COUNT(DISTINCT sale_id) as total_transactions,
            SUM(quantity_purchased) as total_items_sold,
            SUM(subtotal) as total_subtotal,
            SUM(tax) as total_tax,
            SUM(total) as total_sales,
            SUM(cost) as total_cost,
            SUM(profit) as total_profit
        ');
        
        $result = $builder->get()->getRowArray();
        
        return [
            'total_transactions' => $result['total_transactions'] ?? 0,
            'total_items_sold' => $result['total_items_sold'] ?? 0,
            'total_subtotal' => $result['total_subtotal'] ?? 0,
            'total_tax' => $result['total_tax'] ?? 0,
            'total_sales' => $result['total_sales'] ?? 0,
            'total_cost' => $result['total_cost'] ?? 0,
            'total_profit' => $result['total_profit'] ?? 0
        ];
    }

    /**
     * Get column headers for Excel export
     */
    public function getDataColumns(): array
    {
        return [
            'sale_id' => 'Sale ID',
            'invoice_number' => 'Invoice Number',
            'sale_date' => 'Date',
            'sale_time_only' => 'Time',
            'billed_by' => 'Employee (Billed By)',
            'customer_name' => 'Customer Name',
            'customer_email' => 'Customer Email',
            'customer_phone' => 'Customer Phone',
            'item_name' => 'Item Name',
            'category' => 'Category',
            'item_code' => 'Item Code/Barcode',
            'quantity_purchased' => 'Quantity',
            'item_cost_price' => 'Cost Price',
            'item_unit_price' => 'Selling Price',
            'discount_percent' => 'Discount %',
            'subtotal' => 'Subtotal',
            'tax' => 'Tax',
            'total' => 'Total',
            'cost' => 'Total Cost',
            'profit' => 'Profit',
            'payment_type' => 'Payment Type',
            'comment' => 'Comments'
        ];
    }
}


