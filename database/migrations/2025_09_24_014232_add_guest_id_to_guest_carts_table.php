<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('guest_carts', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_id')->nullable()->after('session_id');
            $table->foreign('guest_id')->references('id')->on('guests')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('guest_carts', function (Blueprint $table) {
            //
        });
    }
};
