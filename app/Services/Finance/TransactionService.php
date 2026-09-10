<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Audit\AuditLog;
use App\Models\Finance\Category;
use App\Models\Finance\Tag;
use App\Models\Finance\Transaction;
use App\Models\Finance\Wallet;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function createTransaction(array $validated, ?array $tags, int $userId): Transaction
    {
        return DB::transaction(function () use ($validated, $tags, $userId) {
            $category = Category::findOrFail($validated['category_id']);
            $wallet = Wallet::where('id', $validated['wallet_id'])->lockForUpdate()->firstOrFail();
            $validated['user_id'] = $userId;
            $typeVal = $category->type instanceof CategoryType ? $category->type->value : $category->type;
            $validated['type'] = $typeVal;
            $txData = collect($validated)->except('tags')->all();
            $transaction = Transaction::create($txData);
            $wallet->adjustBalance($validated['type'], (float) $validated['amount']);
            if (!empty($tags)) {
                $tagIds = collect($tags)->map(fn($tagName) => Tag::findOrCreateByName($tagName)->id);
                $transaction->tags()->sync($tagIds);
            }
            AuditLog::record('transaction_created', null, null, [
                'wallet' => $wallet->name,
                'category' => $category->name,
                'type' => $validated['type'],
                'amount' => $transaction->amount,
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
            ]);
            return $transaction;
        });
    }

    public function createTransfer(array $validated, ?array $tags, int $userId): array
    {
        return DB::transaction(function () use ($validated, $tags, $userId) {
            $fromWallet = Wallet::where('id', $validated['from_wallet_id'])->lockForUpdate()->firstOrFail();
            $toWallet = Wallet::where('id', $validated['to_wallet_id'])->lockForUpdate()->firstOrFail();
            if ((float) $fromWallet->current_balance < (float) $validated['amount']) {
                throw new InvalidArgumentException('Insufficient wallet balance');
            }
            $out = Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $fromWallet->id,
                'type' => TransactionType::TransferOut->value,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
            ]);
            $in = Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $toWallet->id,
                'type' => TransactionType::TransferIn->value,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
            ]);
            $out->update(['transfer_pair_id' => $in->id]);
            $in->update(['transfer_pair_id' => $out->id]);
            if (!empty($tags)) {
                $tagIds = collect($tags)->map(fn($tagName) => Tag::findOrCreateByName($tagName)->id);
                $out->tags()->sync($tagIds);
                $in->tags()->sync($tagIds);
            }
            $fromWallet->adjustBalance(TransactionType::TransferOut, (float) $validated['amount']);
            $toWallet->adjustBalance(TransactionType::TransferIn, (float) $validated['amount']);
            AuditLog::record('transfer_created', null, null, [
                'from_wallet' => $fromWallet->name,
                'to_wallet' => $toWallet->name,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transfer_date' => $validated['transaction_date'],
                'tags' => implode(', ', $tags ?? []),
            ]);
            return ['out' => $out, 'in' => $in];
        });
    }

    public function updateTransaction(Transaction $transaction, array $validated, ?array $tags): void
    {
        DB::transaction(function () use ($transaction, $validated, $tags) {
            $category = Category::findOrFail($validated['category_id']);
            $typeVal = $category->type instanceof CategoryType ? $category->type->value : $category->type;
            $validated['type'] = $typeVal;
            $oldType = $transaction->getOriginal('type') ?? $transaction->type;
            $oldAmount = (float) ($transaction->getOriginal('amount') ?? $transaction->amount);
            $oldWalletId = (int) ($transaction->getOriginal('wallet_id') ?? $transaction->wallet_id);
            $oldValues = [
                'wallet' => $transaction->wallet->name ?? 'Unknown',
                'category' => $transaction->category->name ?? 'Unknown',
                'amount' => $oldAmount,
                'description' => $transaction->getOriginal('description') ?? $transaction->description,
                'transaction_date' => $transaction->getRawOriginal('transaction_date'),
            ];
            $walletIds = array_unique(array_filter([$oldWalletId, (int) $validated['wallet_id']]));
            $lockedWallets = Wallet::whereIn('id', $walletIds)->lockForUpdate()->get()->keyBy('id');
            $oldWallet = $lockedWallets->get($oldWalletId);
            $newWallet = $lockedWallets->get($validated['wallet_id']);
            if ($oldWallet) {
                $oldWallet->revertBalance($oldType, $oldAmount);
            }
            $txData = collect($validated)->except('tags')->all();
            $transaction->fill($txData)->save();
            if ($newWallet) {
                $newWallet->adjustBalance($validated['type'], (float) $validated['amount']);
            }
            if ($tags !== null) {
                $tagIds = collect($tags)->map(fn($tagName) => Tag::findOrCreateByName($tagName)->id);
                $transaction->tags()->sync($tagIds);
            }
            AuditLog::record('transaction_updated', null, $oldValues, [
                'wallet' => $newWallet->name ?? 'Unknown',
                'category' => $category->name,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'transaction_date' => $validated['transaction_date'],
            ]);
        });
    }

    public function updateTransfer(Transaction $transaction, array $validated, ?array $tags): void
    {
        DB::transaction(function () use ($transaction, $validated, $tags) {
            $isTransferOut = $transaction->type === TransactionType::TransferOut || $transaction->type === 'transfer_out';
            $isTransferIn = $transaction->type === TransactionType::TransferIn || $transaction->type === 'transfer_in';
            $outTx = $isTransferOut ? $transaction : $transaction->transferPair;
            $inTx = $isTransferIn ? $transaction : $transaction->transferPair;
            $oldAmount = (float) ($outTx->getOriginal('amount') ?? $outTx->amount);
            $oldFromWalletId = (int) ($outTx->getOriginal('wallet_id') ?? $outTx->wallet_id);
            $oldToWalletId = (int) ($inTx->getOriginal('wallet_id') ?? $inTx->wallet_id);
            $walletIds = array_unique(array_filter([
                $oldFromWalletId,
                $oldToWalletId,
                (int) $validated['from_wallet_id'],
                (int) $validated['to_wallet_id'],
            ]));
            $lockedWallets = Wallet::whereIn('id', $walletIds)->lockForUpdate()->get()->keyBy('id');
            $oldFromWallet = $lockedWallets->get($oldFromWalletId) ?? $outTx->wallet;
            $oldToWallet = $lockedWallets->get($oldToWalletId) ?? $inTx->wallet;
            $oldDescription = $outTx->getOriginal('description') ?? $outTx->description;
            $newFromWallet = $lockedWallets->get($validated['from_wallet_id']);
            $availableBalance = (int) $newFromWallet->id === (int) ($oldFromWallet->id ?? 0)
                ? (float) $newFromWallet->current_balance + $oldAmount
                : (float) $newFromWallet->current_balance;
            if ($availableBalance < (float) $validated['amount']) {
                throw new InvalidArgumentException('Insufficient wallet balance');
            }
            if ($oldFromWallet && $lockedWallets->has($oldFromWallet->id)) {
                $lockedWallets->get($oldFromWallet->id)->revertBalance(TransactionType::TransferOut, $oldAmount);
            }
            if ($oldToWallet && $lockedWallets->has($oldToWallet->id)) {
                $lockedWallets->get($oldToWallet->id)->revertBalance(TransactionType::TransferIn, $oldAmount);
            }
            $outTx->fill([
                'wallet_id' => $validated['from_wallet_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
            ])->save();
            $inTx->fill([
                'wallet_id' => $validated['to_wallet_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'transaction_date' => $validated['transaction_date'],
            ])->save();
            if ($tags !== null) {
                $tagIds = collect($tags)->map(fn($tagName) => Tag::findOrCreateByName($tagName)->id);
                $outTx->tags()->sync($tagIds);
                $inTx->tags()->sync($tagIds);
            }
            $fromWallet = $lockedWallets->get($validated['from_wallet_id']);
            $toWallet = $lockedWallets->get($validated['to_wallet_id']);
            $fromWallet->adjustBalance(TransactionType::TransferOut, (float) $validated['amount']);
            $toWallet->adjustBalance(TransactionType::TransferIn, (float) $validated['amount']);
            AuditLog::record('transfer_updated', null, [
                'from_wallet' => $oldFromWallet->name ?? 'Unknown',
                'to_wallet' => $oldToWallet->name ?? 'Unknown',
                'amount' => $oldAmount,
                'description' => $oldDescription,
                'tags' => $outTx->tags->pluck('name')->implode(', '),
            ], [
                'from_wallet' => $fromWallet->name,
                'to_wallet' => $toWallet->name,
                'amount' => $validated['amount'],
                'description' => $validated['description'] ?? null,
                'tags' => implode(', ', $tags ?? []),
            ]);
        });
    }

    public function deleteTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            if ($transaction->isTransfer() && $transaction->transferPair) {
                $pair = $transaction->transferPair;
                $isTransferOut = $transaction->type === TransactionType::TransferOut || $transaction->type === 'transfer_out';
                $outTx = $isTransferOut ? $transaction : $pair;
                $isTransferIn = $transaction->type === TransactionType::TransferIn || $transaction->type === 'transfer_in';
                $inTx = $isTransferIn ? $transaction : $pair;
                $lockedWallets = Wallet::whereIn('id', [$outTx->wallet_id, $inTx->wallet_id])->lockForUpdate()->get()->keyBy('id');
                $outWallet = $lockedWallets->get($outTx->wallet_id);
                $inWallet = $lockedWallets->get($inTx->wallet_id);
                if ($outWallet) {
                    $outWallet->revertBalance(TransactionType::TransferOut, (float) $outTx->amount);
                }
                if ($inWallet) {
                    $inWallet->revertBalance(TransactionType::TransferIn, (float) $inTx->amount);
                }
                AuditLog::record('transfer_deleted', null, [
                    'from_wallet' => $outWallet->name ?? 'Unknown',
                    'to_wallet' => $inWallet->name ?? 'Unknown',
                    'amount' => $outTx->amount,
                ], null);
                $pair->update(['transfer_pair_id' => null]);
                $transaction->update(['transfer_pair_id' => null]);
                $pair->delete();
                $transaction->delete();
            } else {
                $wallet = Wallet::where('id', $transaction->wallet_id)->lockForUpdate()->first();
                if ($wallet) {
                    $wallet->revertBalance($transaction->type, (float) $transaction->amount);
                }
                AuditLog::record('transaction_deleted', null, [
                    'wallet' => $wallet->name ?? 'Unknown',
                    'category' => $transaction->category->name ?? 'Unknown',
                    'type' => $transaction->type instanceof TransactionType ? $transaction->type->value : $transaction->type,
                    'amount' => $transaction->amount,
                ], null);
                $transaction->delete();
            }
        });
    }
}