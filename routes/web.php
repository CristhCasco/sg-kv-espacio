<?php

use App\Models\SaleCredit;
use App\Http\Livewire\Dash;
use Barryvdh\DomPDF\Facade\Pdf;

// ✅ Modelos y controladores
use App\Http\Livewire\PosController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\RolesController;
use App\Http\Livewire\UsersController;
use App\Http\Controllers\HomeController;
use App\Http\Livewire\AsignarController;

// ✅ Componentes Livewire
use App\Http\Livewire\CashoutController;
use App\Http\Livewire\ReportsController;
use App\Http\Livewire\PermisosController;
use App\Http\Livewire\ProductsController;
use App\Http\Controllers\ExportController;
use App\Http\Livewire\CustomersController;
use App\Http\Livewire\PurchasesController;
use App\Http\Livewire\SuppliersController;
use App\Http\Livewire\CategoriesController;
use App\Http\Livewire\SaleCreditsController;
use App\Http\Controllers\PdfCreditController;
use App\Http\Livewire\SalesReportsController;
use App\Http\Controllers\SaleCancelController;
use App\Http\Controllers\TicketSaleController;
use App\Http\Livewire\DenominationsController;
use App\Http\Controllers\InvoicePrintController;
use App\Http\Livewire\PurchasesReportController;
use App\Http\Livewire\PurchasesCreditsController;
use App\Http\Controllers\TicketPurchaseController;
use App\Http\Controllers\reports\BookIvaController;
use App\Http\Livewire\Reports\Invoice\LegalInvoicesReport;
use App\Http\Controllers\inventory\Imports\InventoryController;
use App\Http\Livewire\PreviewSalesController;
use App\Http\Livewire\CreatePreviewSale;
use App\Http\Livewire\ShowPreviewSale;
use App\Http\Livewire\EditPreviewSale;
use App\Http\Livewire\ConfirmPreviewSale;
use App\Http\Livewire\ReportPreviewSale;
use App\Http\Livewire\SettingsController;




// ✅ Página principal (login)
Route::get('/', function () {
    return view('auth.login');
});

// ✅ Rutas para invitados
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', 'LoginController@login');
});

// ✅ Deshabilitar el registro de nuevos usuarios
Auth::routes(['register' => false]);

// ✅ Página de inicio luego del login
Route::get('/home', Dash::class);

// ✅ Ruta PÚBLICA para ver ticket PDF de venta (para WhatsApp)
//Route::get('/pos/ventas/{hash}/ticket', [TicketSaleController::class, 'imprimirTicket'])->name('pos.ticket');
Route::get('/pos/ventas/{hash}/ticket', [TicketSaleController::class, 'printTicket'])->name('pos.ticket');

// ✅ Ruta PÚBLICA para ver comprobante de pago de crédito (para WhatsApp)
Route::get('/creditos/pagos/{hash}/comprobante', [PdfCreditController::class, 'imprimirComprobante'])->name('creditos.comprobante');

