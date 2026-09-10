<?php

namespace App\Services;

use App\DataObjects\AiBreakdownResult;
use App\DataObjects\AiTaskDraft;
use App\Models\CourseSchedule;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    /**
     * Parse natural language input (Indonesian/English) into a structured task or event draft.
     */
    public function parseNaturalLanguageInput(string $text, ?Carbon $referenceDate = null): AiTaskDraft
    {
        $ref = $referenceDate ? $referenceDate->copy() : now();
        $cleaned = trim($text);

        // If Gemini API Key is configured, attempt LLM call first with fallback
        if (config('services.gemini.key') || env('GEMINI_API_KEY')) {
            $llmResult = $this->callGeminiForParse($cleaned, $ref);
            if ($llmResult !== null) {
                return $llmResult;
            }
        }

        return $this->heuristicParse($cleaned, $ref);
    }

    /**
     * Break down a complex task into actionable phases & subtasks.
     */
    public function breakdownTask(string $title, ?string $description = null): AiBreakdownResult
    {
        $prompt = trim($title.' '.($description ?? ''));

        if (config('services.gemini.key') || env('GEMINI_API_KEY')) {
            $llmBreakdown = $this->callGeminiForBreakdown($title, $description);
            if ($llmBreakdown !== null) {
                return $llmBreakdown;
            }
        }

        return $this->heuristicBreakdown($prompt);
    }

    /**
     * Parse multiple tasks from multi-line OCR text or chat history.
     *
     * @return Collection<int, AiTaskDraft>
     */
    public function parseMultipleTasksFromText(string $ocrText, ?Carbon $referenceDate = null): Collection
    {
        $ref = $referenceDate ? $referenceDate->copy() : now();
        $lines = preg_split('/\r\n|\r|\n/', $ocrText);
        $results = collect();

        $currentBlock = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Check if line looks like the start of a new task item (e.g. "1.", "-", "*", "[ ]", "Tugas:")
            if (preg_match('/^(\d+[\.\)]|[-*•]|\[[\sx]?\]|tugas\s*\d*:?)/i', $line)) {
                if (! empty($currentBlock)) {
                    $draft = $this->parseNaturalLanguageInput($currentBlock, $ref);
                    if ($this->isValidDraft($draft)) {
                        $results->push($draft);
                    }
                }
                $currentBlock = $line;
            } else {
                if (empty($currentBlock)) {
                    $currentBlock = $line;
                } else {
                    $currentBlock .= ' '.$line;
                }
            }
        }

        if (! empty($currentBlock)) {
            $draft = $this->parseNaturalLanguageInput($currentBlock, $ref);
            if ($this->isValidDraft($draft)) {
                $results->push($draft);
            }
        }

        // If no structured bullet found, parse whole text if plausible
        if ($results->isEmpty() && strlen(trim($ocrText)) > 5) {
            $draft = $this->parseNaturalLanguageInput($ocrText, $ref);
            if ($this->isValidDraft($draft)) {
                $results->push($draft);
            }
        }

        return $results;
    }

    /**
     * Generate an engaging Indonesian daily insight briefing from Today Intelligence data.
     *
     * @param  array<string, mixed>  $hubData
     */
    public function generateDailyInsight(array $hubData): string
    {
        $overload = $hubData['overload'] ?? null;
        $nextUp = $hubData['nextUp'] ?? null;
        $freeSlots = $hubData['freeSlots'] ?? collect();
        $recommendation = $hubData['recommendation'] ?? null;
        $highRisk = $hubData['highRiskTasks'] ?? collect();
        $freeHours = (float) ($hubData['totalFreeHoursToday'] ?? 0);

        $greeting = $this->getTimeGreeting();
        $sentences = [];

        // 1. Status Overload & Free time
        $overloadLevel = $overload ? $overload->level : 'SEIMBANG';
        if ($overloadLevel === 'KRITIS' || $overloadLevel === 'TINGGI') {
            $sentences[] = "{$greeting}! Hari ini jadwalmu cukup padat ({$overloadLevel}) dengan sisa waktu luang sekitar {$freeHours} jam.";
        } elseif ($freeHours > 4) {
            $sentences[] = "{$greeting}! Kabar baik, hari ini kamu punya banyak ruang bernapas dengan total {$freeHours} jam waktu luang.";
        } else {
            $sentences[] = "{$greeting}! Jadwalmu hari ini terbilang {$overloadLevel} dengan total {$freeHours} jam celah waktu luang.";
        }

        // 2. Next Up Event / Class
        if ($nextUp) {
            $loc = ! empty($nextUp['location']) ? " di {$nextUp['location']}" : '';
            $sentences[] = "Agenda terdekat adalah **{$nextUp['title']}** pada pukul {$nextUp['time']}{$loc} ({$nextUp['countdown']}).";
        }

        // 3. Recommended Task & High Risk
        if ($recommendation && $recommendation->task) {
            $taskTitle = $recommendation->task->title;
            $slotRange = $recommendation->slot->formattedRange;
            $sentences[] = "Fokus tugas terbaik untuk dikerjakan adalah **{$taskTitle}**, paling pas dieksekusi pada slot jam **{$slotRange}** ({$recommendation->recommendedDurationMinutes} mnt).";
        } elseif ($highRisk->isNotEmpty()) {
            $first = $highRisk->first();
            $sentences[] = "Perhatian: ada tugas berisiko tinggi **{$first->title}** yang mendekati deadline. Segera cicil sebelum terlambat!";
        } else {
            $sentences[] = 'Tidak ada tugas berisiko kritis yang mendesak hari ini. Tetap konsisten dan jaga ritme belajar!';
        }

        return implode(' ', $sentences);
    }

    /**
     * Deterministic Indonesian heuristic parser for natural language task & event inputs.
     */
    public function heuristicParse(string $text, Carbon $ref): AiTaskDraft
    {
        $lower = strtolower($text);

        // 1. Type detection: Event vs Task
        $isEvent = (bool) preg_match('/\b(rapat|meet|meeting|seminar|webinar|kuliah umum|sidang|kumpul|latihan|acara|gladi|workshop|upacara|nongkrong)\b/i', $lower);
        $type = $isEvent ? 'event' : 'task';

        // 2. Extract Duration (e.g. "3 jam", "90 menit", "1.5 jam", "2 jam 30 menit", "setengah jam")
        $duration = $this->extractDurationMinutes($lower);

        // 3. Extract Deadline or Date/Time
        $extractedDate = $this->extractDateTime($lower, $ref, $isEvent);

        // 4. Extract Subject / Matkul
        $subject = $this->detectSubject($text);

        // 5. Extract Priority
        $priority = $this->detectPriority($lower);

        // 6. Extract Subtasks (e.g. "bikin ERD sama normalisasi")
        $subtasks = $this->extractSubtasks($text);

        // 7. Clean Title
        $title = $this->cleanTitle($text, $subject, $isEvent);

        // 8. Event specific defaults
        $category = null;
        $location = null;
        if ($isEvent) {
            $category = $this->detectEventCategory($lower);
            $location = $this->extractLocation($text);
        }

        return new AiTaskDraft(
            type: $type,
            title: $title,
            subject: $subject,
            deadline: $extractedDate->format('Y-m-d H:i:s'),
            estimated_duration: $duration,
            priority: $priority,
            subtasks: $subtasks,
            description: $text,
            location: $location,
            category: $category,
            confidence: 0.95,
            original_text: $text
        );
    }

    /**
     * Extract duration in minutes from natural language.
     */
    public function extractDurationMinutes(string $text): int
    {
        if (preg_match('/setengah\s+jam/i', $text)) {
            return 30;
        }

        // e.g. "2 jam 30 menit" or "1 jam 45 mnt"
        if (preg_match('/(\d+)\s*jam\s*(\d+)\s*(?:menit|mnt)/i', $text, $m)) {
            return ((int) $m[1] * 60) + (int) $m[2];
        }

        // e.g. "1.5 jam" or "2,5 jam"
        if (preg_match('/(\d+(?:[\.,]\d+)?)\s*jam/i', $text, $m)) {
            $hours = (float) str_replace(',', '.', $m[1]);

            return max(15, (int) round($hours * 60));
        }

        // e.g. "45 menit" or "90 mnt"
        if (preg_match('/(\d+)\s*(?:menit|mnt)/i', $text, $m)) {
            return max(5, (int) $m[1]);
        }

        return 60; // Default 1 hour
    }

    /**
     * Extract Date and Time from text relative to reference date.
     */
    public function extractDateTime(string $text, Carbon $ref, bool $isEvent): Carbon
    {
        $date = $ref->copy();
        $hasSpecificDate = false;
        $hasSpecificTime = false;

        // --- Day Offset Detection ---
        if (preg_match('/\bhari ini\b/i', $text)) {
            $hasSpecificDate = true;
        } elseif (preg_match('/\bbesok lusa\b/i', $text)) {
            $date->addDays(2);
            $hasSpecificDate = true;
        } elseif (preg_match('/\bbesok\b/i', $text)) {
            $date->addDay();
            $hasSpecificDate = true;
        } elseif (preg_match('/\blusa\b/i', $text)) {
            $date->addDays(2);
            $hasSpecificDate = true;
        } elseif (preg_match('/(\d+)\s*hari\s*lagi/i', $text, $m)) {
            $date->addDays((int) $m[1]);
            $hasSpecificDate = true;
        } elseif (preg_match('/\bminggu depan\b/i', $text)) {
            $date->addWeek();
            $hasSpecificDate = true;
        }

        // Indonesian Day Names (senin, selasa, rabu, kamis, jumat, sabtu, minggu)
        $dayMap = [
            'senin' => 1, 'selasa' => 2, 'rabu' => 3,
            'kamis' => 4, 'jumat' => 5, 'sabtu' => 6, 'minggu' => 7,
        ];

        foreach ($dayMap as $dayName => $targetIso) {
            if (preg_match('/(?:hari\s+)?'.$dayName.'(?:\s+(depan|ini))?/i', $text, $m)) {
                $curIso = $date->dayOfWeekIso;
                $daysToAdd = ($targetIso - $curIso + 7) % 7;
                if ($daysToAdd === 0) {
                    $daysToAdd = 7;
                }

                $date->addDays($daysToAdd);
                $hasSpecificDate = true;
                break;
            }
        }

        // Explicit Date formats: e.g. "tgl 25", "tanggal 15 september", "2026-09-20"
        if (preg_match('/(?:tgl|tanggal)\s*(\d{1,2})(?:\s+([a-z]+))?/i', $text, $m)) {
            $day = (int) $m[1];
            $month = $date->month;
            if (! empty($m[2])) {
                $month = $this->parseIndonesianMonth($m[2]) ?? $month;
            }
            $date->setDay($day)->setMonth($month);
            $hasSpecificDate = true;
        }

        // --- Time Detection ---
        $hour = null;
        $minute = 0;

        // e.g. "jam 7 malam", "jam 8 pagi", "jam 2 siang", "jam 4 sore"
        if (preg_match('/(?:jam|pukul)\s*(\d{1,2})(?::(\d{2}))?\s*(pagi|siang|sore|malam)?/i', $text, $m)) {
            $h = (int) $m[1];
            $minute = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : 0;
            $period = isset($m[3]) ? strtolower($m[3]) : null;

            if ($period === 'malam' && $h < 12) {
                $h += 12;
            } elseif ($period === 'sore' && $h < 12) {
                $h += 12;
            } elseif ($period === 'siang' && $h >= 1 && $h <= 5) {
                $h += 12;
            }

            $hour = $h;
            $hasSpecificTime = true;
        } elseif (preg_match('/(\d{1,2})[:\.](\d{2})/i', $text, $m)) {
            // e.g. "14:30" or "23.59"
            $hour = (int) $m[1];
            $minute = (int) $m[2];
            $hasSpecificTime = true;
        }

        if ($hasSpecificTime && $hour !== null) {
            $date->setTime($hour, $minute, 0);
        } else {
            // Default time: Tasks usually default to 23:59 end-of-day; Events default to 09:00 morning
            if ($isEvent) {
                $date->setTime(19, 0, 0); // Evening meet default
            } else {
                $date->setTime(23, 59, 0);
            }
        }

        // If no explicit date mentioned and date is in the past, push to tomorrow
        if (! $hasSpecificDate && $date->isPast()) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Detect matching subject from registered courses or common academic keywords.
     */
    public function detectSubject(string $text): ?string
    {
        // Check exact or partial match from registered CourseSchedule database
        try {
            $courses = CourseSchedule::pluck('course_name')->unique();
            foreach ($courses as $course) {
                if (stripos($text, $course) !== false) {
                    return $course;
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback if table not accessible
        }

        // Common Indonesian academic subjects
        $subjects = [
            'Basis Data' => ['basis data', 'database', 'sql', 'erd'],
            'Pemrograman Web' => ['pemrograman web', 'pemweb', 'web programming', 'laravel'],
            'Algoritma & Pemrograman' => ['algoritma', 'alpro', 'struktur data'],
            'Jaringan Komputer' => ['jaringan komputer', 'jarkom', 'cisco', 'network'],
            'Sistem Operasi' => ['sistem operasi', 'linux'],
            'Kalkulus' => ['kalkulus', 'matematika', 'aljabar'],
            'Bahasa Inggris' => ['bahasa inggris', 'english'],
            'Kewarganegaraan' => ['kewarganegaraan', 'pancasila', 'pkn'],
        ];

        foreach ($subjects as $name => $keywords) {
            foreach ($keywords as $kw) {
                if (stripos($text, $kw) !== false) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Detect Priority level.
     */
    public function detectPriority(string $text): string
    {
        if (preg_match('/\b(urgent|darurat|kritis|segera|cepat|hari ini)\b/i', $text)) {
            return 'urgent';
        }
        if (preg_match('/\b(penting|tinggi|prioritas)\b/i', $text)) {
            return 'high';
        }
        if (preg_match('/\b(santai|low|ringan|nanti)\b/i', $text)) {
            return 'low';
        }

        return 'medium';
    }

    /**
     * Extract subtasks from sentence (e.g. "bikin ERD sama normalisasi" -> ["Bikin ERD", "Normalisasi"]).
     *
     * @return array<int, array{title: string, completed: bool}>
     */
    public function extractSubtasks(string $text): array
    {
        $subtasks = [];

        // 1. Look for action phrase "bikin|buat|kerjakan [items]"
        if (preg_match('/(?:bikin|buat|kerjakan)\s+([^,\.\n]+?)(?:\s+(?:sama|dan)\s+([^,\.\n]+))?(?:,|\.|$)/i', $text, $m)) {
            if (! empty($m[1])) {
                $subtasks[] = ['title' => ucfirst(trim($m[1])), 'completed' => false];
            }
            if (! empty($m[2])) {
                $subtasks[] = ['title' => ucfirst(trim($m[2])), 'completed' => false];
            }
        }

        // 2. Match bullet lists in text
        if (preg_match_all('/(?:^|[\r\n])\s*[-*•]\s*(.+)/', $text, $matches)) {
            foreach ($matches[1] as $item) {
                $trimmed = trim($item);
                if (! empty($trimmed)) {
                    $subtasks[] = ['title' => $trimmed, 'completed' => false];
                }
            }
        }

        return $subtasks;
    }

    /**
     * Clean and generate a natural Indonesian title.
     */
    public function cleanTitle(string $text, ?string $subject, bool $isEvent): string
    {
        $clean = $text;

        // Remove date and duration phrases from title
        $patterns = [
            '/(?:jumat|senin|selasa|rabu|kamis|sabtu|minggu)\s+(?:depan|ini)/i',
            '/(?:hari\s+ini|besok\s+lusa|besok|lusa|minggu\s+depan)/i',
            '/(?:kira-kira|sekitar)?\s*\d+(?:[\.,]\d+)?\s*(?:jam|menit|mnt)/i',
            '/(?:jam|pukul)\s*\d{1,2}(?::\d{2})?\s*(?:pagi|siang|sore|malam)?/i',
            '/\bada\s+(?:tugas|acara|jadwal)\b/i',
        ];

        foreach ($patterns as $pattern) {
            $clean = preg_replace($pattern, '', $clean);
        }

        $clean = trim(preg_replace('/[,\s]+/', ' ', $clean));
        $clean = trim($clean, ' .,-');

        if (empty($clean)) {
            return $isEvent ? 'Acara Baru' : ($subject ? "Tugas {$subject}" : 'Tugas Baru');
        }

        return ucfirst($clean);
    }

    /**
     * Breakdown task using structured Indonesian academic task knowledge.
     */
    public function heuristicBreakdown(string $prompt): AiBreakdownResult
    {
        $lower = strtolower($prompt);

        // 1. Software / Coding / Final Project
        if (preg_match('/\b(aplikasi|kasir|coding|web|sistem|proyek|tugas akhir|skripsi|fitur|backend|frontend)\b/i', $lower)) {
            $steps = [
                ['title' => 'Analisis Kebutuhan & Desain Fitur', 'phase' => 'Perencanaan', 'estimated_minutes' => 60],
                ['title' => 'Perancangan Skema Database & Relasi ERD', 'phase' => 'Database', 'estimated_minutes' => 90],
                ['title' => 'Desain Wireframe & Antarmuka UI/UX', 'phase' => 'Desain', 'estimated_minutes' => 60],
                ['title' => 'Setup Proyek & Implementasi Backend/API', 'phase' => 'Pengembangan', 'estimated_minutes' => 120],
                ['title' => 'Pengembangan Frontend & Integrasi Tampilan', 'phase' => 'Pengembangan', 'estimated_minutes' => 120],
                ['title' => 'Pengujian Fitur, Validasi & Bug Fixing', 'phase' => 'Testing', 'estimated_minutes' => 60],
                ['title' => 'Penyusunan Laporan Dokumentasi & Presentasi', 'phase' => 'Finalisasi', 'estimated_minutes' => 90],
            ];
            $tips = 'Fokus selesaikan skema database dan alur CRUD dasar terlebih dahulu sebelum memoles antarmuka.';
        }
        // 2. Paper / Makalah / Laporan
        elseif (preg_match('/\b(makalah|paper|laporan|laprak|jurnal|esai|artikel)\b/i', $lower)) {
            $steps = [
                ['title' => 'Pengumpulan Referensi & Studi Literatur', 'phase' => 'Riset', 'estimated_minutes' => 60],
                ['title' => 'Penyusunan Kerangka & Bab 1 (Pendahuluan)', 'phase' => 'Penulisan', 'estimated_minutes' => 45],
                ['title' => 'Penulisan Bab 2 (Landasan Teori & Kajian)', 'phase' => 'Penulisan', 'estimated_minutes' => 60],
                ['title' => 'Analisis Data & Penulisan Bab 3 (Pembahasan)', 'phase' => 'Analisis', 'estimated_minutes' => 90],
                ['title' => 'Penulisan Kesimpulan, Saran & Daftar Pustaka', 'phase' => 'Penulisan', 'estimated_minutes' => 45],
                ['title' => 'Proofreading, Format Sitasi & Cek Plagiarisme', 'phase' => 'Finalisasi', 'estimated_minutes' => 40],
            ];
            $tips = 'Gunakan manajer referensi seperti Mendeley/Zotero untuk mempermudah sitasi dan daftar pustaka.';
        }
        // 3. Presentation / Kelompok
        elseif (preg_match('/\b(presentasi|slide|ppt|kelompok)\b/i', $lower)) {
            $steps = [
                ['title' => 'Diskusi Kelompok & Pembagian Topik Materi', 'phase' => 'Persiapan', 'estimated_minutes' => 30],
                ['title' => 'Ringkasan Inti Materi & Struktur Alur Paparan', 'phase' => 'Konsep', 'estimated_minutes' => 45],
                ['title' => 'Desain Slide Presentasi & Visual Infografis', 'phase' => 'Desain', 'estimated_minutes' => 60],
                ['title' => 'Penyusunan Catatan Pembicara & Simulasi Q&A', 'phase' => 'Latihan', 'estimated_minutes' => 45],
            ];
            $tips = 'Jaga teks di slide seminimal mungkin, gunakan poin kunci dan grafik yang jelas.';
        }
        // 4. Default Breakdown
        else {
            $steps = [
                ['title' => 'Pahami Instruksi Soal & Kumpulkan Referensi', 'phase' => 'Persiapan', 'estimated_minutes' => 30],
                ['title' => 'Pengerjaan Draf Awal / Komponen Utama', 'phase' => 'Eksekusi', 'estimated_minutes' => 90],
                ['title' => 'Review Hasil Pengerjaan & Koreksi Kesalahan', 'phase' => 'Evaluasi', 'estimated_minutes' => 45],
                ['title' => 'Finalisasi Format Berkas & Submit Tugas', 'phase' => 'Submit', 'estimated_minutes' => 15],
            ];
            $tips = 'Bagi waktu pengerjaan menjadi blok-blok 25 menit (metode Pomodoro) agar fokus tetap terjaga.';
        }

        $totalMinutes = array_sum(array_column($steps, 'estimated_minutes'));

        return new AiBreakdownResult(
            goal: $prompt,
            steps: $steps,
            totalEstimatedMinutes: $totalMinutes,
            tips: $tips
        );
    }

    /**
     * Call Gemini LLM API (if configured) with graceful error recovery.
     */
    protected function callGeminiForParse(string $prompt, Carbon $ref): ?AiTaskDraft
    {
        try {
            $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
            $refDateStr = $ref->format('Y-m-d H:i:s');

            $systemPrompt = "You are an AI planner assistant. Today's date is {$refDateStr}. Extract structured academic planner data from the user input in Indonesian. Return strictly a JSON object with keys: type ('task' or 'event'), title, subject (nullable), deadline (Y-m-d H:i:s), estimated_duration (minutes integer), priority ('urgent','high','medium','low'), subtasks (array of strings), category (for event: 'kuliah','ujian','seminar','organisasi','pribadi'), location (nullable).";

            $response = Http::timeout(5)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => "{$systemPrompt}\n\nInput: \"{$prompt}\""]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($rawText) {
                    $data = json_decode($rawText, true);
                    if (is_array($data) && isset($data['title'])) {
                        return new AiTaskDraft(
                            type: $data['type'] ?? 'task',
                            title: $data['title'],
                            subject: $data['subject'] ?? null,
                            deadline: $data['deadline'] ?? $ref->addDay()->format('Y-m-d 23:59:00'),
                            estimated_duration: (int) ($data['estimated_duration'] ?? 60),
                            priority: $data['priority'] ?? 'medium',
                            subtasks: $data['subtasks'] ?? [],
                            description: $prompt,
                            location: $data['location'] ?? null,
                            category: $data['category'] ?? null,
                            confidence: 0.98,
                            original_text: $prompt
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini API parse fallback to heuristic: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Call Gemini LLM API for task breakdown.
     */
    protected function callGeminiForBreakdown(string $title, ?string $description): ?AiBreakdownResult
    {
        try {
            $apiKey = config('services.gemini.key') ?? env('GEMINI_API_KEY');
            $prompt = "Tugas: {$title}. Deskripsi: {$description}. Buatkan 4-7 subtasks terstruktur dalam Bahasa Indonesia. Return strict JSON: { steps: [{ title: string, phase: string, estimated_minutes: int }], tips: string }";

            $response = Http::timeout(5)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ]);

            if ($response->successful()) {
                $json = $response->json();
                $rawText = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                if ($rawText) {
                    $data = json_decode($rawText, true);
                    if (is_array($data) && ! empty($data['steps'])) {
                        $total = array_sum(array_column($data['steps'], 'estimated_minutes'));

                        return new AiBreakdownResult(
                            goal: $title,
                            steps: $data['steps'],
                            totalEstimatedMinutes: $total,
                            tips: $data['tips'] ?? null
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Gemini API breakdown fallback to heuristic: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Validate if a generated draft has enough substance to be presented.
     */
    protected function isValidDraft(AiTaskDraft $draft): bool
    {
        return strlen(trim($draft->title)) >= 3;
    }

    /**
     * Get Indonesian greeting based on current hour.
     */
    protected function getTimeGreeting(): string
    {
        $h = (int) now()->format('H');
        if ($h >= 4 && $h < 11) {
            return 'Selamat pagi';
        }
        if ($h >= 11 && $h < 15) {
            return 'Selamat siang';
        }
        if ($h >= 15 && $h < 18) {
            return 'Selamat sore';
        }

        return 'Selamat malam';
    }

    /**
     * Extract event category from keywords.
     */
    protected function detectEventCategory(string $text): string
    {
        if (preg_match('/\b(kuliah|kelas|dosen|kampus)\b/i', $text)) {
            return 'kuliah';
        }
        if (preg_match('/\b(ujian|uts|uas|kuis)\b/i', $text)) {
            return 'ujian';
        }
        if (preg_match('/\b(seminar|webinar|workshop|kuliah umum)\b/i', $text)) {
            return 'seminar';
        }
        if (preg_match('/\b(organisasi|himpunan|bem|rapat|panitia)\b/i', $text)) {
            return 'organisasi';
        }

        return 'pribadi';
    }

    /**
     * Extract location (e.g. "di Ruang TULT 08.01" or "di GKU").
     */
    protected function extractLocation(string $text): ?string
    {
        if (preg_match('/\b(?:di|at|lokasi:?)\s+([A-Za-z0-9\.\-\s]+?)(?:[\.,]|$)/i', $text, $m)) {
            $loc = trim($m[1]);
            // Exclude false positive day words
            if (! in_array(strtolower($loc), ['senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu', 'minggu', 'hari ini', 'besok'])) {
                return $loc;
            }
        }

        return null;
    }

    /**
     * Parse Indonesian month names to integer.
     */
    protected function parseIndonesianMonth(string $monthName): ?int
    {
        $map = [
            'januari' => 1, 'jan' => 1,
            'februari' => 2, 'feb' => 2,
            'maret' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'mei' => 5, 'may' => 5,
            'juni' => 6, 'jun' => 6,
            'juli' => 7, 'jul' => 7,
            'agustus' => 8, 'agt' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9,
            'oktober' => 10, 'okt' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'desember' => 12, 'des' => 12, 'dec' => 12,
        ];

        return $map[strtolower($monthName)] ?? null;
    }
}
