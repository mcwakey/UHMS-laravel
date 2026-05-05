<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name');
            $table->string('type')->default('public');
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('address')->nullable();
            $table->string('contract_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_providers');
    }
};
