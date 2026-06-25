<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetBorrow;
use Illuminate\Http\Request;

class AssetBorrowController extends Controller
{
    public function index()
    {
        $borrows = AssetBorrow::with('asset')->orderBy('created_at', 'desc')->paginate(20);
        return view('borrows.index', compact('borrows'));
    }

    public function create(Request $request)
    {
        $asset = null;
        if ($request->filled('asset_id')) {
            $asset = Asset::find($request->asset_id);
        }
        return view('borrows.create', compact('asset'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'borrower' => 'required|string|max:100',
            'department' => 'nullable|string|max:100',
            'borrow_date' => 'required|date',
            'expected_return_date' => 'nullable|date',
            'remarks' => 'nullable|string',
        ]);

        $today = now()->format('Ymd');
        $baseCount = AssetBorrow::whereDate('created_at', now())->count();
        $borrows = [];

        foreach ($request->asset_ids as $i => $assetId) {
            $asset = Asset::findOrFail($assetId);

            $orderNo = 'JY-' . $today . '-' . str_pad($baseCount + $i + 1, 3, '0', STR_PAD_LEFT);

            $borrow = AssetBorrow::create([
                'asset_id' => $asset->id,
                'order_no' => $orderNo,
                'borrower' => $request->borrower,
                'department' => $request->department,
                'borrow_date' => $request->borrow_date,
                'expected_return_date' => $request->expected_return_date,
                'previous_status' => $asset->status,
                'remarks' => $request->remarks,
            ]);

            $asset->update(['status' => '借用']);
            $borrows[] = $borrow;
        }

        $msg = count($borrows) > 1 ? "已成功借用 " . count($borrows) . " 台设备" : "设备借用成功";
        return redirect()->route('borrows.index')->with('success', $msg);
    }

    public function show(AssetBorrow $borrow)
    {
        $borrow->load('asset');
        return view('borrows.show', compact('borrow'));
    }

    // 借用中资产管理
    public function manage()
    {
        $borrowedAssets = Asset::where('status', '借用')
            ->with(['logs' => function ($q) {
                $q->where('field', 'status')->orderBy('created_at', 'desc');
            }])
            ->get();

        $activeBorrows = AssetBorrow::whereNull('return_date')
            ->with('asset')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('borrows.manage', compact('borrowedAssets', 'activeBorrows'));
    }

    // 批量归还
    public function batchReturn(Request $request)
    {
        $request->validate([
            'borrow_ids' => 'required|array|min:1',
            'borrow_ids.*' => 'exists:asset_borrows,id',
        ]);

        $count = 0;
        foreach ($request->borrow_ids as $id) {
            $borrow = AssetBorrow::find($id);
            if ($borrow && !$borrow->return_date) {
                $borrow->update(['return_date' => now()]);
                $borrow->asset->update(['status' => $borrow->previous_status]);
                $count++;
            }
        }

        return redirect()->route('borrows.manage')->with('success', "已归还 {$count} 台设备，状态已恢复");
    }

    // 归还设备
    public function returnBook(AssetBorrow $borrow)
    {
        if ($borrow->return_date) {
            return back()->with('error', '该设备已经归还');
        }

        $borrow->update(['return_date' => now()]);
        $borrow->asset->update(['status' => $borrow->previous_status]);

        return back()->with('success', '设备已归还，状态已恢复');
    }

    public function destroy(AssetBorrow $borrow)
    {
        $borrow->delete();
        return redirect()->route('borrows.index')->with('success', '借用记录已删除');
    }
}
