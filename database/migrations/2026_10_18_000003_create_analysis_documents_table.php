<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_documents', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('analysis_case_id')->constrained()->cascadeOnDelete();
            $table->string('original_name');
            $table->string('stored_path');
            $table->string('sha256', 64);
            $table->string('mime', 128);
            $table->unsignedBigInteger('size');
            $table->string('status', 32)->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->json('redaction_notes')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->unique(['analysis_case_id', 'sha256']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_documents');
    }
};
