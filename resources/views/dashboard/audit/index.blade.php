<x-dashboard-layout>
    <x-slot name="title">Journal d'activité</x-slot>

    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-display font-bold text-gray-900">Journal d'activité</h1>
                <p class="text-sm text-gray-500 mt-1">Toutes les actions sensibles sur votre institut</p>
            </div>
        </div>

        <form method="GET" class="card p-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="form-label">Action</label>
                <select name="action" class="form-select">
                    <option value="">Toutes</option>
                    @foreach($actions as $a)
                        <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Type d'objet</label>
                <select name="subject_type" class="form-select">
                    <option value="">Tous</option>
                    @foreach($subjects as $s)
                        <option value="{{ $s }}" @selected(request('subject_type') === $s)>{{ class_basename($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Recherche</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Libellé ou ID..." class="form-input">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary">Filtrer</button>
                <a href="{{ route('dashboard.audit.index') }}" class="btn-outline">Reset</a>
            </div>
        </form>

        <div class="card overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-left">Utilisateur</th>
                        <th class="px-4 py-3 text-left">Action</th>
                        <th class="px-4 py-3 text-left">Objet</th>
                        <th class="px-4 py-3 text-left">Détails</th>
                        <th class="px-4 py-3 text-left">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-xs">
                                @if($log->user)
                                    {{ $log->user->prenom }} {{ $log->user->nom_famille }}
                                @else
                                    <span class="text-gray-400">Système</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $cls = match($log->action) {
                                        'created' => 'badge-success',
                                        'updated' => 'badge-info',
                                        'deleted' => 'badge-danger',
                                        default   => 'badge-primary',
                                    };
                                @endphp
                                <span class="{{ $cls }}">{{ $log->action }}</span>
                            </td>
                            <td class="px-4 py-3 text-xs">{{ $log->label ?? class_basename($log->subject_type) }}</td>
                            <td class="px-4 py-3 text-xs max-w-md">
                                @if($log->changes)
                                    <details>
                                        <summary class="cursor-pointer text-primary-600">Voir</summary>
                                        <pre class="mt-2 bg-gray-50 p-2 rounded text-[10px] overflow-x-auto">{{ json_encode($log->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400 font-mono">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-12 text-center text-gray-400">Aucune activité enregistrée</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $logs->links() }}
    </div>
</x-dashboard-layout>
