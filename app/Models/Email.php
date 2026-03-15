<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'loan_id',
        'email_template_id',
        'receiver_email',
        'sender_email',
        'subject',
        'body',
        'file_ids',
        'body_s3_path',
        'file_s3_paths',
        'sent_at',
    ];

    protected $casts = [
        'file_ids' => 'array',
        'file_s3_paths' => 'array',
        'sent_at' => 'datetime',
    ];
}
