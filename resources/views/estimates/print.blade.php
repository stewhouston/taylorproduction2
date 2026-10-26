<div class="container">
    <div class="block estimate">

        <div class="taylor-estimate-header-container">
            <div style="padding: 10px;" id="print-btns">
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

            @if ($estimate->estimate_comment)
            <div style="width: 600px; margin: 0 auto; text-align: center; color: #444; font-style: italic; margin-top: -4px;"><strong>Comment:</strong> {{ $estimate->estimate_comment }}</div>
            @endif

            <div class="estimate-alerts-message" style="display: {{ $estimate->has_alert && $estimate->alerts ? 'block' : 'none' }};">
                <strong>Alerts ({{ count($estimate->getAlerts()) }}):</strong>
                @foreach ($estimate->getAlerts() as $alert_msg)
                    {{ $alert_msg }}<br />
                @endforeach
            </div>

            <h2 style="
                float: right;
                font-size: 42px;
                margin: 0;
                margin-top: 23px;
            ">Estimate</h2>

            <div style="">
                <img src="https://storage.googleapis.com/taylor-estimating-prov.appspot.com/images/taylor-logo-1.png" style="
                    height: 100px;
                    display: block;
                    margin-bottom: 6px;
                ">
            </div>
            <div style="
                text-align: center;
                margin-bottom: 6px;
                font-size: 14px;
            ">Taylor Demers Mail Processing Inc. ‐ 8 Kenview Blvd, Brampton, ON, L6T 5E4<br />
                Phone: (416) 332‐6806 Fax: (416) 427‐5533
            </div>
        </div>

    <table id="header-table" style="border:3px solid #000000; margin:0 0 0 0;" width="100%" cellspacing="0">
        <tbody>
            <tr>
              <td style="
                  border-right: 3px solid #000;
                  padding: 10px;
              ">
                  <div style="text-align: center;">
                      <!--{{ $estimate->company->name }}-->
                      <!--TSX Trust-->
                  </div>
                  Number: {{$estimate->client_reference_number}}<br />
                  Description: {{$estimate->client_reference_description}}<br />
                  Mail Date: {{$estimate->client_reference_mail_date}}
              </td>
              <td align="right" style="
                  padding: 10px;
                  vertical-align: top;
              ">
                <strong>Estimate</strong>
                    #{{$estimate->id}} <br />
                <strong>Date:</strong>
                    {{ date('d/m/Y', strtotime($estimate->created_at)) }} <br />
                <strong>By:</strong>
                    @if ($estimate->createdBy)
                        {{$estimate->createdBy->name}}
                    @else
                        —
                    @endif
              </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 3px solid #000; padding: 10px;">
                    <strong>FileReq:</strong> <span class="files-required-by">
                    @if ($has_file_req_adjustment)
                        {{ $estimate->files_require_adjustment }}
                    @elseif ($estimate->dropInstances->count() > 0 && empty($estimate->files_required_by))
                        <!-- drops are created but no file req date has been set -->
                        –
                    @elseif ($estimate->dropInstances->count() > 0)
                        {{ $estimate->files_required_by }}
                    @else
                        –
                    @endif
                    </span>
                    @if ($estimate->file_require_adjust_reason)
                    <div style="color: #333; font-style: italic; font-size: 13px; margin-bottom: 4px;">
                        {{ $estimate->file_require_adjust_reason }}
                    </div>
                    @endif

                    <script>
                    $('.files-required-by').text(function(i, sqlDate) {
                        sqlDate = sqlDate.trim();

                        if (!sqlDate || sqlDate == '–') {}
                        else {
                            return moment(sqlDate).format('MMM D, YYYY - h:mma');
                        }
                    });
                    </script>
                    <!--<div style="float: right;">
                        <em>$200 Rush Fee applies if files not received by 4pm &ndash;</em>
                    </div>-->
                </td>
            </tr>
        </tbody>
    </table>

        <table id="items-table" width="100%" border="1" cellspacing="0" cellpadding="0" style="margin: 0 0 0 0;">
          <tbody>
            <tr>
                <td width="100" align="center" bgcolor="#eee" style="padding:0.25em 0.25em 0.25em 0.25em"><strong>Qty</strong></td>
                <td colspan="2" align="left" bgcolor="#eee" style="padding:0.25em 0.25em 0.25em 0.25em"><strong>Description</strong></td>
                <td width="133" align="right" bgcolor="#eee" style="padding:0.25em 0.25em 0.25em 0.25em"><strong>Cost</strong>&nbsp;&nbsp;</td>
            </tr>
            @foreach($estimate->documents as $document)
            <tr> {{-- Document's will be BOLD | Component's will not be --}}
                <td style="padding:0.25em 0.25em 0.25em 0.25em; text-align: center;">
                    {{ $document->quantity }}
                </td>
                <td colspan="2" style="padding:0.25em 0.25em 0.25em 0.25em;">{{$document->report_description}}</td>
                <td align="right" style="padding:0.25em 0.25em 0.25em 0.25em">{{number_format($document->production->sell,2)}}&nbsp;&nbsp;</td>
            </tr>
            @foreach($document->components as $component)
            <tr>
                <td style="padding:0.25em 0.25em 0.25em 0.25em; text-align: center;">
                    &nbsp;
                </td>
                <td colspan="2" style="padding:0.25em 0.25em 0.25em 0.25em; padding-left: 30px;">&bull; {{$component->report_description}}</td>
                <td align="right" style="padding:0.25em 0.25em 0.25em 0.25em">&nbsp;</td>
            </tr>
            @endforeach
            <tr>
                <td style="padding:0.25em 0.25em 0.25em 0.25em"><strong>&nbsp;</strong></td>
                <td colspan="2" style="padding:0.25em 0.25em 0.25em 0.25em">&nbsp;</td>
                <td align="center" style="padding:0.25em 0.25em 0.25em 0.25em">&nbsp;</td>
            </tr>
            @endforeach
          </tbody>
        </table>

        <table id="totals-table" style="width: 100%; border: 3px solid #000; padding: 5px;">
            <tr>
                <!-- rowspan larger than reality so formatting doesnt break -->
                <td rowspan="99" style="vertical-align: top; width: 50%;">
                    <div style="padding-right: 10px;">Postage must be received 24 hours in advance of mail date. This estimate is valid for 30 days and is subject to change  on review of 'live' data and material.</div>
                </td>
                <td style="width: 20%; white-space: nowrap;">Print Sub Total</td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        {{ number_format($costs['print_sub_total'], 2) }}
                    </span>
                </td>
            </tr>
            @if ($estimate->print_sell_adjustment)
            <tr id="drop-sell-adjustment">
                <td style="white-space: nowrap;">
                    <span style="font-size: 90%; color: #999;">&nbsp;&nbsp;&nbsp;Adjustment</span>
                </td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right; white-space: nowrap;">
                        @if ($estimate->print_sell_adjustment < 0)
                            ({{ str_replace('-', '', number_format($estimate->print_sell_adjustment, 2)) }})
                        @else
                            {{ number_format($estimate->print_sell_adjustment, 2) }}
                        @endif
                    </span>
                </td>
            </tr>
            @endif
            <tr id="rush-fee-quote-row" style="display: {{ $estimate->has_rush_fee ? 'table-row' : 'none' }}">
                <td style="white-space: nowrap;">Rush Fee</td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        206.00
                    </span>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap; padding-bottom: 16px;">Print Total</td>
                <td style="vertical-align: top;">
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        {{ number_format($costs['sub_total_with_rush_fee'], 2) }}
                    </span>
                </td>
            </tr>

            <tr>
                <td style="white-space: nowrap;">Shipping Sub Total</td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        <span style="padding-right: 6px; font-size: 90%;">({{ $estimate->dropInstances->count() }} drops)</span>
                        {{ number_format($costs['ship_sub_total'], 2) }}
                    </span>
                </td>
            </tr>
            @if ($estimate->drop_sell_adjustment)
            <tr id="drop-sell-adjustment">
                <td style="white-space: nowrap;">
                    <span style="font-size: 90%; color: #999;">&nbsp;&nbsp;&nbsp;Adjustment</span>
                </td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right; white-space: nowrap;">
                        @if ($estimate->drop_sell_adjustment < 0)
                            ({{ str_replace('-', '', number_format($estimate->drop_sell_adjustment, 2)) }})
                        @else
                            {{ number_format($estimate->drop_sell_adjustment, 2) }}
                        @endif
                    </span>
                </td>
            </tr>
            @endif
            <tr>
                <td style="white-space: nowrap; padding-bottom: 16px;">Shipping Total</td>
                <td style="vertical-align: top;">
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        {{ number_format($costs['shipping_cost'], 2) }}
                    </span>
                </td>
            </tr>

            <tr>
                <td style="white-space: nowrap;">Estimate Sub Total</td>
                <td style="vertical-align: top;">
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        {{ number_format($costs['sub_total_with_ship'], 2) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td style="white-space: nowrap;">HST (13%)</td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                    {{ number_format($costs['taxes'], 2) }}
                    </span>
                </td>
            </tr>
            <tr>
                <td>Total</td>
                <td>
                    <span style="float: left;">$</span>
                    <span style="float: right;">
                        {{ number_format($costs['total'], 2) }}
                    </span>
                </td>
            </tr>
        </table>

        @if ($uses_inactive_rate_set)
        <div style="padding-top: 6px; font-size: 12px;"><strong>Comment:</strong> This estimate does not use the current rate table.</div>
        @endif

        @if ($estimate->print_sell_adjust_reason)
        <div style="padding-top: 6px; font-size: 12px;"><strong>Print Adjust Reason:</strong> {{ $estimate->print_sell_adjust_reason }}</div>
        @endif

        @if ($estimate->drop_sell_adjust_reason)
        <div style="padding-top: 6px; font-size: 12px;"><strong>Shipping Adjust Reason:</strong> {{ $estimate->drop_sell_adjust_reason }}</div>
        @endif
    </div>
</div>