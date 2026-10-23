<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('algeria-invoice.numbering.table', 'invoice_sequences'), function (Blueprint $table) {
            $table->id();
            $table->string('series', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamp('updated_at')->nullable();

            $table->unique(['series', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('algeria-invoice.numbering.table', 'invoice_sequences'));
    }
};
