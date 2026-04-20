@extends('layouts.admin')
@section('page-title', 'Messages')

@section('content')
<div class="space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="page-title">Messages de contact</h1>
            <p class="page-subtitle">{{ $nonLus }} message(s) non lu(s)</p>
        </div>
    </div>

    <div class="card overflow-hidden">
        <table class="table-auto">
            <thead>
            <tr>
                <th>Expéditeur</th>
                <th>Sujet</th>
                <th>Reçu le</th>
                <th>Lu</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($messages as $msg)
            <tr class="{{ $msg->lu ? 'bg-white' : 'bg-primary-50' }} hover:bg-gray-50">
                <td>
                    <div class="font-medium text-gray-900 {{ !$msg->lu ? 'font-bold' : '' }}">{{ $msg->nom }}</div>
                    <div class="text-xs text-gray-400">{{ $msg->email }}</div>
                </td>
                <td class="text-sm {{ !$msg->lu ? 'font-semibold text-gray-900' : 'text-gray-600' }}">
                    {{ Str::limit($msg->message, 50) }}
                </td>
                <td class="text-sm text-gray-500">{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    @if($msg->lu)
                        <span class="badge badge-success text-xs">Lu</span>
                    @else
                        <span class="badge bg-yellow-100 text-yellow-700 text-xs">Non lu</span>
                    @endif
                </td>
                <td class="flex items-center gap-3">
                    <button onclick="document.getElementById('modal-{{ $msg->id }}').showModal()"
                            class="text-primary-600 text-sm hover:underline">Lire</button>
                    <form id="form-msg-{{ $msg->id }}" action="{{ route('admin.messages.destroy', $msg) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="button" class="text-red-500 text-sm hover:underline"
                                onclick="window.dispatchEvent(new CustomEvent('confirm-action',{detail:{formId:'form-msg-{{ $msg->id }}',title:'Supprimer ce message',message:'Ce message sera définitivement supprimé.',confirmLabel:'Supprimer',confirmClass:'!bg-red-600 hover:!bg-red-700',danger:true}}))">Supprimer</button>
                    </form>
                </td>
            </tr>

            {{-- Dialog natif HTML --}}
            <dialog id="modal-{{ $msg->id }}" class="rounded-2xl shadow-2xl max-w-lg w-full p-8 backdrop:bg-black/50">
                <h2 class="font-bold text-lg text-gray-900 mb-1">Message de {{ $msg->nom }}</h2>
                <p class="text-sm text-gray-400 mb-4">De : <strong>{{ $msg->nom }}</strong> ({{ $msg->email }}) — {{ $msg->telephone ?? '' }}</p>
                <div class="bg-gray-50 rounded-xl p-4 text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $msg->message }}</div>
                <div class="mt-5 flex justify-between items-center">
                    @if(!$msg->lu)
                    <form action="{{ route('admin.messages.lire', $msg) }}" method="POST">
                        @csrf @method('PATCH')
                        <button class="text-sm text-primary-600 hover:underline">Marquer comme lu</button>
                    </form>
                    @else
                    <span></span>
                    @endif
                    <button onclick="document.getElementById('modal-{{ $msg->id }}').close()"
                            class="btn-secondary text-sm">Fermer</button>
                </div>
            </dialog>
            @empty
            <tr><td colspan="5" class="text-center py-10 text-gray-400">Aucun message.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{ $messages->links() }}
</div>

@push('scripts')
<script>
    // Auto-open dialog on page load if #msg=xxx in URL (optional)
    const params = new URLSearchParams(location.search);
    if (params.get('open')) {
        const d = document.getElementById('modal-' + params.get('open'));
        if (d) d.showModal();
    }
</script>
@endpush
@endsection
