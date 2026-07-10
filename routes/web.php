<?php

use App\Http\Controllers\AssetBorrowController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetDisposalController;
use App\Http\Controllers\AssetIntakeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataUpdateController;
use App\Http\Controllers\DepartmentCodeController;
use App\Http\Controllers\InstallController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TransferOrderController;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 安装向导（无需登录）
Route::get('/install', [InstallController::class, 'index']);
Route::post('/install/setup', [InstallController::class, 'setupDatabase']);
Route::post('/install/complete', [InstallController::class, 'createAdmin']);

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])->name('dashboard');

// 数据更新接收（公开端点，供 shell 脚本调用，无需登录）
Route::post('/data-update/receive', [DataUpdateController::class, 'receive']);

Route::middleware('auth')->group(function () {
    // 个人资料
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // API 文档
    Route::get('/api-docs', fn() => view('api-docs.index'))->name('api-docs');

    // Token 管理（web session）
    Route::get('/api/tokens', function () {
        return auth()->user()->tokens()->select('id', 'name', 'created_at')->get();
    });
    // 编码 API（供自动补全用）
    Route::get('/api/depts', function () {
        return \App\Models\DepartmentCode::type('department')->select('code', 'name')->orderBy('code')->get();
    });
    Route::get('/api/codes', function (Request $request) {
        $type = $request->get('type', 'category');
        return \App\Models\DepartmentCode::type($type)->select('code', 'name')->orderBy('code')->get();
    });

    Route::post('/api/tokens', function (Request $request) {
        $token = auth()->user()->createToken($request->name ?? 'api-token');
        return response()->json(['id' => $token->accessToken->id, 'name' => $request->name ?? 'api-token', 'token' => $token->plainTextToken]);
    });
    Route::delete('/profile/tokens', function (Request $request) {
        auth()->user()->tokens()->where('id', $request->token_id)->delete();
        return back()->with('success', 'Token 已撤销');
    });

    // 资产（查看）
    Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');

    // 资产搜索（JSON API）- 必须在 {asset} 之前
    Route::get('/assets/search', [AssetController::class, 'searchJson'])->name('assets.searchJson');
    Route::get('/assets/json', [AssetController::class, 'jsonIndex'])->name('assets.jsonIndex');

    // 资产盘点 - 必须在 {asset} 之前
    Route::get('/assets/check', [AssetController::class, 'check'])->name('assets.check');
    Route::get('/assets/check/print', [AssetController::class, 'checkPrint'])->name('assets.checkPrint');

    // 资产导出 - 必须在 {asset} 之前
    Route::get('/assets/export/print', [AssetController::class, 'export'])->name('assets.export');
    Route::get('/assets/export/csv', [AssetController::class, 'exportCsv'])->name('assets.exportCsv');
    Route::get('/assets/export/preview', [AssetController::class, 'exportPreview'])->name('assets.exportPreview');
    Route::get('/assets/template/csv', [AssetController::class, 'downloadTemplate'])->name('assets.template');

    // 资产调拨单
    Route::get('/transfers', [TransferOrderController::class, 'index'])->name('transfers.index');
    Route::get('/transfers/create', [TransferOrderController::class, 'create'])->name('transfers.create');
    Route::post('/transfers', [TransferOrderController::class, 'store'])->name('transfers.store');
    Route::get('/transfers/{transferOrder}/edit', [TransferOrderController::class, 'edit'])->name('transfers.edit');
    Route::put('/transfers/{transferOrder}', [TransferOrderController::class, 'update'])->name('transfers.update');
    Route::delete('/transfers/{transferOrder}', [TransferOrderController::class, 'destroy'])->name('transfers.destroy');
    Route::get('/transfers/{transferOrder}', [TransferOrderController::class, 'show'])->name('transfers.show');
    Route::get('/transfers/{transferOrder}/snapshot', [TransferOrderController::class, 'snapshot'])->name('transfers.snapshot');
    Route::post('/transfers/cancel', [TransferOrderController::class, 'cancel'])->name('transfers.cancel');

    // 资产入库（所有登录用户可查看列表和详情）
    Route::get('/intakes', [AssetIntakeController::class, 'index'])->name('intakes.index');
    Route::get('/intakes/{intake}/print', [AssetIntakeController::class, 'print'])->name('intakes.print');

    // 资产报废（所有登录用户可查看列表和详情）
    Route::get('/disposals', [AssetDisposalController::class, 'index'])->name('disposals.index');

    // 维修管理（所有登录用户可查看列表和详情）
    Route::get('/repairs', [RepairController::class, 'index'])->name('repairs.index');

    // 管理员专属
    Route::middleware('admin')->group(function () {
        // 资产 CRUD
        Route::get('/assets/create/form', [AssetController::class, 'create'])->name('assets.create');
        Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
        Route::get('/assets/{asset}/edit', [AssetController::class, 'edit'])->name('assets.edit');
        Route::put('/assets/{asset}', [AssetController::class, 'update'])->name('assets.update');
        Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
        Route::post('/assets/batch-delete', [AssetController::class, 'batchDelete'])->name('assets.batchDelete');

        // 资产导入
        Route::get('/assets/import/form', [AssetController::class, 'importForm'])->name('assets.importForm');
        Route::post('/assets/import', [AssetController::class, 'import'])->name('assets.import');
        Route::post('/assets/import/parse', [AssetController::class, 'parseCsv'])->name('assets.parseCsv');
        Route::post('/assets/import/batch', [AssetController::class, 'batchImport'])->name('assets.batchImport');

        // 资产入库管理（管理员）— 字面量路由在 {intake} 之前
        Route::get('/intakes/create', [AssetIntakeController::class, 'create'])->name('intakes.create');
        Route::post('/intakes', [AssetIntakeController::class, 'store'])->name('intakes.store');
        Route::post('/intakes/cancel', [AssetIntakeController::class, 'cancel'])->name('intakes.cancel');
        Route::get('/intakes/{intake}', [AssetIntakeController::class, 'show'])->name('intakes.show');
        Route::get('/intakes/{intake}/edit', [AssetIntakeController::class, 'edit'])->name('intakes.edit');
        Route::put('/intakes/{intake}', [AssetIntakeController::class, 'update'])->name('intakes.update');
        Route::delete('/intakes/{intake}', [AssetIntakeController::class, 'destroy'])->name('intakes.destroy');

        // 资产报废管理（管理员）— 字面量路由在 {disposal} 之前
        Route::get('/disposals/create', [AssetDisposalController::class, 'create'])->name('disposals.create');
        Route::post('/disposals', [AssetDisposalController::class, 'store'])->name('disposals.store');
        Route::post('/disposals/cancel', [AssetDisposalController::class, 'cancel'])->name('disposals.cancel');
        Route::get('/disposals/{disposal}', [AssetDisposalController::class, 'show'])->name('disposals.show');
        Route::get('/disposals/{disposal}/edit', [AssetDisposalController::class, 'edit'])->name('disposals.edit');
        Route::put('/disposals/{disposal}', [AssetDisposalController::class, 'update'])->name('disposals.update');
        Route::delete('/disposals/{disposal}', [AssetDisposalController::class, 'destroy'])->name('disposals.destroy');

        // 用户管理
        Route::resource('users', UserController::class);

        // 设备借用管理
        Route::get('/borrows', [AssetBorrowController::class, 'index'])->name('borrows.index');
        Route::get('/borrows/create', [AssetBorrowController::class, 'create'])->name('borrows.create');
        Route::get('/borrows/manage', [AssetBorrowController::class, 'manage'])->name('borrows.manage');
        Route::post('/borrows', [AssetBorrowController::class, 'store'])->name('borrows.store');
        Route::get('/borrows/{borrow}', [AssetBorrowController::class, 'show'])->name('borrows.show');
        Route::patch('/borrows/{borrow}/return', [AssetBorrowController::class, 'returnBook'])->name('borrows.return');
        Route::post('/borrows/batch-return', [AssetBorrowController::class, 'batchReturn'])->name('borrows.batchReturn');
        Route::delete('/borrows/{borrow}', [AssetBorrowController::class, 'destroy'])->name('borrows.destroy');

        // 维修管理
        Route::get('/repairs/create', [RepairController::class, 'create'])->name('repairs.create');
        Route::post('/repairs', [RepairController::class, 'store'])->name('repairs.store');
        Route::post('/repairs/cancel', [RepairController::class, 'cancel'])->name('repairs.cancel');
        Route::get('/repairs/{repair}', [RepairController::class, 'show'])->name('repairs.show');
        Route::get('/repairs/{repair}/edit', [RepairController::class, 'edit'])->name('repairs.edit');
        Route::put('/repairs/{repair}', [RepairController::class, 'update'])->name('repairs.update');
        Route::delete('/repairs/{repair}', [RepairController::class, 'destroy'])->name('repairs.destroy');
        Route::post('/repairs/{repair}/complete', [RepairController::class, 'complete'])->name('repairs.complete');

        // 系统更新
        Route::get('/updates', [UpdateController::class, 'index'])->name('updates.index');
        Route::post('/updates/upload', [UpdateController::class, 'upload'])->name('updates.upload');
        Route::post('/updates/rollback', [UpdateController::class, 'rollback'])->name('updates.rollback');

        // 打印模板管理
        Route::get('/print-templates', [\App\Http\Controllers\PrintTemplateController::class, 'index'])->name('print-templates.index');
        Route::get('/print-templates/{printTemplate}/edit', [\App\Http\Controllers\PrintTemplateController::class, 'edit'])->name('print-templates.edit');
        Route::put('/print-templates/{printTemplate}', [\App\Http\Controllers\PrintTemplateController::class, 'update'])->name('print-templates.update');
        Route::post('/print-templates/{printTemplate}/reset', [\App\Http\Controllers\PrintTemplateController::class, 'resetToDefault'])->name('print-templates.reset');

        // 统一打印
        Route::get('/print/{module}/{id}', [\App\Http\Controllers\PrintController::class, 'print'])->name('print.universal');
        // 系统管理
        Route::get('/system', [SystemController::class, 'index'])->name('system.index');
        Route::post('/system/init', [SystemController::class, 'init'])->name('system.init');
        Route::post('/system/backup', [SystemController::class, 'backup'])->name('system.backup');
        Route::post('/system/restore', [SystemController::class, 'restore'])->name('system.restore');
        Route::get('/system/backup/{filename}/download', [SystemController::class, 'downloadBackup'])->name('system.backup.download');
        Route::post('/system/backup/delete', [SystemController::class, 'deleteBackup'])->name('system.backup.delete');
        Route::get('/system/logs', [SystemController::class, 'logs'])->name('system.logs');
        Route::post('/system/logs/clear', [SystemController::class, 'clearLogs'])->name('system.logs.clear');

        // 数据更新管理
        Route::get('/data-updates', [DataUpdateController::class, 'index'])->name('data-updates.index');
        Route::post('/data-updates/suggest', [DataUpdateController::class, 'suggest'])->name('data-updates.suggest');
        Route::post('/data-updates/field', [DataUpdateController::class, 'updateField'])->name('data-updates.field');
        Route::post('/data-updates/submit/{id}', [DataUpdateController::class, 'submitRow'])->name('data-updates.submit');
        Route::post('/data-updates/submit-all', [DataUpdateController::class, 'submitAll'])->name('data-updates.submitAll');
        Route::delete('/data-updates/{id}', [DataUpdateController::class, 'destroy'])->name('data-updates.destroy');

        // 系统编码管理
        Route::get('/codes', [DepartmentCodeController::class, 'index'])->name('codes.index');
        Route::get('/codes/create', [DepartmentCodeController::class, 'create'])->name('codes.create');
        Route::post('/codes', [DepartmentCodeController::class, 'store'])->name('codes.store');
        Route::get('/codes/{code}/edit', [DepartmentCodeController::class, 'edit'])->name('codes.edit');
        Route::put('/codes/{code}', [DepartmentCodeController::class, 'update'])->name('codes.update');
        Route::delete('/codes/{code}', [DepartmentCodeController::class, 'destroy'])->name('codes.destroy');
        Route::get('/codes/import/form', [DepartmentCodeController::class, 'importForm'])->name('codes.importForm');
        Route::post('/codes/import', [DepartmentCodeController::class, 'import'])->name('codes.import');

        // ========== 耗材管理模块 ==========
        // 耗材主数据
        Route::get('/consumables', [\App\Http\Controllers\ConsumableController::class, 'index'])->name('consumables.index');
        Route::get('/consumables/create', [\App\Http\Controllers\ConsumableController::class, 'create'])->name('consumables.create');
        Route::post('/consumables', [\App\Http\Controllers\ConsumableController::class, 'store'])->name('consumables.store');
        Route::get('/consumables/search-json', [\App\Http\Controllers\ConsumableController::class, 'searchJson'])->name('consumables.searchJson');
        Route::get('/consumables/{consumable}', [\App\Http\Controllers\ConsumableController::class, 'show'])->name('consumables.show');
        Route::get('/consumables/{consumable}/edit', [\App\Http\Controllers\ConsumableController::class, 'edit'])->name('consumables.edit');
        Route::put('/consumables/{consumable}', [\App\Http\Controllers\ConsumableController::class, 'update'])->name('consumables.update');
        Route::delete('/consumables/{consumable}', [\App\Http\Controllers\ConsumableController::class, 'destroy'])->name('consumables.destroy');

        // 耗材入库
        Route::get('/consumable-intakes', [\App\Http\Controllers\ConsumableIntakeController::class, 'index'])->name('consumable-intakes.index');
        Route::get('/consumable-intakes/create', [\App\Http\Controllers\ConsumableIntakeController::class, 'create'])->name('consumable-intakes.create');
        Route::post('/consumable-intakes', [\App\Http\Controllers\ConsumableIntakeController::class, 'store'])->name('consumable-intakes.store');
        Route::get('/consumable-intakes/{consumable_intake}', [\App\Http\Controllers\ConsumableIntakeController::class, 'show'])->name('consumable-intakes.show');
        Route::get('/consumable-intakes/{consumable_intake}/edit', [\App\Http\Controllers\ConsumableIntakeController::class, 'edit'])->name('consumable-intakes.edit');
        Route::put('/consumable-intakes/{consumable_intake}', [\App\Http\Controllers\ConsumableIntakeController::class, 'update'])->name('consumable-intakes.update');
        Route::delete('/consumable-intakes/{consumable_intake}', [\App\Http\Controllers\ConsumableIntakeController::class, 'destroy'])->name('consumable-intakes.destroy');
        Route::post('/consumable-intakes/{consumable_intake}/complete', [\App\Http\Controllers\ConsumableIntakeController::class, 'complete'])->name('consumable-intakes.complete');
        Route::post('/consumable-intakes/{consumable_intake}/cancel', [\App\Http\Controllers\ConsumableIntakeController::class, 'cancel'])->name('consumable-intakes.cancel');

        // 耗材领用
        Route::get('/consumable-usages', [\App\Http\Controllers\ConsumableUsageController::class, 'index'])->name('consumable-usages.index');
        Route::get('/consumable-usages/create', [\App\Http\Controllers\ConsumableUsageController::class, 'create'])->name('consumable-usages.create');
        Route::post('/consumable-usages', [\App\Http\Controllers\ConsumableUsageController::class, 'store'])->name('consumable-usages.store');
        Route::get('/consumable-usages/{consumable_usage}', [\App\Http\Controllers\ConsumableUsageController::class, 'show'])->name('consumable-usages.show');

        // 耗材盘点
        Route::get('/consumable-inventories', [\App\Http\Controllers\ConsumableInventoryController::class, 'index'])->name('consumable-inventories.index');
        Route::get('/consumable-inventories/create', [\App\Http\Controllers\ConsumableInventoryController::class, 'create'])->name('consumable-inventories.create');
        Route::post('/consumable-inventories', [\App\Http\Controllers\ConsumableInventoryController::class, 'store'])->name('consumable-inventories.store');
        Route::get('/consumable-inventories/{consumable_inventory}', [\App\Http\Controllers\ConsumableInventoryController::class, 'show'])->name('consumable-inventories.show');
        Route::post('/consumable-inventories/{consumable_inventory}/settle', [\App\Http\Controllers\ConsumableInventoryController::class, 'settle'])->name('consumable-inventories.settle');

        // 耗材报表
        Route::get('/consumable-reports', [\App\Http\Controllers\ConsumableReportController::class, 'index'])->name('consumable-reports.index');
        Route::get('/consumable-reports/export', [\App\Http\Controllers\ConsumableReportController::class, 'export'])->name('consumable-reports.export');
        // 单据汇总
        Route::get('/process-void-orders', [\App\Http\Controllers\ProcessVoidOrderController::class, 'index'])->name('process-void-orders.index');
        Route::post('/process-void-orders/parse', [\App\Http\Controllers\ProcessVoidOrderController::class, 'parse'])->name('process-void-orders.parse');
        Route::get('/process-void-orders/create', [\App\Http\Controllers\ProcessVoidOrderController::class, 'create'])->name('process-void-orders.create');
        Route::post('/process-void-orders', [\App\Http\Controllers\ProcessVoidOrderController::class, 'store'])->name('process-void-orders.store');
        Route::get('/process-void-orders/{processVoidOrder}/edit', [\App\Http\Controllers\ProcessVoidOrderController::class, 'edit'])->name('process-void-orders.edit');
        Route::put('/process-void-orders/{processVoidOrder}', [\App\Http\Controllers\ProcessVoidOrderController::class, 'update'])->name('process-void-orders.update');
        Route::delete('/process-void-orders/{processVoidOrder}', [\App\Http\Controllers\ProcessVoidOrderController::class, 'destroy'])->name('process-void-orders.destroy');
        Route::post('/process-void-orders/{processVoidOrder}/void', [\App\Http\Controllers\ProcessVoidOrderController::class, 'void'])->name('process-void-orders.void');
        Route::post('/process-void-orders/{processVoidOrder}/paper-toggle', [\App\Http\Controllers\ProcessVoidOrderController::class, 'togglePaper'])->name('process-void-orders.togglePaper');
        Route::get('/process-void-orders/{processVoidOrder}/download', [\App\Http\Controllers\ProcessVoidOrderController::class, 'downloadDoc'])->name('process-void-orders.download');
        Route::get('/process-void-orders/{processVoidOrder}', [\App\Http\Controllers\ProcessVoidOrderController::class, 'show'])->name('process-void-orders.show');
        Route::get('/process-void-orders/export', [\App\Http\Controllers\ProcessVoidOrderController::class, 'exportCsv'])->name('process-void-orders.exportCsv');
        // 权限单汇总
        Route::get('/permission-orders', [\App\Http\Controllers\PermissionOrderController::class, 'index'])->name('permission-orders.index');
        Route::post('/permission-orders/parse', [\App\Http\Controllers\PermissionOrderController::class, 'parse'])->name('permission-orders.parse');
        Route::get('/permission-orders/create', [\App\Http\Controllers\PermissionOrderController::class, 'create'])->name('permission-orders.create');
        Route::post('/permission-orders', [\App\Http\Controllers\PermissionOrderController::class, 'store'])->name('permission-orders.store');
        Route::get('/permission-orders/{permissionOrder}/edit', [\App\Http\Controllers\PermissionOrderController::class, 'edit'])->name('permission-orders.edit');
        Route::put('/permission-orders/{permissionOrder}', [\App\Http\Controllers\PermissionOrderController::class, 'update'])->name('permission-orders.update');
        Route::delete('/permission-orders/{permissionOrder}', [\App\Http\Controllers\PermissionOrderController::class, 'destroy'])->name('permission-orders.destroy');
        Route::post('/permission-orders/{permissionOrder}/void', [\App\Http\Controllers\PermissionOrderController::class, 'void'])->name('permission-orders.void');
        Route::post('/permission-orders/{permissionOrder}/paper-toggle', [\App\Http\Controllers\PermissionOrderController::class, 'togglePaper'])->name('permission-orders.togglePaper');
        Route::get('/permission-orders/{permissionOrder}/download', [\App\Http\Controllers\PermissionOrderController::class, 'downloadDoc'])->name('permission-orders.download');
        Route::get('/permission-orders/{permissionOrder}', [\App\Http\Controllers\PermissionOrderController::class, 'show'])->name('permission-orders.show');
        Route::get('/permission-orders/export', [\App\Http\Controllers\PermissionOrderController::class, 'exportCsv'])->name('permission-orders.exportCsv');
    });

    // 资产详情 — 必须放在所有字面量路由之后
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
});

require __DIR__.'/auth.php';
