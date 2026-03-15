<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailsTable extends Migration
{
    public function up()
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('loan_id');
            $table->unsignedBigInteger('email_template_id');
            $table->string('receiver_email');
            $table->string('sender_email');
            $table->string('subject');
            $table->longText('body');
            $table->json('file_ids')->nullable(); // Original local file IDs
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            // Indices for performance
            $table->index('client_id');
            $table->index('loan_id');
            $table->index('email_template_id');
            $table->index('receiver_email');
        });
    }

    public function down()
    {
        Schema::dropIfExists('emails');
    }
}
