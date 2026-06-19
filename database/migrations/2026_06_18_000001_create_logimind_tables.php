<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ports', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('ville');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('capacite_max')->default(1000); // conteneurs/jour
            $table->timestamps();
        });

        Schema::create('port_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedTinyInteger('meteo_score');     // 0=tempete .. 100=calme
            $table->unsignedTinyInteger('saturation_pct');  // 0..100 remplissage port
            $table->smallInteger('news_sentiment');         // -100..100 contexte eco
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('port_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('marchandise');
            $table->string('origine');
            $table->string('destination_ville');
            $table->decimal('dest_lat', 10, 7);
            $table->decimal('dest_lng', 10, 7);
            $table->string('statut')->default('en_mer'); // en_mer | au_port | en_route | livre
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('port_conditions');
        Schema::dropIfExists('ports');
    }
};
