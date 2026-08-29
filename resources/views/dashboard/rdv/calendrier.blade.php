<x-dashboard-layout>
    <x-slot name="title">Calendrier RDV</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900">Calendrier des rendez-vous</h1>
                <p class="text-sm text-gray-500 mt-1">Glissez-déposez un RDV pour le déplacer • cliquez pour ouvrir</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('dashboard.rdv.index') }}" class="btn-outline">Vue liste</a>
                <a href="{{ route('dashboard.rdv.create') }}" class="btn-primary">+ Nouveau RDV</a>
            </div>
        </div>

        <div class="card p-4">
            <div id="calendar"></div>
        </div>
    </div>

    @push('head')
        <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales/fr.global.min.js"></script>
        <style>
            /* ── FullCalendar — thème CLAIR (défaut) ─────────────────────── */
            .fc { color: #374151; }
            .fc .fc-toolbar-title { color: #111827; font-size: 1.1rem; }
            .fc .fc-button { background: #f3f4f6; border-color: #e5e7eb; color: #374151; }
            .fc .fc-button:hover { background: #e5e7eb; border-color: #d1d5db; }
            .fc .fc-button-primary:not(:disabled).fc-button-active,
            .fc .fc-button-primary:not(:disabled):active { background: #7c3aed; border-color: #7c3aed; color: #fff; }
            .fc .fc-col-header-cell { background: #f9fafb; border-color: #e5e7eb; }
            .fc .fc-col-header-cell-cushion { color: #6b7280; font-weight: 600; text-decoration: none; padding: 6px 4px; }
            .fc .fc-timegrid-slot-label-cushion { color: #6b7280; font-size: 0.75rem; }
            .fc .fc-timegrid-slot { border-color: #f3f4f6; }
            .fc .fc-scrollgrid, .fc .fc-scrollgrid-section > td { border-color: #e5e7eb; }
            .fc .fc-daygrid-day, .fc .fc-timegrid-col { background: transparent; border-color: #e5e7eb; }
            .fc .fc-day-today { background: rgba(124,58,237,0.06) !important; }
            .fc .fc-daygrid-day-number { color: #4b5563; text-decoration: none; }
            .fc .fc-list-event:hover td { background: rgba(0,0,0,0.02); }
            .fc .fc-list-day-cushion { background: #f9fafb; }
            .fc .fc-list-day-text, .fc .fc-list-day-side-text { color: #6b7280; }
            .fc .fc-list-event-title a { color: #374151; text-decoration: none; }
            .fc .fc-list-event-time { color: #6b7280; }
            .fc .fc-event { border-radius: 5px; padding: 2px 4px; }
            .fc .fc-event-title, .fc .fc-event-time { color: #fff !important; font-weight: 600; }
            /* Vue liste : texte foncé sur fond clair (sinon invisible) */
            .fc .fc-list-event .fc-event-title, .fc .fc-list-event .fc-event-time { color: #111827 !important; }
            .dark .fc .fc-list-event .fc-event-title, .dark .fc .fc-list-event .fc-event-time { color: #f1f5f9 !important; }

            /* ── FullCalendar — thème SOMBRE (via classe .dark) ──────────── */
            .dark .fc { color: #e2e8f0; }
            .dark .fc .fc-toolbar-title { color: #f1f5f9; }
            .dark .fc .fc-button { background: #334155; border-color: #475569; color: #e2e8f0; }
            .dark .fc .fc-button:hover { background: #475569; border-color: #64748b; }
            .dark .fc .fc-button-primary:not(:disabled).fc-button-active,
            .dark .fc .fc-button-primary:not(:disabled):active { background: #7c3aed; border-color: #7c3aed; color: #fff; }
            .dark .fc .fc-col-header-cell { background: #1e293b; border-color: #334155; }
            .dark .fc .fc-col-header-cell-cushion { color: #94a3b8; }
            .dark .fc .fc-timegrid-slot-label-cushion { color: #94a3b8; }
            .dark .fc .fc-timegrid-slot { border-color: #1e293b; }
            .dark .fc .fc-scrollgrid, .dark .fc .fc-scrollgrid-section > td { border-color: #334155; }
            .dark .fc .fc-daygrid-day, .dark .fc .fc-timegrid-col { background: transparent; border-color: #334155; }
            .dark .fc .fc-day-today { background: rgba(124,58,237,0.08) !important; }
            .dark .fc .fc-daygrid-day-number { color: #cbd5e1; }
            .dark .fc .fc-list-event:hover td { background: rgba(255,255,255,0.04); }
            .dark .fc .fc-list-day-cushion { background: #1e293b; }
            .dark .fc .fc-list-day-text, .dark .fc .fc-list-day-side-text { color: #94a3b8; }
            .dark .fc .fc-list-event-title a { color: #e2e8f0; }
            .dark .fc .fc-list-event-time { color: #94a3b8; }

            /* Mobile : toolbar compacte */
            @media (max-width: 640px) {
                .fc .fc-toolbar { flex-direction: column; gap: 8px; align-items: flex-start; }
                .fc .fc-toolbar-title { font-size: 0.95rem; }
                .fc .fc-button { padding: 4px 8px; font-size: 0.75rem; }
                .fc .fc-header-toolbar .fc-toolbar-chunk { display: flex; flex-wrap: wrap; gap: 4px; }
            }
        </style>
    @endpush

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const el = document.getElementById('calendar');
            if (!el) return;
            const cal = new FullCalendar.Calendar(el, {
                initialView: 'timeGridWeek',
                locale: 'fr',
                firstDay: 1,
                slotMinTime: '07:00:00',
                slotMaxTime: '21:00:00',
                height: 'auto',
                editable: true,
                eventResizableFromStart: false,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
                },
                buttonText: {
                    today:    "Aujourd'hui",
                    month:    'Mois',
                    week:     'Semaine',
                    day:      'Jour',
                    list:     'Liste',
                },
                events: (info, success, failure) => {
                    fetch(`{{ route('dashboard.rdv.events') }}?start=${info.startStr}&end=${info.endStr}`)
                        .then(r => r.json()).then(success).catch(failure);
                },
                eventDrop: (info) => {
                    fetch(`/dashboard/rdv/${info.event.id}/move`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ debut_le: info.event.start.toISOString().slice(0,19).replace('T',' ') }),
                    }).then(r => {
                        if (!r.ok) { info.revert(); alert('Impossible de déplacer ce RDV.'); }
                    }).catch(() => { info.revert(); });
                },
                eventClick: (info) => {
                    if (info.event.url) { info.jsEvent.preventDefault(); window.location.href = info.event.url; }
                },
            });
            cal.render();
        });
    </script>
    @endpush
</x-dashboard-layout>