// ✅ Rutas protegidas por login
Route::middleware(['auth'])->group(function () {
    // Búsqueda de producto por código (escáner de Preventas). Requiere sesión iniciada
    // y devuelve solo los datos necesarios (no expone el costo).
    Route::get('api/products/barcode/{barcode}', function ($barcode) {
        $product = \App\Models\Product::where('barcode', trim($barcode))
            ->first(['id', 'name', 'barcode', 'price', 'price_per_kg', 'stock', 'is_weighted']);

        if ($product) {
            return response()->json($product);
        }

        // Las preventas cargan de a 1 unidad: las etiquetas de balanza (peso/cantidad) se cargan en el POS
        $label = app(\App\Services\ScaleBarcodeService::class)->parse($barcode);
        if ($label && $label['product']) {
            return response()->json([
                'error' => 'Es una etiqueta de balanza de "' . $label['product']->name . '": cárgala en Ventas (POS).',
            ], 422);
        }

        return response()->json(['error' => 'El producto ' . $barcode . ' no está registrado'], 404);
    })->name('api.products.barcode');


    // CRUDs
    Route::get("categories", CategoriesController::class)->name('categories');
    Route::get("products", ProductsController::class)->name('products');
    Route::get("customers", CustomersController::class)->name('customers');
    Route::get("suppliers", SuppliersController::class)->name('suppliers');
    Route::get("pos", PosController::class)->name('pos');
    Route::get("purchases", PurchasesController::class)->name('purchases');

    // 🎫 Preventas
    Route::get("preview-sales", PreviewSalesController::class)->name('preview-sales');
    Route::get("preview-sales/create", CreatePreviewSale::class)->name('preview-sales.create');
    Route::get("preview-sales/{previewSale}", ShowPreviewSale::class)->name('preview-sales.show');
    Route::get("preview-sales/{previewSale}/edit", EditPreviewSale::class)->name('preview-sales.edit');
    Route::get("preview-sales/{previewSale}/confirm", ConfirmPreviewSale::class)->name('preview-sales.confirm-form');
    Route::get("preview-sales/{previewSale}/to-pos", [PreviewSalesController::class, 'quickConfirm'])->name('preview-sales.to-pos');
    Route::get("preview-sales/report/all", ReportPreviewSale::class)->name('preview-sales.report');

    // Créditos
    Route::get('/credits/sales', SaleCreditsController::class)->name('sale-credits');
    Route::get('/credits/purchases', PurchasesCreditsController::class)->name('purchase-credits');

    // Área de administración Tecnica
    Route::middleware(['role:TI'])->group(function () {
        Route::get("users", UsersController::class)->name('users');
        Route::get("roles", RolesController::class)->name('roles');
        Route::get("permissions", PermisosController::class)->name('permissions');
        Route::get("assign", AsignarController::class)->name('assign');
        Route::get("denominations", DenominationsController::class)->name('denominations');
        Route::get("settings", SettingsController::class)->name('settings');
    });

    // Reportes
    Route::get("cashout", CashoutController::class)->name('cashout');
    Route::get("cashout/export-pdf", function () {
        $data = session('cashout_pdf_data');
        if (!$data) {
            return redirect()->route('cashout')->with('error', 'Datos no disponibles');
        }
        
        $fileName = 'Corte_Caja_' . $data['type'] . '_' . $data['fromDate'] . '_' . $data['toDate'] . '.pdf';
        
        try {
            return Pdf::loadView('exports.cashout-pdf', $data)
                ->setPaper('a4', 'portrait')
                ->stream($fileName);
        } catch (\Exception $e) {
            \Log::error('Error en PDF Export: ' . $e->getMessage());
            return redirect()->route('cashout')->with('error', 'Error al generar PDF');
        }
    })->name('cashout.export-pdf');
    
    Route::get("reports", [HomeController::class, 'reports'])->name('reports');
    Route::get("sales/reports", SalesReportsController::class)->name('sales.reports');
    Route::get("purchases/reports", PurchasesReportController::class)->name('purchases.reports');

    // PDF de reportes
    Route::get('report/pdf/{user}/{type}', [ExportController::class, 'reportPDF']);
    Route::get('report/pdf/{user}/{type}/{f1}/{f2}', [ExportController::class, 'reportPDF']);
    Route::get('report/purchases/pdf/{user}/{type}', [ExportController::class, 'reportPurchasePDF']);
    Route::get('report/purchases/pdf/{user}/{type}/{f1}/{f2}', [ExportController::class, 'reportPurchasePDF']);
    Route::get('/reports/inventory', [SalesReportsController::class, 'inventory'])->name('inventory.reports');

    // Exportar productos
    Route::get('export/products', [ExportController::class, 'exportProducts'])->name('export.products');
    Route::get('export/products/pdf', [ExportController::class, 'exportProductsPdf'])->name('export.products.pdf');

    // Inventario
    Route::get('inventory', [InventoryController::class, 'showImportForm'])->name('inventory.show');
    Route::post('inventory/import', [InventoryController::class, 'import'])->name('inventory.import');
    Route::post('inventory/saveScanned', [InventoryController::class, 'saveScanned'])->name('inventory.saveScanned');
    Route::post('inventory/generateCount', [InventoryController::class, 'generateCount'])->name('inventory.generateCount');
    Route::post('/inventory/download', [InventoryController::class, 'downloadInventoryResults'])->name('inventory.download');

    // ✅ Descargar ticket de venta (requiere login)
    //Route::get('/pos/ventas/{hash}/ticket-descargar', [TicketSaleController::class, 'generarTicket'])->name('pos.ticket.descargar');
    Route::get('/pos/ventas/{hash}/ticket-descargar', [TicketSaleController::class, 'downloadTicket'])->name('pos.ticket.descargar');

    // Datos del ticket de venta para PrinterNinja (print://)
    Route::get('/pos/ventas/{hash}/ticket-ninja', [TicketSaleController::class, 'ninjaPayload'])->name('pos.ticket.ninja');
    Route::get('/compras/{hash}/ticket-ninja', [TicketPurchaseController::class, 'ninjaPayload'])->name('purchase.ticket.ninja');
    Route::get('/creditos/pagos/{hash}/comprobante-ninja', [PdfCreditController::class, 'ninjaPayload'])->name('creditos.comprobante.ninja');

    // ✅ Ticket de compras (requiere login)
    Route::get('/compras/{hash}/ticket', [TicketPurchaseController::class, 'imprimirTicket'])->name('purchase.ticket');

    // 📄 Reporte de Facturas Legales / Libro IVA
    Route::get('/reports/legal-invoices', LegalInvoicesReport::class)
        ->name('reports.legal-invoices');

    Route::get('/reports/legal-invoices/export', [BookIvaController::class, 'exportLibroIvaVentas'])
        ->name('reports.legal-invoices.export');

    // 🧾 Impresión de Facturas desde el POS (válidas y activas)
    Route::get('/invoice-legal/{hash}', [PosController::class, 'generateInvoicePDF'])
        ->name('invoice.legal');

    Route::get('/invoice-summary/{hash}', [PosController::class, 'generateSummaryInvoicePDF'])
        ->name('invoice.summary');

    // Datos de las facturas para PrinterNinja (print://)
    Route::get('/invoice-legal/{hash}/ninja', [TicketSaleController::class, 'invoiceNinjaPayload'])->name('invoice.legal.ninja');
    Route::get('/invoice-summary/{hash}/ninja', [TicketSaleController::class, 'invoiceSummaryNinjaPayload'])->name('invoice.summary.ninja');


});
