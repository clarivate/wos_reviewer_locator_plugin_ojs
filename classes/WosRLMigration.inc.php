<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class WosRLMigration extends Migration {

	/**
	 * Run migration to create wosrl_submission_tokes table.
	 * @return void
	 */
	public function up() {
		Capsule::schema()->create('wosrl_submission_tokens', function (Blueprint $table) {
			$table->bigInteger('token_id')->autoIncrement();
			$table->bigInteger('submission_id');
			$table->string('locale', 5)->default('');
			$table->string('token', 255);
			$table->date('created_at');
			$table->index(['submission_id'], 'submission_tokens_submission_id');
			$table->unique(['submission_id', 'locale', 'token'], 'wosrl_submission_tokens_pkey');
		});
	}
}
