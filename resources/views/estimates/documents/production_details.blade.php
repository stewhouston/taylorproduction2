<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Production Details</title>
    <style type="text/css">
    body {
        /*height: 842px;*/
        width: 595px;
        /* to centre page on screen*/
        margin-left: auto;
        margin-right: auto;
    }

    body > table {
        width: 595px;
    }

    th {
        font-weight: normal;
        text-align: left;
    }

    table.document-items th {
        padding: 2px;
        background-color: #eee;
    }

    table.document-items td {
        padding: 2px;
    }

    table.inline-table-descriptors td:first-child {
        text-align: right;
        width: 120px;
    }

    table.inline-table-descriptors td {
        padding: 2px;
        border-bottom: 1px solid #ccc;
    }

    table.document-production-specs td {
        padding: 2px;
        white-space: nowrap;
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }
    </style>
</head>
<body>
    <div style="padding: 10px; padding-left: 0;" class="no-print">
        <a style="color: blue; text-decoration:underline; cursor: pointer; display: inline-block; margin-right: 8px;"
           onclick="window.print()"
        >
            Print
        </a>


        <a style="color: blue; text-decoration:underline; cursor: pointer;  display: inline-block;"
           onclick="window.close()"
        >
            Close Window
        </a>
    </div>

    <table id="header" style="margin-bottom: 15px;">
        <thead>
            <tr>
                <th colspan="3">
                    <h2 style="font-size: 14px;">
                      <!-- {{ $company->name }}  TSX Trust -->
                    </h2>
                </th>
            </tr>
            <tr>
                <th style="width: 80px;"><strong>Job Num:</strong></th>
                <th>{{$estimate->client_reference_number}}</th>
                <th style="width: 140px;">Estimate #{{$estimate->id}}</th>
            </tr>
            <tr>
                <th><strong>Job Name:</strong></th>
                <th>{{$estimate->client_reference_description}}</th>
                <th>Date: {{ date('d/m/Y') }}</th>
            </tr>
            <tr>
                <th><strong>Mail Date:</strong></th>
                <th>{{$estimate->client_reference_mail_date}}</th>
                <th>&nbsp;</th>
            </tr>
        </thead>
    </table>

    <hr style="margin: 0; border-color: #000;" />

    @foreach ($documents as $i => $document)
    <table class="document-items document-n-{{ $i }}" style="margin-top: 15px;">
        <thead>
            <tr>
                <th style="width: 30px; font-weight: bold; text-align: center;">{{ $i + 1 }}</th>
                <th style="width: 70px; text-align: center;"><strong>Qty</strong></th>
                <th><strong>Description</strong></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>&nbsp;</td>
                <td style="text-align: center;">
                    {{ number_format($document->quantity) }}
                </td>
                <td>{{ $document->report_description }}</td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>
                    @foreach ($document->components_filtered as $component)
                    <table class="inline-table-descriptors" width="100%" style="margin-top: 20px;">
                        <thead>
                            <tr>
                                <td colspan="2" style="text-align: left;">
                                    <strong>{{ $component->report_description }}</strong>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Device:</td>
                                <td>{{ $component->production->device }}</td>
                            </tr>
                            <tr>
                                <td>Impressions:</td>
                                <td>{{ $component->production->impressions }}</td>
                            </tr>
                            <tr>
                                <td>Run Sheets:</td>
                                <td>{{ $component->production->run_sheets }}</td>
                            </tr>
                            <tr>
                                <td>Run Sheet Size:</td>
                                <td>{{ $run_sheet_sizes[$component->production->run_sheet_sqin] ?? $component->production->run_sheet_sqin }}</td>
                            </tr>
                            <tr>
                                <td>Speed:</td>
                                <td>{{ $component->production->print_run_speed }}</td>
                            </tr>
                            <tr>
                                <td>Print Hours:</td>
                                <td>{{ $component->production->print_hours }}</td>
                            </tr>
                        </tbody>
                    </table>
                    @endforeach
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <table style="margin-left: 56px; margin-top: 16px;" class="document-production-specs">
                        @if ($document->production && $document->production->cut_hours > 0)
                        <tr>
                            <td>Cut Hours:</td>
                            <td>{{ $document->production->cut_hours }}</td>
                        </tr>
                        @endif
                        @if ($document->production && $document->production->fold_hours > 0)
                        <tr>
                            <td>Fold Hours:</td>
                            <td>{{ $document->production->fold_hours }}</td>
                        </tr>
                        @endif
                        @if ($document->production && $document->production->saddlestitch_hours > 0)
                        <tr>
                            <td>Saddlestitch Hours:</td>
                            <td>{{ $document->production->saddlestitch_hours }}</td>
                        </tr>
                        @endif
                        @if ($document->production && $document->production->perfect_bind_hours > 0)
                        <tr>
                            <td>Perfect Bind Hours:</td>
                            <td>{{ $document->production->perfect_bind_hours }}</td>
                        </tr>
                        @endif
                        @if ($document->production && $document->production->bind_hours > 0)
                        <tr>
                            <td>Total Bind Hours:</td>
                            <td>{{ $document->production->bind_hours }}</td>
                        </tr>
                        @endif
                        @if ($document->production)
                        <tr>
                            <td>Document Weight:</td>
                            <td>{{ $document->production->weight ?? '&mdash;' }}lbs |
                            @if ($document->production->weight)
                              {{ round($document->production->weight * 453.592, 0) }}g
                            @endif
                            </td>
                        </tr>
                        <tr>
                            <td>Production Hours:</td>
                            <td>{{ $document->production->hours ?? '&mdash;' }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
    @endforeach
</body>
</html>
