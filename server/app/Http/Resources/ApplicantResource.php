<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApplicantResource extends JsonResource
{
    /**
     * @return array{id: string, program: array{university: string, faculty: string, name: string}}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'program' => [
                'university' => $this->program->university,
                'faculty' => $this->program->faculty,
                'name' => $this->program->name,
            ],
        ];
    }
}
