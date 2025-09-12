<?php

use IbraheemGhazi\OmniTenancy\Database\Traits\UsingCentralConnection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

   use UsingCentralConnection;

    public function up(): void
    {
        Schema::create('tenants_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain');
            $table->json('routes_group')->nullable();
            $table->string('db_name')->nullable();
            $table->string('db_user')->nullable();
            $table->string('db_pass')->nullable();
            $table->json('options')->nullable();
            $table->json('owner_info')->nullable();
            $table->timestamps();

            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants_requests');
    }
};
