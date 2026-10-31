@extends('pdf.layout')

@php($money = fn (float $value) => number_format($value, 2, ',', ' ').' €')

@section('title', 'Cotisations '.$season)

@section('content')
    <h1>Cotisations — saison {{ $season }}</h1>
    <p class="muted">Cotisation annuelle : {{ $money($due) }} · {{ $stats['members'] }} membres actifs</p>

    <table class="kpis"><tr>
        <td>
            <span class="muted">Encaissé</span><br>
            <span class="kpi-value">{{ $money($stats['collected']) }}</span><br>
            <span class="muted">sur {{ $money($stats['expected']) }} attendus</span>
        </td>
        <td>
            <span class="muted">Taux de recouvrement</span><br>
            <span class="kpi-value">{{ $stats['rate'] }} %</span>
        </td>
        <td>
            <span class="muted">À jour</span><br>
            <span class="kpi-value paid">{{ $stats['paid'] }}</span>
        </td>
        <td>
            <span class="muted">Partielles / impayées</span><br>
            <span class="kpi-value"><span class="partial">{{ $stats['partial'] }}</span> / <span class="unpaid">{{ $stats['unpaid'] }}</span></span>
        </td>
    </tr></table>

    <table width="100%" style="margin-top: 4px;"><tr>
        <td width="58%" style="vertical-align: top; padding-right: 10px;">
            <h2>Encaissé par</h2>
            @if ($byRecorder->isEmpty())
                <p class="empty">Aucun paiement enregistré.</p>
            @else
                <table class="data">
                    <tr><th>Membre du bureau</th><th>Fonction</th><th class="right">Paiements</th><th class="right">Total</th></tr>
                    @foreach ($byRecorder as $row)
                        <tr>
                            <td>{{ $row->recorder?->name ?? 'Ancien membre' }}</td>
                            <td>{{ $row->recorder?->bureau_title ?? '—' }}</td>
                            <td class="right">{{ $row->payments }}</td>
                            <td class="right">{{ $money((float) $row->total) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </td>
        <td width="42%" style="vertical-align: top;">
            <h2>Par moyen de paiement</h2>
            @if ($byMethod->isEmpty())
                <p class="empty">—</p>
            @else
                <table class="data">
                    <tr><th>Moyen</th><th class="right">Paiements</th><th class="right">Total</th></tr>
                    @foreach ($byMethod as $row)
                        <tr>
                            <td>{{ $row->method }}</td>
                            <td class="right">{{ $row->payments }}</td>
                            <td class="right">{{ $money((float) $row->total) }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </td>
    </tr></table>

    <h2>Membres restant à recouvrer ({{ $toCollect->count() }})</h2>
    @if ($toCollect->isEmpty())
        <p class="empty">Tous les membres actifs sont à jour.</p>
    @else
        <table class="data">
            <tr><th>Membre</th><th>Email</th><th>Téléphone</th><th>Statut</th><th class="right">Versé</th><th class="right">Reste</th></tr>
            @foreach ($toCollect as $member)
                <tr>
                    <td>{{ $member->name }}</td>
                    <td>{{ $member->email }}</td>
                    <td>{{ $member->phone ?? '—' }}</td>
                    <td class="{{ $member->payment_status }}">{{ \App\Models\MembershipFee::PAYMENT_STATUSES[$member->payment_status] }}</td>
                    <td class="right">{{ $money($member->paid_amount) }}</td>
                    <td class="right">{{ $money(max(0, $due - $member->paid_amount)) }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="5">Reste à recouvrer</td>
                <td class="right">{{ $money($toCollect->sum(fn ($member) => max(0, $due - $member->paid_amount))) }}</td>
            </tr>
        </table>
    @endif

    <h2>Journal des paiements ({{ $payments->count() }})</h2>
    @if ($payments->isEmpty())
        <p class="empty">Aucun paiement enregistré pour cette saison.</p>
    @else
        <table class="data">
            <tr><th>Date</th><th>Membre</th><th class="right">Montant</th><th>Moyen</th><th>Encaissé par</th><th>Note</th></tr>
            @foreach ($payments as $payment)
                <tr>
                    <td>{{ $payment->paid_on->format('d/m/Y') }}</td>
                    <td>{{ $payment->user->name }}</td>
                    <td class="right">{{ $money((float) $payment->amount) }}</td>
                    <td>{{ $payment->method }}</td>
                    <td>{{ $payment->recorder?->name ?? 'Ancien membre' }}</td>
                    <td>{{ $payment->note }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="2">Total encaissé</td>
                <td class="right">{{ $money((float) $payments->sum('amount')) }}</td>
                <td colspan="3"></td>
            </tr>
        </table>
    @endif
@endsection
