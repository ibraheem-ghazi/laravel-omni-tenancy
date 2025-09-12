<?php

use IbraheemGhazi\OmniTenancy\Database\Traits\UsingCentralConnection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    use UsingCentralConnection;

    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('domain', 255)->unique();
            $table->boolean('is_main')->default(false);
            $table->unsignedBigInteger('tenant_id');

            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->index(['tenant_id', 'is_main']);
            $table->unique(['tenant_id', 'is_main'], 'unique_main_domain_per_tenant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
