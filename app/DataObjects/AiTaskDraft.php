<?php

namespace App\DataObjects;

class AiTaskDraft
{
    /**
     * @param  array<int, array{title: string, completed: bool}>|array<int, string>  $subtasks
     */
    public function __construct(
        public string $type,
        public string $title,
        public ?string $subject = null,
        public ?string $deadline = null,
        public int $estimated_duration = 60,
        public string $priority = 'medium',
        public array $subtasks = [],
        public ?string $description = null,
        public ?string $location = null,
        public ?string $category = null,
        public float $confidence = 0.9,
        public string $original_text = '',
    ) {}

    /**
     * Convert the draft to an array suitable for JSON responses or form hydration.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'title' => $this->title,
            'subject' => $this->subject,
            'deadline' => $this->deadline,
            'estimated_duration' => $this->estimated_duration,
            'priority' => $this->priority,
            'subtasks' => $this->normalizeSubtasks(),
            'description' => $this->description,
            'location' => $this->location,
            'category' => $this->category,
            'confidence' => $this->confidence,
            'original_text' => $this->original_text,
        ];
    }

    /**
     * Ensure subtasks are normalized to array of {title, completed}.
     *
     * @return array<int, array{title: string, completed: bool}>
     */
    public function normalizeSubtasks(): array
    {
        return array_values(array_map(function ($item) {
            if (is_array($item)) {
                return [
                    'title' => (string) ($item['title'] ?? ''),
                    'completed' => (bool) ($item['completed'] ?? false),
                ];
            }

            return [
                'title' => (string) $item,
                'completed' => false,
            ];
        }, $this->subtasks));
    }
}
