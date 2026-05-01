<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Transaction Report</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #334155;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #1e293b;
            font-size: 24px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            margin: 5px 0 0;
            color: #64748b;
            font-size: 14px;
        }
        .filters {
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            font-size: 12px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-align: left;
            padding: 12px 10px;
            border-bottom: 2px solid #e2e8f0;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 12px;
        }
        .amount {
            text-align: right;
            font-weight: bold;
        }
        .credit { color: #059669; }
        .debit { color: #dc2626; }
        .transfer { color: #4f46e5; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
        }
        .summary {
            margin-top: 20px;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Financial Transaction Report</h1>
        <p>Generated on {{ now()->format('d M, Y H:i') }}</p>
    </div>

    <div class="filters">
        <strong>Applied Filters:</strong>
        @if($filters['search']) Search: "{{ $filters['search'] }}" | @endif
        @if($filters['type']) Type: {{ ucfirst($filters['type']) }} | @endif
        @if($filters['user']) User: {{ \App\Models\User::find($filters['user'])?->name }} | @endif
        @if($filters['from']) From: {{ $filters['from'] }} | @endif
        @if($filters['to']) To: {{ $filters['to'] }} | @endif
        @if(!$filters['search'] && !$filters['type'] && !$filters['user'] && !$filters['from'] && !$filters['to'])
            None (All Transactions)
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Description</th>
                <th>Category</th>
                <th>Account</th>
                <th>Added By</th>
                <th style="text-align: right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $tx)
                <tr>
                    <td>{{ $tx->date->format('d M, Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($tx->time)->format('h:i A') }}</td>
                    <td>{{ $tx->description ?? ($tx->transaction_details ?? 'Unspecified') }}</td>
                    <td>{{ $tx->tag ?? '-' }}</td>
                    <td>
                        @if($tx->type === 'transfer')
                            {{ $tx->fromAccount?->name }} → {{ $tx->toAccount?->name }}
                        @else
                            {{ $tx->mainAccount?->name ?? 'Default' }}
                        @endif
                    </td>
                    <td>{{ $tx->user?->name ?? 'System' }}</td>
                    <td class="amount {{ $tx->type }}">
                        {{ $tx->type === 'debit' ? '-' : '' }}₹{{ number_format($tx->amount, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        Total Transactions: {{ count($transactions) }}
    </div>

    <div class="footer">
        This is a system generated report from {{ config('app.name') }}.
    </div>
</body>
</html>
