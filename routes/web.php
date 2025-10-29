<?php

use App\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::middleware('auth:sanctum')->group(function () {
    Route::redirect('/dashboard', '/');
    Route::get('/language/{language}', [Controllers\AjaxController::class, 'language']);
    Route::get('/', [Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/activity', [Controllers\DashboardController::class, 'activity'])->name('activity');

    Route::get('/alerts', [Controllers\AjaxController::class, 'alerts'])->name('alerts');
    Route::get('/alerts/{warehouse}', [Controllers\AjaxController::class, 'warehouse'])->name('alerts.list');

    Route::get('/settings', [Controllers\SettingController::class, 'index'])->name('settings');
    Route::post('settings', [Controllers\SettingController::class, 'store'])->name('settings.store');

    Route::post('search/items', [Controllers\AjaxController::class, 'items'])->name('items.search');
    Route::get('items/{item}/trail', [Controllers\ItemController::class, 'trail'])->name('items.trail');
    Route::post('search/contacts', [Controllers\AjaxController::class, 'contacts'])->name('contacts.search');
    Route::delete('media/{media}/delete', [Controllers\AjaxController::class, 'delete'])->name('media.delete');
    Route::get('media/{media}/download', [Controllers\AjaxController::class, 'download'])->name('media.download');
    Route::delete('users/{user}/disable_2fa', [Controllers\UserController::class, 'disable2FA'])->name('users.disable.2fa');
    Route::delete('items/{item}/photo', [Controllers\ItemController::class, 'destroyPhoto'])->name('items.deletePhoto');


    Route::get('inbound/import', [Controllers\CheckinController::class, 'import'])->name('checkins.import.excel');

    Route::post('inbound/import/save', [Controllers\CheckinController::class, 'importStore'])->name('checkins.import.save');
    Route::post('inbound/import/save', [Controllers\CheckinController::class, 'importStore'])->name('checkins.import.store');

    Route::get('outbound/import', [Controllers\CheckoutController::class, 'import'])->name('checkouts.import.excel');

    Route::post('outbound/import/save', [Controllers\CheckoutController::class, 'importStore'])->name('checkouts.import.save');
    Route::post('outbound/import/save', [Controllers\CheckoutController::class, 'importStore'])->name('checkouts.import.store');

    Route::extendedResources([
        'items'       => Controllers\ItemController::class,
        'roles'       => Controllers\RoleController::class,
        'units'       => Controllers\UnitController::class,
        'users'       => Controllers\UserController::class,
        'contacts'    => Controllers\ContactController::class,
        'checkins'    => Controllers\CheckinController::class,
        'checkouts'   => Controllers\CheckoutController::class,
        'categories'  => Controllers\CategoryController::class,
        'transfers'   => Controllers\TransferController::class,
        'warehouses'  => Controllers\WarehouseController::class,
        'adjustments' => Controllers\AdjustmentController::class,
        'longstaycargo' => Controllers\LongStayCargoController::class,
    ]);

    Route::portResources([
        'items'      => Controllers\ItemPortController::class,
        'units'      => Controllers\UnitPortController::class,
        'contacts'   => Controllers\ContactPortController::class,
        'checkins'   => Controllers\CheckinPortController::class,
        'checkouts'  => Controllers\CheckoutPortController::class,
        'categories' => Controllers\CategoryPortController::class,
        'warehouses' => Controllers\WarehousePortController::class,
        'longstaycargo' => Controllers\LongStayCargoController::class,
    ]);

    // Notifications
    Route::get('notifications/inbound/{checkin}', [Controllers\NotificationController::class, 'checkin'])->name('notifications.checkin');
    Route::get('notifications/outbound/{checkout}', [Controllers\NotificationController::class, 'checkout'])->name('notifications.checkout');
    Route::get('notifications/transfer/{transfer}', [Controllers\NotificationController::class, 'transfer'])->name('notifications.transfer');
    Route::get('notifications/adjustment/{adjustment}', [Controllers\NotificationController::class, 'adjustment'])->name('notifications.adjustment');
    // Notifications Preview
    Route::get('preview/low_stock', [Controllers\NotificationController::class, 'stock'])->name('notifications.stock.preview');
    Route::get('preview/inbound/{checkin}', [Controllers\NotificationController::class, 'checkin'])->name('notifications.checkin.preview');
    Route::get('preview/outbound/{checkout}', [Controllers\NotificationController::class, 'checkout'])->name('notifications.checkout.preview');
    Route::get('preview/transfer/{transfer}', [Controllers\NotificationController::class, 'transfer'])->name('notifications.transfer.preview');
    Route::get('preview/adjustment/{adjustment}', [Controllers\NotificationController::class, 'adjustment'])->name('notifications.adjustment.preview');

    // Reports
    Route::get('reports', [Controllers\ReportController::class, 'index'])->name('reports.index');
    Route::match(['GET', 'POST'], 'reports/inbound', [Controllers\ReportController::class, 'checkin'])->name('reports.checkin');
    Route::match(['GET', 'POST'], 'reports/outbound', [Controllers\ReportController::class, 'checkout'])->name('reports.checkout');
    Route::match(['GET', 'POST'], 'reports/transfer', [Controllers\ReportController::class, 'transfer'])->name('reports.transfer');
    Route::match(['GET', 'POST'], 'reports/adjustment', [Controllers\ReportController::class, 'adjustment'])->name('reports.adjustment');
    // Route::get('reports/checkin/export', [Controllers\ReportController::class, 'exportCheckin'])->name('reports.checkin.export');
    Route::get('reports/checkin/export/xlsx', [Controllers\ReportController::class, 'exportCheckinXLSX'])->name('reports.checkin.export.xlsx');
    Route::get('reports/checkout/export/xlsx', [Controllers\ReportController::class, 'exportCheckoutXLSX'])->name('reports.checkout.export.xlsx');
    Route::get('reports/transfer/export/xlsx', [Controllers\ReportController::class, 'exportTransferXLSX'])->name('reports.transfer.export.xlsx');
    Route::get('reports/adjustment/export/xlsx', [Controllers\ReportController::class, 'exportAdjustmentXLSX'])->name('reports.adjustment.export.xlsx');

    Route::get('reports/checkin/export/pdf', [Controllers\ReportController::class, 'exportCheckinPDF'])->name('reports.checkin.export.pdf');
    Route::get('reports/checkout/export/pdf', [Controllers\ReportController::class, 'exportCheckoutPDF'])->name('reports.checkout.export.pdf');
    Route::get('reports/transfer/export/pdf', [Controllers\ReportController::class, 'exportTransferPDF'])->name('reports.transfer.export.pdf');
    Route::get('reports/adjustment/export/pdf', [Controllers\ReportController::class, 'exportAdjustmentPDF'])->name('reports.adjustment.export.pdf');



    // Role Permissions
    Route::post('roles/{role}/permissions', [Controllers\RoleController::class, 'permissions'])->name('roles.permissions');
});

// Route::get('reports', [Controllers\ReportController::class, 'index'])->name('reports.index');

// Routes to run storage & migration commands
Route::view('/notification', 'notification')->name('notification');
Route::prefix('commands')->middleware(['throttle:6,10', 'purchased'])->group(function () {
    Route::get('storage_link', function () {
        Artisan::call('storage:link');

        return redirect('notification')->with('message', Artisan::output());
    });
    Route::get('update_database', function () {
        Artisan::call('migrate --force');

        return redirect('notification')->with('message', Artisan::output());
    });
});

// 