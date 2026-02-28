<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ScoreResource extends JsonResource
{
    /**
     * @return array{osszpontszam: int, alappont: int, tobbletpont: int}
     */
    public function toArray(Request $request): array
    {
        return [
            'osszpontszam' => $this->resource->total(),
            'alappont' => $this->resource->basePoints(),
            'tobbletpont' => $this->resource->bonusPoints(),
        ];
    }
}
