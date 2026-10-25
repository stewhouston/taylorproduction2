<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Estimates') }}
            </h2>
            <div>
                <input type="text" placeholder="Find Estimate" id="find-estimate" class="hidden" />
                <button class="bg-gray-100 hover:bg-gray-200 active:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-md border border-gray-300 transition-colors duration-150"
                    id="btn-find-estimate"
                >
                    Find Estimate
                </button> 
                <a class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md border border-gray-600 transition-colors duration-150 no-underline"
                    href="{{ route('estimates.create') }}"
                >New Estimate</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="px-8 max-w-[80%] mx-auto">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg py-4 px-6">
                <table id="primary-estimate-list" class="w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Estimate&nbsp;#</th>
                            <th class="text-left">Job Number</th>
                            <th class="text-left">Job Name</th>
                            <th style="width: 100px;" class="text-left">Mail Date</th>
                            <th class="text-left"># of Documents</th>
                            <th class="text-left">Files Required By</th>
                            <th style="max-width: 120px;" class="text-left">Alerts</th>
                            <th class="text-left">Created/Last Modified By</th>
                            <th style="width: 152px;">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($estimates as $estimate)
                            <tr data-estimate-id="{{$estimate->id}}" class="estimate-row {{$estimate->is_archived ? 'estimate-archived' : ''}}">
                                <td class="id"><a href="{{route('estimate.show', $estimate->id)}}">{{$estimate->id}}</a></td>
                                <td class="client_reference_number"><a href="{{route('estimate.show', $estimate->id)}}">{{$estimate->client_reference_number}}</a>{!! $estimate->is_print_order ? '<span class="inline-block ml-2 text-orange-600 bold">Print Order</span>' : '' !!}</td>
                                <td class="client_reference_description">{{$estimate->client_reference_description}}</td>
                                <td class="client_reference_mail_date">{{$estimate->client_reference_mail_date}}</td>
                                <td class="document_count" style="text-align: center; width: 120px;">COUNT</td>
                                <td class=""><span class="files-required-by">
                                    @if ($estimate->files_require_adjustment && substr($estimate->files_require_adjustment, 0, 4) !== '0000')
                                        {{ $estimate->files_require_adjustment }}
                                    @else
                                        {{ $estimate->files_required_by ?? '–' }}
                                    @endif
                                </span></td>
                                <td class="alerts-cell">
                                    ALERTS
                                </td>
                                <td class="__last_modified_by_resolved">LAST_MODIFIED_BY</td>
                                <td class="actions" style="padding-right: 16px; font-size: 86%; padding-top: 9px;">
                                    SHIPPING
                                </td>
                            </tr>
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
        $('#btn-find-estimate').click(function() {
            var $findEstimate = $('#find-estimate');
            if ($findEstimate.is(':visible')) {
                alert('find estimate');
            }
            $findEstimate.show();
            setTimeout(function() { 
                $findEstimate.focus() 
            }, 100);
        });

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
    </script>
</x-app-layout>
