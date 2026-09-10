<?php

declare(strict_types=1);

namespace App\Http\Resources\Finance;

use App\Enums\CategoryType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $typeVal = $this->type instanceof CategoryType ? $this->type->value : (string) $this->type;
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $typeVal,
            'amount' => (float) $this->amount,
            'amount_label' => $this->amount_label,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}