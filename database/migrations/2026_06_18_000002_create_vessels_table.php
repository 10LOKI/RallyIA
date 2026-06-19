<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mmsi')->unique();
            $table->string('name')->nullable();
            $table->string('ship_type')->nullable();
            $table->decimal('lat', 10, 6)->nullable();
            $table->decimal('lng', 10, 6)->nullable();
            $table->decimal('sog', 6, 1)->nullable();   // speed over ground (noeuds)
            $table->decimal('cog', 6, 1)->nullable();   // course over ground
            $table->string('nav_status')->nullable();
            $table->string('destination')->nullable();  // AIS Destination (port saisi)
            $table->string('eta')->nullable();          // AIS ETA brut
            $table->timestamp('seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessels');
    }
};
