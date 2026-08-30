<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

trait HasAttachment
{
    /**
     * Boot the trait.
     */
    protected static function bootHasAttachment(): void
    {
        static::deleting(function (self $model): void {
            $model->deleteAttachmentFile();
        });
    }

    /**
     * Get URL for the attached file.
     */
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Get formatted file size (e.g. 1.5 MB, 320 KB).
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $bytes = (int) $this->file_size;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    }

    /**
     * Get file extension in lowercase.
     */
    public function getFileExtensionAttribute(): ?string
    {
        if (! $this->file_name) {
            return null;
        }

        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Get file type category for UI styling and icons.
     */
    public function getFileTypeAttribute(): ?string
    {
        $ext = $this->file_extension;

        if (! $ext) {
            return null;
        }

        return match ($ext) {
            'pdf' => 'pdf',
            'doc', 'docx', 'odt', 'rtf' => 'word',
            'xls', 'xlsx', 'csv', 'ods' => 'excel',
            'ppt', 'pptx', 'odp' => 'powerpoint',
            'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp' => 'image',
            'zip', 'rar', '7z', 'tar', 'gz' => 'archive',
            'txt', 'md' => 'text',
            default => 'file',
        };
    }

    /**
     * Delete the physical file from storage disk.
     */
    public function deleteAttachmentFile(): void
    {
        if ($this->file_path && Storage::disk('public')->exists($this->file_path)) {
            Storage::disk('public')->delete($this->file_path);
        }
    }
}
