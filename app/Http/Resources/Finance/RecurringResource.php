<?php

declare(strict_types=1);

namespace App\Http\Resources\Finance;

use App\Enums\Frequency;
use App\Enums\RecurringType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecurringResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeVal = $this->type instanceof RecurringType ? $this->type->value : (string) $this->type;
        $freqVal = $this->frequency instanceof Frequency ? $this->frequency->value : (string) $this->frequency;
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'wallet' => new WalletResource($this->whenLoaded('wallet')),
            'to_wallet_id' => $this->to_wallet_id,
            'to_wallet' => new WalletResource($this->whenLoaded('toWallet')),
            'category_id' => $this->category_id,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'type' => $typeVal,
            'amount' => (float) $this->amount,
            'description' => $this->description,
            'frequency' => $freqVal,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'next_due_date' => $this->next_due_date ? $this->next_due_date->format('Y-m-d') : null,
            'last_generated_at' => $this->last_generated_at ? $this->last_generated_at->format('Y-m-d') : null,
            'is_active' => (bool) $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}