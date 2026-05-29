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
