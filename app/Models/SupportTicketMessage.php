<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SupportTicketMessage extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'admin_id',
        'sender_type',
        'message',
        'attachment_path',
    ];

    public function ticket()
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function resolveAttachmentAbsolutePath(): ?string
    {
        if (!$this->attachment_path) {
            return null;
        }

        $rawPath = parse_url($this->attachment_path, PHP_URL_PATH) ?: $this->attachment_path;
        $normalizedPath = ltrim(str_replace('\\', '/', $rawPath), '/');

        $candidates = [
            $normalizedPath,
            Str::startsWith($normalizedPath, 'storage/') ? Str::after($normalizedPath, 'storage/') : null,
            Str::startsWith($normalizedPath, 'public/') ? Str::after($normalizedPath, 'public/') : null,
        ];

        if (Str::contains($normalizedPath, 'support-tickets/')) {
            $candidates[] = Str::after($normalizedPath, 'support-tickets/');
            $candidates[] = 'support-tickets/' . basename($normalizedPath);
        }

        $candidates = collect($candidates)
            ->filter()
            ->unique()
            ->values();

        foreach ($candidates as $candidate) {
            if (Storage::disk('public')->exists($candidate)) {
                return Storage::disk('public')->path($candidate);
            }

            $publicStoragePath = public_path($candidate);
            if (is_file($publicStoragePath)) {
                return $publicStoragePath;
            }

            $storagePublicPath = storage_path('app/public/' . $candidate);
            if (is_file($storagePublicPath)) {
                return $storagePublicPath;
            }
        }

        return null;
    }
}
