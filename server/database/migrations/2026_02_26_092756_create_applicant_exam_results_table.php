<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_exam_results', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('subject_name');
            $table->string('level');
            $table->unsignedTinyInteger('percentage');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_exam_results');
    }
};
