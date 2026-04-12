<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->setDefaultController('Login');
$routes->setAutoRoute(true);

$routes->get('/', 'Login::index');
$routes->get('login', 'Login::index');
$routes->post('login', 'Login::index');

$routes->add('no_access/index/(:segment)', 'No_access::index/$1');
$routes->add('no_access/index/(:segment)/(:segment)', 'No_access::index/$1/$2');

$routes->add('reports/summary_(:any)/(:any)/(:any)', 'Reports::Summary_$1/$2/$3/$4');
$routes->add('reports/summary_expenses_categories', 'Reports::date_input_only');
$routes->add('reports/summary_payments', 'Reports::date_input_only');
$routes->add('reports/summary_discounts', 'Reports::summary_discounts_input');
$routes->add('reports/summary_(:any)', 'Reports::date_input');

$routes->add('reports/graphical_(:any)/(:any)/(:any)', 'Reports::Graphical_$1/$2/$3/$4');
$routes->add('reports/graphical_summary_expenses_categories', 'Reports::date_input_only');
$routes->add('reports/graphical_summary_discounts', 'Reports::summary_discounts_input');
$routes->add('reports/graphical_(:any)', 'Reports::date_input');

$routes->add('reports/inventory_(:any)/(:any)', 'Reports::Inventory_$1/$2');
$routes->add('reports/inventory_low', 'Reports::inventory_low');
$routes->add('reports/inventory_summary', 'Reports::inventory_summary_input');
$routes->add('reports/inventory_summary/(:any)/(:any)/(:any)', 'Reports::inventory_summary/$1/$2/$3');

$routes->add('reports/detailed_(:any)/(:any)/(:any)/(:any)', 'Reports::Detailed_$1/$2/$3/$4');
$routes->add('reports/detailed_sales', 'Reports::date_input_sales');
$routes->add('reports/detailed_receivings', 'Reports::date_input_recv');

$routes->add('reports/specific_(:any)/(:any)/(:any)/(:any)', 'Reports::Specific_$1/$2/$3/$4');
$routes->add('reports/specific_customers', 'Reports::specific_customer_input');
$routes->add('reports/specific_employees', 'Reports::specific_employee_input');
$routes->add('reports/specific_discounts', 'Reports::specific_discount_input');
$routes->add('reports/specific_suppliers', 'Reports::specific_supplier_input');

$routes->add('reports/comprehensive_sales_input', 'Reports::comprehensive_sales_input');
$routes->add('reports/comprehensive_sales/(:any)/(:any)/(:any)/(:any)', 'Reports::comprehensive_sales/$1/$2/$3/$4');
$routes->add('reports/customer_history_input', 'Reports::customer_history_input');
$routes->add('reports/customer_history/(:any)', 'Reports::customer_history/$1');

// ===========================================
// ALL SALES ROUTES - Complete list
// ===========================================

// Main pages
$routes->get('sales', 'Sales::getIndex');
$routes->get('sales/index', 'Sales::getIndex');
$routes->get('sales/manage', 'Sales::getManage');

// Search and suggestions
$routes->get('sales/row/(:num)', 'Sales::getRow/$1');
$routes->get('sales/search', 'Sales::getSearch');
$routes->get('sales/itemSearch', 'Sales::getItemSearch');
$routes->get('sales/item_search', 'Sales::getItemSearch');
$routes->post('sales/suggest_search', 'Sales::suggest_search');
$routes->add('sales/suggest_search', 'Sales::suggest_search');

// Customer selection
$routes->post('sales/select_customer', 'Sales::postSelectCustomer');
$routes->post('sales/selectCustomer', 'Sales::postSelectCustomer');
$routes->add('sales/select_customer', 'Sales::postSelectCustomer');
$routes->add('sales/selectCustomer', 'Sales::postSelectCustomer');
$routes->get('sales/removeCustomer', 'Sales::getRemoveCustomer');
$routes->get('sales/remove_customer', 'Sales::getRemoveCustomer');

// Mode changes
$routes->post('sales/change_mode', 'Sales::postChangeMode');
$routes->post('sales/changeMode', 'Sales::postChangeMode');
$routes->add('sales/change_mode', 'Sales::postChangeMode');
$routes->add('sales/changeMode', 'Sales::postChangeMode');
$routes->get('sales/change_register_mode/(:num)', 'Sales::change_register_mode/$1');
$routes->get('sales/changeRegisterMode/(:num)', 'Sales::change_register_mode/$1');

// Settings
$routes->post('sales/set_comment', 'Sales::postSetComment');
$routes->post('sales/setComment', 'Sales::postSetComment');
$routes->add('sales/set_comment', 'Sales::postSetComment');
$routes->add('sales/setComment', 'Sales::postSetComment');
$routes->post('sales/set_invoice_number', 'Sales::postSetInvoiceNumber');
$routes->post('sales/setInvoiceNumber', 'Sales::postSetInvoiceNumber');
$routes->add('sales/set_invoice_number', 'Sales::postSetInvoiceNumber');
$routes->add('sales/setInvoiceNumber', 'Sales::postSetInvoiceNumber');
$routes->post('sales/set_payment_type', 'Sales::postSetPaymentType');
$routes->post('sales/setPaymentType', 'Sales::postSetPaymentType');
$routes->post('sales/set_print_after_sale', 'Sales::postSetPrintAfterSale');
$routes->post('sales/setPrintAfterSale', 'Sales::postSetPrintAfterSale');
$routes->add('sales/set_print_after_sale', 'Sales::postSetPrintAfterSale');
$routes->add('sales/setPrintAfterSale', 'Sales::postSetPrintAfterSale');
$routes->post('sales/set_price_work_orders', 'Sales::postSetPriceWorkOrders');
$routes->post('sales/setPriceWorkOrders', 'Sales::postSetPriceWorkOrders');
$routes->post('sales/set_email_receipt', 'Sales::postSetEmailReceipt');
$routes->post('sales/setEmailReceipt', 'Sales::postSetEmailReceipt');
$routes->add('sales/set_email_receipt', 'Sales::postSetEmailReceipt');
$routes->add('sales/setEmailReceipt', 'Sales::postSetEmailReceipt');

