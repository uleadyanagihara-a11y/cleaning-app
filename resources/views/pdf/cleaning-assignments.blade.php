<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>掃除当番表 {{ $assignment_date->format('Y-m-d') }}</title>
    <style>
        @page {
            margin: 18mm 15mm 16mm;
        }

        @if ($fontPath !== null)
        @font-face {
            font-family: 'IPAex Gothic';
            font-style: normal;
            font-weight: normal;
            src: url('file://{{ $fontPath }}') format('truetype');
        }
        @endif

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: @if ($fontPath !== null) 'IPAex Gothic', @endif 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
        }

        h1 {
            margin: 0 0 4mm;
            text-align: center;
            font-size: 20pt;
            font-weight: normal;
            letter-spacing: 0.08em;
        }

        .date {
            margin: 0 0 7mm;
            text-align: center;
            font-size: 12pt;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #4b5563;
            padding: 3.2mm 3mm;
            vertical-align: middle;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        th {
            background: #e5e7eb;
            text-align: center;
            font-weight: normal;
        }

        .role-column {
            width: 30%;
        }

        .members-column {
            width: 46%;
        }

        .count-column {
            width: 12%;
        }

        .number {
            text-align: center;
        }

        .shortage {
            color: #b91c1c;
        }

        .summary {
            margin-top: 6mm;
            padding: 3mm 4mm;
            border: 1px solid #9ca3af;
            background: #f9fafb;
            text-align: right;
        }

        .summary span {
            margin-left: 7mm;
        }

        .generated-at {
            margin-top: 7mm;
            color: #6b7280;
            text-align: right;
            font-size: 8.5pt;
        }
    </style>
</head>
<body>
    <h1>掃除当番表</h1>
    <p class="date">
        {{ $assignment_date->year }}年{{ $assignment_date->month }}月{{ $assignment_date->day }}日
    </p>

    <table>
        <thead>
            <tr>
                <th class="role-column">掃除役割</th>
                <th class="members-column">担当者</th>
                <th class="count-column">必要人数</th>
                <th class="count-column">不足</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td>{{ $role['name'] }}</td>
                    <td>{{ implode('、', $role['member_names']) }}</td>
                    <td class="number">{{ $role['required_member_count'] }}名</td>
                    <td class="number {{ $role['shortage_count'] > 0 ? 'shortage' : '' }}">
                        {{ $role['shortage_count'] }}名
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <span>必要人数：{{ $required_member_count }}名</span>
        <span>割当人数：{{ $assigned_member_count }}名</span>
        <span class="{{ $shortage_count > 0 ? 'shortage' : '' }}">不足：{{ $shortage_count }}名</span>
    </div>

    <p class="generated-at">出力日時：{{ now()->format('Y-m-d H:i') }}</p>
</body>
</html>
