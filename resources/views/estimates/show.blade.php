<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <div>
                <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
                    {{ __('Estimate') . ' #' . $estimate->id }} (COMPANY_NAME)
                </h2>
                <div class="text-lg">
                    <span class="font-semibold">Status:</span> <span class="italic">{{ !empty($estimate->status) ? ucfirst(str_replace('_', ' ', $estimate->status)) : 'Active' }}</span>
                </div>
            </div>
            <div>
                <button
                    class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150"
                >Mark as Print Order</button>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="px-8 xl:w-full xl:mx-4 2xl:max-w-[80%] 2xl:mx-auto">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg py-4 px-6">
                @if (!auth()->user()->isReadOnly())
                    <section class="content-header">
                        <ul id="taylor-button-nav-bar" class="nav-horizontal text-center {{ $estimate->is_print_order ? 'nav-as-print-order' : '' }} {{ $estimate->has_alert_disable_interact ? 'nav-has-alert-disable-interact' : '' }}">
                        <li style="float: left; pointer-events: all; opacity: 1; filter: saturate(1)">
                            <a href="{{route('estimates.index')}}{{ $estimate->is_archived ? '?archives=1' : '' }}">
                            <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </li>

                        <li class="{{ $estimate->has_rush_fee ? 'active' : '' }}">
                            <a href="#" id="toggle-rush-fee"><i class="fa fa-bolt"></i> Rush Fee</a>
                        </li>
                        <li>
                            <a href="{{route('estimate.edit', $estimate->id)}}"><i class="fa fa-edit"></i> Edit Estimate</a>
                        </li>
                        <li>
                            <a id="btn-delete-estimate" href="{{route('estimate.destroy', $estimate->id)}}"><i class="fa fa-remove"></i> Delete</a>
                        </li>
                        <!--<li>
                            <a href="#" id="est-button-duplicate"><i class="fa fa-copy"></i> Duplicate</a>
                        </li>-->
                        <li class="{{ $estimate->is_archived ? 'active' : '' }}">
                            <a href="#" id="btn-archive-estimate"><i class="fa fa-archive"></i> Archive</a>
                        </li>
                        <li>
                            <a style="cursor: pointer;" onclick="window.open('{{ route("estimate.print", $estimate->id) }}','name','width=800,height=920'); return false;"><i class="fa fa-print"></i> Print</a>
                        </li>
                        <li>
                            <a href="{{route('shipping.drops', $estimate->id)}}" id="shipping-button">
                            <i class="fa fa-truck"></i> Shipping
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" data-toggle="control-sidebar" id="add-document-button">
                            <i class="fa fa-book"></i> Add Document
                            </a>
                        </li>
                        </ul>
                    </section>
                @endif

                <div class="border-t border-solid border-gray-300 mt-4 pt-4">
                    <div class="flex justify-between">
                        <div>
                            <div><span class="font-semibold">Number:</span> <span>{{$estimate->client_reference_number}}</span></div>
                            <div><span class="font-semibold">Description:</span> <span>{{$estimate->client_reference_description}}</span></div>
                            <div><span class="font-semibold">Mail Date:</span> <span id="mail-date-text-value">{{$estimate->client_reference_mail_date}}</span></div>
                            <div><span class="font-semibold">Files Required By:</span> <span id="files-required-by">—</span></div>
                            <div>
                            @if ($estimate->file_require_adjust_reason)
                                <div style="color: #666; font-style: italic; font-size: 13px; margin-bottom: 4px;">
                                    {{ $estimate->file_require_adjust_reason }}
                                </div>
                                @else
                                <div style="color: #666; font-style: italic; font-size: 13px; margin-bottom: 4px;">
                                    Rush fee applies to files arriving after file required date time.
                                </div>
                            @endif
                            </div>
                        </div>
                        <div>
                            <div><span class="font-semibold">Created By:</span> todo</div>
                            <div><span class="font-semibold">Estimate Last Modified By:</span> todo</div>
                            <div><span class="font-semibold">Shipping Last Modified By:</span> todo</div>
                            <div><span class="font-semibold">Adjustments Last Modified By:</span> todo</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        $('#mail-date-text-value').text(function(i, basicDate) {
            if (!basicDate.trim() || basicDate == '--') {
            return '--';
            }
            return moment(basicDate, 'YYYY-MM-DD').format('MMM D, YYYY');
        });

        $('#files-required-by').text(function(i, sqlDate) {
            sqlDate = sqlDate.trim();

            if (!sqlDate || sqlDate == '—') {
                var msg = sqlDate;
                $(this).css('color', '#999');
                return msg || 'No drops created';
            }
            else {
                $(this).css('color', '#c32a07');
                return moment(sqlDate).format('MMM D, YYYY - h:mma');
            }
        });

        $('#btn-delete-estimate').click(function(event) {
            if (!confirm('Are you absolutely sure you want to delete this estimate?')) {
                return;
            }
            $('#formDeleteEstimate').submit();
        });
    </script>

    @section('styles')
    @parent
    <link rel="stylesheet" href="/css/estimate-show.css" />
    <link rel="stylesheet" href="/css/jquery.jsonview.css" />
    @endsection

    @section('scripts')
    <script type="text/javscript" src="/js/jquery.jsonview.min.js"></script>
    @parent
        
    @endsection
</x-app-layout>