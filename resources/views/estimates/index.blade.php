<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Estimates') }}
            </h2>
            <form method="GET">
                <input name="search" type="text" placeholder="Find Estimate" id="find-estimate" class="hidden" />
                <button type="submit"
                    class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150"
                    id="btn-find-estimate"
                >
                    Find Estimate
                </button> 
                <a class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 no-underline"
                    href="{{ route('estimate.create') }}"
                >New Estimate</a>
            </form>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="lg:px-8 xl:w-full xl:mx-4 2xl:max-w-[80%] 2xl:mx-auto">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg py-4 px-6">
                @if (!empty($search_term))
                    <div class="text-2xl mb-4">Searching for: <span class="font-semibold>"{{ $search_term }}</span></div>
                @endif
                <table id="primary-estimate-list" class="w-full" cellpadding="4" cellspacing="4">
                    <thead>
                        <tr class="bg-gray-800 text-white">
                            <th class="text-left py-2 pl-4">Estimate&nbsp;#</th>
                            <th class="text-left">Job Number</th>
                            <th class="text-left">Job Name</th>
                            <th style="width: 100px;" class="text-left">Mail Date</th>
                            <th class="text-left" style="width: 140px;"># of Documents</th>
                            <th class="text-left">Files Required By</th>
                            <th style="max-width: 120px;" class="text-left">Alerts</th>
                            <th class="text-left">Created/Last Modified By</th>
                            <th style="width: 152px;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estimates as $estimate)
                            @if (!empty($estimate->client_reference_description))
                            <tr data-estimate-id="{{$estimate->id}}" class="estimate-row {{$estimate->is_archived ? 'estimate-archived' : ''}}">
                                <td class="id py-2 pl-4"><a href="{{route('estimate.show', $estimate->id)}}">{{$estimate->id}}</a></td>
                                <td class="client_reference_number"><a href="{{route('estimate.show', $estimate->id)}}">
                                    {{$estimate->client_reference_number}}</a>{!! $estimate->is_print_order ? '<span class="inline-block ml-2 text-orange-600 font-semibold">Print Order</span>' : '' !!}
                                </td>
                                <td class="client_reference_description">{{$estimate->client_reference_description}}</td>
                                <td class="client_reference_mail_date">{{$estimate->client_reference_mail_date}}</td>
                                <td class="document_count text-center">{{$estimate->documents->count()}}</td>
                                <td class=""><span class="files-required-by">
                                    @if ($estimate->files_require_adjustment && substr($estimate->files_require_adjustment, 0, 4) !== '0000')
                                        {{ $estimate->files_require_adjustment }}
                                    @else
                                        {{ $estimate->files_required_by ?? '–' }}
                                    @endif
                                </span></td>
                                <td class="alerts-cell">
                                    <div class="alerts-list" style="max-width: 120px; font-size: 12px; color: {{ $estimate->has_alert ? 'red' : '#999' }}">
                                    @if ($estimate->has_alert)
                                        <div>
                                        @foreach($estimate->getAlerts() as $alert)
                                            {{ $alert }}<br />
                                        @endforeach
                                        </div>
                                    @else
                                        –
                                    @endif
                                </div>
                                </td>
                                <td class="__last_modified_by_resolved">LAST_MODIFIED_BY</td>
                                <td class="actions" style="padding-right: 16px; font-size: 86%; padding-top: 9px; width: 170px;">
                                    @if (!auth()->user()->isReadOnly())
                                        <a class="shipping-link inline-block mr-4" href="#">Shipping</a>
                                        <a class="btn-archive-estimate" href="#archive-{{ $estimate->id }}">{{$estimate->is_archived ? 'Unarchive' : 'Archive'}}</a>
                                    @else
                                        &nbsp;
                                    @endif
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">
                    {{ $estimates->links() }}
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="/js/estimate-archive-lightbox.js"></script>
    <script type="text/javascript">
        $('#btn-find-estimate').click(function(event) {
            var $findEstimate = $('#find-estimate');
            if ($findEstimate.is(':visible')) {
                return;
            }
            event.preventDefault();
            $findEstimate.show();
            setTimeout(function() { 
                $findEstimate.focus() 
            }, 100);
        });

        // Convert database timestamps into human-readable dates
        $('.files-required-by').text(function(i, sqlDate) {
            return filesRequiredDisplayDate(sqlDate);
        });

        function filesRequiredDisplayDate(sqlDate) {
            sqlDate = _.trim(sqlDate);
            if (!/^[2-9]+/.test(sqlDate)) return '–';
            var dateText = moment(sqlDate).format('MMM D, YYYY - h:mma');
            return (dateText != 'Invalid date') ? dateText : '–';
        }

        $.estimateArchiveFeatherlight('#primary-estimate-list', '.btn-archive-estimate', function(event, $btn) {
            event.preventDefault();

            var $estimateRow = $btn.closest('tr');
            var estimateId = $estimateRow.data('estimate-id');
            var estimateIsArchived = $estimateRow.is('.estimate-archived');

            // Unarchive/reactivate
            if (estimateIsArchived) {
                $btn.text('Archive');
                $estimateRow.removeClass('estimate-archived');
                setEstimateArchiveState(estimateId, false, 'active');
                return false;
            }

            return {
                beforeClose: function(params) {
                    var feather = this;

                    if (params.action === 'cancel') {
                        return;
                    }
                    else if (params.action === 'submit') {
                        $estimateRow.addClass('estimate-archived');
                        $btn.text('Unarchive');
                        setEstimateArchiveState(estimateId, true, params.status);
                    }
                }
            };
        });

        function setEstimateArchiveState(estimateId, estimateIsArchived, status) {
            var estimateUrl = location.origin + '/estimates/' + estimateId;
            var qs = 'is_archived=' + (estimateIsArchived ? 1 : 0);
            qs += '&status=' + status;

            return $.ajax({
                method: 'POST',
                url: estimateUrl + '/set_archive_state?' + qs,
            done: function() {
                console.log(arguments);
            },
            error: function() {
                console.error(arguments);
            }
            });
        }
    </script>
</x-app-layout>
