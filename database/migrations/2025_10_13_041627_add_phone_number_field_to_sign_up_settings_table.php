<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn('global_settings', 'sign_up_phone_field')) {
            Schema::table('global_settings', function (Blueprint $table) {
                $table->enum('sign_up_phone_field', ['yes', 'no'])->default('no')->after('terms_link');
                $table->enum('sign_up_phone_required', ['yes', 'no'])->default('no')->after('sign_up_phone_field');
            });
        }
    }

    public function down(): void
    {
        Schema::table('global_settings', function (Blueprint $table) {
            $table->dropColumn(['sign_up_phone_field', 'sign_up_phone_required']);
        });
    }
};