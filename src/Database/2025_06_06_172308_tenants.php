<?php

use IbraheemGhazi\OmniTenancy\Database\Traits\UsingCentralConnection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

   use UsingCentralConnection;

    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->boolean('active')->default(true);
            $table->json('options')->nullable();
            $table->json('owner_info')->nullable();
            $table->timestamps();

            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
