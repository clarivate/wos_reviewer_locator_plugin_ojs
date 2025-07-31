<?php

namespace APP\plugins\generic\wosReviewerLocator\classes;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class wosRLMigration extends Migration {

    /**
     * Run migration to create the wosrl_submissions_settings table
     *
     * @return void
     */
    public function up() {
        Schema::create('wosrl_submission_tokens', function (Blueprint $table) {
            $table->bigInteger('token_id')->autoIncrement();
            $table->bigInteger('submission_id');
            $table->string('locale', 5)->default('');
            $table->string('token', 255);
            $table->date('created_at');
            $table->index(['submission_id'], 'submission_tokens_submission_id');
            $table->unique(['submission_id', 'locale', 'token'], 'wosrl_submission_tokens_pkey');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void {
        Schema::drop('wosrl_submission_tokens');
    }

}
