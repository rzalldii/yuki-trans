<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Finance\FinanceTransfer;
use App\Models\Finance\FinanceWallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceTransferController extends Controller
{

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|exists:finance_wallets,id',
            'to_wallet_id' => 'required|exists:finance_wallets,id|different:from_wallet_id',
            'amount' => ['required', 'numeric', 'min:0'],
            'transfer_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);
        $fromWallet = FinanceWallet::findOrFail($validated['from_wallet_id']);
        $toWallet = FinanceWallet::findOrFail($validated['to_wallet_id']);
        $transfer = FinanceTransfer::create($validated);
        AuditLog::record('transfer_created', null, null, [
            'from_wallet' => $fromWallet->name,
            'to_wallet' => $toWallet->name,
            'amount' => $transfer->amount,
            'transfer_date' => $validated['transfer_date'],
        ]);

        return response()->json([], 201);
    }

    public function edit(FinanceTransfer $financeTransfer): JsonResponse
    {
        return response()->json([
            'id' => $financeTransfer->id,
            'from_wallet_id' => $financeTransfer->from_wallet_id,
            'to_wallet_id' => $financeTransfer->to_wallet_id,
            'amount' => (int) $financeTransfer->amount,
            'description' => $financeTransfer->description,
            'transfer_date' => $financeTransfer->getRawOriginal('transfer_date'),
        ]);
    }

    public function update(Request $request, FinanceTransfer $financeTransfer): JsonResponse
    {
        $validated = $request->validate([
            'from_wallet_id' => 'required|exists:finance_wallets,id',
            'to_wallet_id' => 'required|exists:finance_wallets,id|different:from_wallet_id',
            'amount' => ['required', 'numeric', 'min:0'],
            'transfer_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
        ]);
        $fromWallet = FinanceWallet::findOrFail($validated['from_wallet_id']);
        $toWallet = FinanceWallet::findOrFail($validated['to_wallet_id']);
        $oldFromWallet = $financeTransfer->fromWallet;
        $oldToWallet = $financeTransfer->toWallet;
        $oldValues = [
            'from_wallet' => $oldFromWallet->name ?? 'Unknown',
            'to_wallet' => $oldToWallet->name ?? 'Unknown',
            'amount' => $financeTransfer->amount,
            'transfer_date' => $financeTransfer->getRawOriginal('transfer_date'),
        ];
        $financeTransfer->fill($validated);
        if (!$financeTransfer->isDirty()) {
            return response()->json([], 204);
        }
        $financeTransfer->save();
        $newValues = [
            'from_wallet' => $fromWallet->name,
            'to_wallet' => $toWallet->name,
            'amount' => $financeTransfer->amount,
            'transfer_date' => $validated['transfer_date'],
        ];
        AuditLog::record('transfer_updated', null, $oldValues, $newValues);
        return response()->json([], 200);
    }

    public function destroy(FinanceTransfer $financeTransfer): JsonResponse
    {
        $fromWallet = $financeTransfer->fromWallet;
        $toWallet = $financeTransfer->toWallet;
        $deletedInfo = [
            'from_wallet' => $fromWallet->name ?? 'Unknown',
            'to_wallet' => $toWallet->name ?? 'Unknown',
            'amount' => $financeTransfer->amount,
            'transfer_date' => $financeTransfer->getRawOriginal('transfer_date'),
        ];
        AuditLog::record('transfer_deleted', null, $deletedInfo, null);
        $financeTransfer->delete();
        return response()->json([], 200);
    }
}
