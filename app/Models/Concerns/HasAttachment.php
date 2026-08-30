<?php

namespace App\Models\Concerns;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\UploadedFile;

trait HasAttachment
{
    /**
     * Boot the trait.
     */
    protected static function bootHasAttachment(): void
    {
        static::deleting(function (self $model): void {
            $model->attachments->each->delete();
        });
    }

    /**
     * Get all attachments for the model.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Save multiple uploaded files as attachments.
     *
     * @param  array<UploadedFile>|UploadedFile|null  $files
     */
    public function saveAttachments(array|UploadedFile|null $files, string $folder = 'uploads'): void
    {
        if (! $files) {
            return;
        }

        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        foreach ($files as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $path = $file->store($folder, 'public');
                $this->attachments()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }
    }

    /**
     * Remove specific attachments by their IDs.
     *
     * @param  array<int|string>|null  $ids
     */
    public function deleteAttachmentsByIds(?array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $this->attachments()->whereIn('id', $ids)->get()->each->delete();
    }
}
