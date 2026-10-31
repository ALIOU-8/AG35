@extends('pdf.layout')

@section('title', 'Liste des membres')

@section('content')
    <h1>Liste des membres</h1>
    <p class="muted">
        {{ $members->count() }} comptes ·
        {{ $counts['active'] ?? 0 }} actifs · {{ $counts['pending'] ?? 0 }} en attente · {{ $counts['suspended'] ?? 0 }} suspendus
        · cotisation {{ $season }} : {{ number_format($due, 2, ',', ' ') }} €
    </p>

    <table class="data" style="margin-top: 10px;">
        <tr>
            <th>Nom</th><th>Email</th><th>Téléphone</th><th>Études</th><th>Origine</th>
            <th>Rôle / fonction</th><th>Statut</th><th>Cotisation {{ $season }}</th><th>Inscrit le</th>
        </tr>
        @foreach ($members as $member)
            <tr>
                <td>{{ $member->name }}</td>
                <td>{{ $member->email }}</td>
                <td>{{ $member->phone ?? '—' }}</td>
                <td>{{ collect([$member->school, $member->studies()])->filter()->implode(' · ') ?: '—' }}</td>
                <td>{{ $member->origin_city ?? '—' }}</td>
                <td>{{ $member->bureau_title ?? $member->roleLabel() }}</td>
                <td>{{ $member->statusLabel() }}</td>
                @if ($member->isActive())
                    <td class="{{ $member->fee_status }}">
                        {{ \App\Models\MembershipFee::PAYMENT_STATUSES[$member->fee_status] }}@if ($member->fee_status === 'partial') ({{ number_format((float) $member->paid, 2, ',', ' ') }} €)@endif
                    </td>
                @else
                    <td class="muted">—</td>
                @endif
                <td>{{ $member->created_at->format('d/m/Y') }}</td>
            </tr>
        @endforeach
    </table>
@endsection
