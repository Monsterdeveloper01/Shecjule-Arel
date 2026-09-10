# PERSONAL PLANNER — DEVELOPMENT ROADMAP V2–V4

## Project Context
Personal planner berbasis Laravel + React/Inertia + MySQL + PWA. V1 Foundation sudah selesai: Tasks, Events, College Schedule, Calendar, Today, dan PWA/Mobile.

## Product Vision
Berkembang dari Task Manager + Calendar menjadi **Personal Planning Intelligence System**.

Tujuan: ketika user membuka aplikasi, sistem memahami konteks hari user dan membantu menentukan apa yang sebaiknya dilakukan berikutnya.

## Architecture Principle
Gunakan alur:

DATA → ENGINE → INSIGHT → ACTION

Database menyimpan fakta. Engine melakukan perhitungan deterministik. AI digunakan untuk reasoning/natural language bila diperlukan.

## Roadmap

### V2 — Smart Engine
- Priority Engine
- Deadline Risk Engine
- Free Time Detection
- Smart Schedule Recommendation
- Smart Reminder
- Schedule Overload Detection
- Today Intelligence

### V3 — AI Assistant
- Natural Language Task/Event Input
- AI Task Breakdown
- Screenshot → Task
- Voice Input
- AI Daily Insight
- AI Planning Assistant

### V4 — Personal OS
- Focus Mode
- Productivity Analytics
- Habit / Streak
- Weekly Review
- Monthly Review
- Personal Productivity Pattern
- Long-term Planning Intelligence
- Personalized Recommendations

## Development Rules
1. Inspect existing project before changing anything.
2. Reuse existing architecture and functionality.
3. Avoid unnecessary rewrites.
4. Avoid duplicate logic.
5. Keep business logic out of React components.
6. Prefer reusable services/domain logic.
7. Preserve backward compatibility.
8. Implement incrementally and test each feature.
9. Do not let AI become the source of truth for structured data.
10. Mobile-first UX is the priority.

## Target UX
Today should answer:
- What is next?
- What is important?
- Am I at risk of missing a deadline?
- When am I free?
- What should I work on now?
- Is my schedule overloaded?
- What should I prepare for tomorrow?
