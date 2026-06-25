<?php

use App\Http\Controllers\AssetBorrowController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataUpdateController;
use App\Http\Controllers\DepartmentCodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TransferOrderController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/transfers/{transferOrder}', [TransferOrderController::class, 'show'])->name('transfers.show');
    Route::post('/transfers/cancel', [TransferOrderController::class, 'cancel'])->name('transfers.cancel');

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
    });

    // 资产详情 — 必须放在所有字面量路由之后
    Route::get('/assets/{asset}', [AssetController::class, 'show'])->name('assets.show');
});

require __DIR__.'/auth.php';
