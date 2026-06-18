<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cisco_extension_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('extension_from', 16);
            $table->string('extension_to', 16)->nullable();
            $table->string('description');
            $table->timestamps();

            $table->index('extension_from');
            $table->index('extension_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cisco_extension_reservations');
    }
};
