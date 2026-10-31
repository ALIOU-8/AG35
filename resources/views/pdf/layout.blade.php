<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    {{-- Dompdf : CSS 2.1 uniquement (pas de Tailwind, de flex ni de grid) ; DejaVu Sans affiche accents et €. --}}
    <style>
        @page { margin: 26px 30px 44px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5px; color: #111827; }
        .stripe { width: 100%; border-collapse: collapse; }
        .stripe td { height: 5px; padding: 0; }
        .header { width: 100%; margin-top: 8px; }
        .brand { font-size: 16px; font-weight: bold; color: #006744; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        h1 { font-size: 17px; margin: 14px 0 2px; }
        h2 { font-size: 11.5px; color: #006744; margin: 16px 0 5px; padding-bottom: 3px; border-bottom: 1.5px solid #c2e9d8; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #e6f6ef; color: #005036; text-align: left; padding: 5px 6px; font-size: 8px; text-transform: uppercase; }
        table.data th.right { text-align: right; }
        table.data td { padding: 4.5px 6px; border-bottom: 0.6px solid #e5e7eb; vertical-align: top; }
        table.data tr.total td { font-weight: bold; border-top: 1.2px solid #9ca3af; border-bottom: none; }
        .kpis { width: 100%; table-layout: fixed; border-collapse: separate; border-spacing: 6px 0; margin: 8px -6px 0; }
        .kpis td { width: 25%; border: 0.8px solid #d1d5db; border-radius: 6px; padding: 8px 10px; vertical-align: top; }
        .kpi-value { font-size: 14px; font-weight: bold; }
        .paid { color: #007f53; font-weight: bold; }
        .partial { color: #b45309; font-weight: bold; }
        .unpaid { color: #b91c1c; font-weight: bold; }
        .empty { color: #6b7280; font-style: italic; padding: 6px 0; }
        .footer { position: fixed; bottom: -28px; left: 0; right: 0; font-size: 8px; color: #9ca3af; }
        .page-number:after { content: "Page " counter(page); }
    </style>
</head>
<body>
    <div class="footer">
        <table width="100%"><tr>
            <td>{{ $association }} — document interne</td>
            <td class="right page-number"></td>
        </tr></table>
    </div>

    <table class="stripe"><tr>
        <td style="background: #CE1126;"></td>
        <td style="background: #FCD116;"></td>
        <td style="background: #009460;"></td>
    </tr></table>

    <table class="header"><tr>
        <td>
            <span class="brand">AG35</span><br>
            <span class="muted">{{ $association }}</span>
        </td>
        <td class="right muted">
            Édité le {{ now()->translatedFormat('j F Y à H:i') }}<br>
            par {{ $generatedBy }}
        </td>
    </tr></table>

    @yield('content')
</body>
</html>
