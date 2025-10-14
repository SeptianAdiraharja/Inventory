<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\Item_inController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PermintaanController;
use App\Http\Controllers\CartController;

// Role Controllers
use App\Http\Controllers\Role\SuperAdminController;
use App\Http\Controllers\Role\PegawaiController;
use App\Http\Controllers\Role\admin\AdminController;
use App\Http\Controllers\Role\admin\ItemoutController;
use App\Http\Controllers\Role\admin\RequestController;
use App\Http\Controllers\Role\admin\GuestController;
use App\Http\Controllers\Role\admin\ProdukController;
use App\Http\Controllers\Role\admin\RejectController;
use App\Http\Controllers\SearchController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Profile (Authenticated Users)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:super_admin'])
    ->prefix('super-admin')
    ->as('super_admin.')
    ->group(function () {
        Route::get('/dashboard', [SuperAdminController::class, 'index'])->name('dashboard');
        Route::get('/admin/dashboard/modal/{type}', [AdminController::class, 'loadModalData']);
        Route::get('/dashboard/modal/barang_keluar', [AdminController::class, 'barangKeluarModal'])
            ->name('dashboard.modal.barang_keluar');

        // CRUD Master Data
        Route::resources([
            'categories' => CategoryController::class,
            'items'      => ItemController::class,
            'item_ins'   => Item_inController::class,
            'units'      => UnitController::class,
            'suppliers'  => SupplierController::class,
            'users'      => UserController::class,
        ]);

        // Barcode
        Route::get('items/{item}/barcode-pdf', [ItemController::class, 'printBarcode'])
            ->name('items.barcode.pdf');

        Route::get('/export', [ExportController::class, 'index'])
            ->name('export.index');

        // Export Barang Masuk
        Route::get('/export/barang-masuk/excel', [ExportController::class, 'exportBarangMasukExcel'])
            ->name('exports.barang_masuk.excel');
        Route::get('/export/barang-masuk/pdf', [ExportController::class, 'exportBarangMasukPdf'])
            ->name('exports.barang_masuk.pdf');

        // Export Barang Keluar
        Route::get('/export/barang-keluar/excel', [ExportController::class, 'exportBarangKeluarExcel'])
            ->name('exports.barang_keluar.excel');
        Route::get('/export/barang-keluar/pdf', [ExportController::class, 'exportBarangKeluarPdf'])
            ->name('exports.barang_keluar.pdf');
        Route::get('/export/download', [ExportController::class, 'download'])
            ->name('export.download');

        Route::delete('/export/clear', [ExportController::class, 'clearLogs'])->name('export.clear');
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Semua route untuk role:admin ditempatkan di sini.
| Prefix   : /admin
| Namespace: App\Http\Controllers\Role\admin
| Name     : admin.*
|
*/
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->as('admin.')
    ->group(function () {

        /*
        |----------------------------------------------------------------------
        | Dashboard
        |----------------------------------------------------------------------
        */
        Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data', [AdminController::class, 'getChartData']); // data untuk chart
        Route::get('/dashboard/modal/{type}', [AdminController::class, 'loadModalData'])->name('dashboard.modal.data');
        Route::get('/dashboard/modal/barang_keluar', [AdminController::class, 'barangKeluarModal'])
            ->name('dashboard.modal.barang_keluar');

        /*
        |----------------------------------------------------------------------
        | Item Out (Barang Keluar untuk pegawai)
        |----------------------------------------------------------------------
        */
        Route::resource('itemout', ItemoutController::class);
        Route::get('/itemout/{cart}/struk', [ItemoutController::class, 'struk'])->name('itemout.struk');
        Route::post('/itemout/scan/{cart}', [ItemoutController::class, 'scan'])->name('itemout.scan');
        Route::get('/itemout/check-all-scanned/{cart}', [ItemoutController::class, 'checkAllScanned'])->name('itemout.checkAllScanned');
        Route::post('/itemout/release/{cart}', [ItemoutController::class, 'release'])->name('itemout.release');

        /*
        |----------------------------------------------------------------------
        | Requests & Carts
        |----------------------------------------------------------------------
        | - RequestController digunakan untuk mengelola permintaan (approval/reject)
        | - carts resource diarahkan juga ke RequestController
        */
        Route::get('/request', [RequestController::class, 'index'])->name('request');

        // BARU: Routes untuk item-level status updates (menggunakan PATCH)
        Route::patch('/carts/item/{id}/approve', [RequestController::class, 'approveItem'])->name('carts.item.approve');
        Route::patch('/carts/item/{id}/reject', [RequestController::class, 'rejectItem'])->name('carts.item.reject');

        // Resource route untuk Carts/Requests. 'show' digunakan untuk detail table.
        Route::resource('carts', RequestController::class);

        /*
        |----------------------------------------------------------------------
        | Guests (Tamu)
        // 🔎 Search guest (digunakan untuk navbar search)
        |----------------------------------------------------------------------
        */
        Route::get('/guests/search', [SearchController::class, 'searchGuests'])
            ->name('guests.search');

        Route::resource('guests', GuestController::class)->except('show');


        /*
        |----------------------------------------------------------------------
        | Produk Guest
        |----------------------------------------------------------------------
        | - Menampilkan produk yang dapat dipilih oleh tamu (guest)
        | - Scan barang masuk ke guest_carts
        | - Release barang dari guest_carts ke item_out_guests
        */
        // Produk Admin
        Route::get('/produk', [ProdukController::class, 'index'])->name('produk.index');
        Route::get('/produk/guest/{id}', [ProdukController::class, 'showByGuest'])->name('produk.byGuest');
        Route::post('/produk/guest/{id}/scan', [ProdukController::class, 'scan'])->name('produk.scan');
        Route::get('/produk/guest/{id}/cart', [ProdukController::class, 'showCart'])->name('produk.cart'); // <- AJAX modal
        Route::post('/produk/guest/{id}/release', [ProdukController::class, 'release'])->name('produk.release');

        /*
        |----------------------------------------------------------------------
        | Reject Barang
        |----------------------------------------------------------------------
        */
        Route::get('/rejects/scan', [RejectController::class, 'scanPage'])->name('rejects.scan');
        Route::post('/rejects/process', [RejectController::class, 'processScan'])->name('rejects.process');
        Route::get('/rejects/check/{barcode}', [RejectController::class, 'checkBarcode'])->name('admin.rejects.check');


         /*
        |----------------------------------------------------------------------
        | Export (Barang Keluar)
        |----------------------------------------------------------------------
        */
        Route::get('/out', [ExportController::class, 'exportOut'])->name('export.out');
        Route::post('/out/clear', [ExportController::class, 'clearOutHistory'])->name('export.out.clear');
        Route::get('/export/out', [ExportController::class, 'exportOut'])->name('export.out');
        Route::get('/export/barang-keluar/excel', [ExportController::class, 'exportBarangKeluarExcelAdmin'])
            ->name('barang_keluar.excel');
        Route::get('/export/barang-keluar/pdf', [ExportController::class, 'exportBarangKeluarPdfAdmin'])
            ->name('barang_keluar.pdf');



});


/*
|--------------------------------------------------------------------------
| Pegawai Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:pegawai'])
    ->prefix('pegawai')
    ->as('pegawai.')
    ->group(function () {
        Route::get('/dashboard', [PegawaiController::class, 'index'])->name('dashboard');
        Route::resource('cart', CartController::class);

        // Produk & Permintaan
        Route::get('/produk', [PermintaanController::class, 'index'])->name('produk');

        Route::get('/produk/search', [SearchController::class, 'index'])
            ->name('produk.search');

        Route::get('/permintaan', [PermintaanController::class, 'permintaan'])->name('permintaan.index');
        Route::get('/permintaan/pending', [PermintaanController::class, 'pendingPermintaan'])->name('permintaan.pending');
        Route::get('/permintaan/{id}', [PermintaanController::class, 'detailPermintaan'])->name('permintaan.detail');

        Route::post('/permintaan/create', [PermintaanController::class, 'createPermintaan'])->name('permintaan.create');
        Route::post('/permintaan/{id}/submit', [PermintaanController::class, 'submitPermintaan'])->name('permintaan.submit');

        Route::get('/notifications/read', [PegawaiController::class, 'readNotifications'])
        ->name('notifications.read');

});

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';
