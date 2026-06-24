<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cisco_physical_device_metadata', function (Blueprint $table) {
            $table->id();
            $table->string('device_name')->unique();
            $table->string('standort')->nullable();
            $table->string('raum')->nullable();
            $table->string('etage')->nullable();
            $table->string('haus')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cisco_physical_device_metadata');
    }
};
