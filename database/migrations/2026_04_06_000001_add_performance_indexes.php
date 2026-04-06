<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('source');
            $table->index('amount');
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->index('transaction_id');
            $table->index('date');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropIndex(['amount']);
        });

        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['parent_id']);
        });
    }
};
