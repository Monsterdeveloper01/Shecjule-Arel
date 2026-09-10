# V3 — AI Assistant

Setelah V2 stabil, tambahkan AI sebagai enhancement.

## V3.1 Natural Language Input
Contoh:
"Jumat depan ada tugas basis data, bikin ERD sama normalisasi, kira-kira 3 jam."

AI menghasilkan structured draft:
- title
- subtasks
- deadline
- estimated duration

Jangan langsung insert tanpa confirmation. User dapat Add Task / Edit.

## V3.2 AI Task Breakdown
Contoh:
"Buat aplikasi kasir untuk tugas akhir."

AI dapat memecah:
- analisis kebutuhan
- desain database
- UI/UX
- backend
- frontend
- testing
- dokumentasi
- presentasi

## V3.3 Screenshot → Task
Upload screenshot chat/papan tulis. AI mendeteksi tasks, deadline, dan informasi terkait. Tampilkan preview dan beri opsi Add All/Edit.

## V3.4 Voice Input
Contoh:
"Besok jam 7 malam rapat organisasi."

Parse menjadi Event dengan title/date/time. Untuk task juga sama.

## V3.5 AI Daily Insight
Smart Engine menyediakan data terstruktur; AI menjelaskan dengan bahasa natural.

AI bukan source of truth. Structured data tetap divalidasi dan dikonfirmasi user.