// Payments
$routes->post('sales/add_payment', 'Sales::postAddPayment');
$routes->post('sales/addPayment', 'Sales::postAddPayment');
$routes->post('sales/add_split_payment', 'Sales::postAddSplitPayment');
$routes->post('sales/addSplitPayment', 'Sales::postAddSplitPayment');
$routes->get('sales/delete_payment/(:any)', 'Sales::getDeletePayment/$1');
$routes->get('sales/deletePayment/(:any)', 'Sales::getDeletePayment/$1');

// Items
$routes->post('sales/add', 'Sales::postAdd');
$routes->add('sales/add', 'Sales::postAdd');
$routes->post('sales/edit_item/(:any)', 'Sales::postEditItem/$1');
$routes->post('sales/editItem/(:any)', 'Sales::postEditItem/$1');
$routes->add('sales/edit_item/(:any)', 'Sales::postEditItem/$1');
$routes->add('sales/editItem/(:any)', 'Sales::postEditItem/$1');
$routes->get('sales/delete_item/(:num)', 'Sales::getDeleteItem/$1');
$routes->get('sales/deleteItem/(:num)', 'Sales::getDeleteItem/$1');
$routes->post('sales/change_item_number', 'Sales::postChangeItemNumber');
$routes->post('sales/changeItemNumber', 'Sales::postChangeItemNumber');
$routes->add('sales/change_item_number', 'Sales::postChangeItemNumber');
$routes->add('sales/changeItemNumber', 'Sales::postChangeItemNumber');
$routes->post('sales/change_item_name', 'Sales::postChangeItemName');
$routes->post('sales/changeItemName', 'Sales::postChangeItemName');
$routes->add('sales/change_item_name', 'Sales::postChangeItemName');
$routes->add('sales/changeItemName', 'Sales::postChangeItemName');
$routes->post('sales/change_item_description', 'Sales::postChangeItemDescription');
$routes->post('sales/changeItemDescription', 'Sales::postChangeItemDescription');
$routes->add('sales/change_item_description', 'Sales::postChangeItemDescription');
$routes->add('sales/changeItemDescription', 'Sales::postChangeItemDescription');

// Complete sale
$routes->post('sales/complete', 'Sales::postComplete');
$routes->add('sales/complete', 'Sales::postComplete');

// PDF and receipts
$routes->get('sales/send_pdf/(:num)', 'Sales::getSendPdf/$1');
$routes->get('sales/send_pdf/(:num)/(:any)', 'Sales::getSendPdf/$1/$2');
$routes->get('sales/sendPdf/(:num)', 'Sales::getSendPdf/$1');
$routes->get('sales/sendPdf/(:num)/(:any)', 'Sales::getSendPdf/$1/$2');
$routes->get('sales/send_receipt/(:num)', 'Sales::getSendReceipt/$1');
$routes->get('sales/sendReceipt/(:num)', 'Sales::getSendReceipt/$1');
$routes->get('sales/receipt/(:num)', 'Sales::getReceipt/$1');
$routes->get('sales/invoice/(:num)', 'Sales::getInvoice/$1');

// Edit and delete
$routes->get('sales/edit/(:num)', 'Sales::getEdit/$1');
$routes->post('sales/delete/(:num)', 'Sales::postDelete/$1');
$routes->post('sales/delete', 'Sales::postDelete');
$routes->add('sales/delete/(:num)', 'Sales::postDelete/$1');
$routes->add('sales/delete', 'Sales::postDelete');
$routes->get('sales/restore/(:num)', 'Sales::restore/$1');
$routes->add('sales/restore/(:num)', 'Sales::restore/$1');

// Save
$routes->post('sales/save/(:num)', 'Sales::postSave/$1');
$routes->post('sales/save', 'Sales::postSave');
$routes->add('sales/save/(:num)', 'Sales::postSave/$1');
$routes->add('sales/save', 'Sales::postSave');

// Cancel
$routes->post('sales/cancel', 'Sales::postCancel');
$routes->add('sales/cancel', 'Sales::postCancel');

// Suspend
$routes->post('sales/suspend', 'Sales::postSuspend');
$routes->add('sales/suspend', 'Sales::postSuspend');
$routes->get('sales/suspended', 'Sales::getSuspended');
$routes->get('sales/discard_suspended_sale', 'Sales::getDiscardSuspendedSale');
$routes->get('sales/discardSuspendedSale', 'Sales::getDiscardSuspendedSale');
$routes->post('sales/unsuspend', 'Sales::postUnsuspend');
$routes->add('sales/unsuspend', 'Sales::postUnsuspend');

// Help and misc
$routes->get('sales/sales_keyboard_help', 'Sales::getSalesKeyboardHelp');
$routes->get('sales/salesKeyboardHelp', 'Sales::getSalesKeyboardHelp');
$routes->post('sales/check_invoice_number', 'Sales::postCheckInvoiceNumber');
$routes->post('sales/checkInvoiceNumber', 'Sales::postCheckInvoiceNumber');
$routes->add('sales/check_invoice_number', 'Sales::postCheckInvoiceNumber');
$routes->add('sales/checkInvoiceNumber', 'Sales::postCheckInvoiceNumber');
