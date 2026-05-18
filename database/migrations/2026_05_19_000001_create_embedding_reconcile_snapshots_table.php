<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embedding_reconcile_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('namespace', 128);
            $table->longText('payload');
            $table->timestamp('reconciled_at');
            $table->timestamps();

            $table->unique(['tenant_id', 'namespace']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embedding_reconcile_snapshots');
    }
};
