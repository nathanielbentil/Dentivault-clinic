<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BackupLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'backup_type',
        'storage_location',
        'file_size_bytes',
        'sha256_checksum',
        'is_encrypted',
        'age_recipient_preview',
        'status',
        'error_message',
        'restoration_tested_at',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'restoration_tested_at' => 'datetime',
    ];

    public function humanFileSize(): string
    {
        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
