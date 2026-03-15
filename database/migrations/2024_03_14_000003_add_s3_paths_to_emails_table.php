<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddS3PathsToEmailsTable extends Migration
{
    public function up()
    {
        Schema::table('emails', function (Blueprint $table) {
            // Adding the migration tracking fields
            $table->text('body_s3_path')->nullable()->after('body');
            $table->json('file_s3_paths')->nullable()->after('file_ids');
        });
    }

    public function down()
    {
        Schema::table('emails', function (Blueprint $table) {
            $table->dropColumn(['body_s3_path', 'file_s3_paths']);
        });
    }
}
