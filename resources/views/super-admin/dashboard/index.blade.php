@extends('layouts.app')

@push('styles')
    <script src="{{ asset('vendor/jquery/frappe-charts.min.iife.js') }}"></script>
@endpush

@section('content')

    <!-- CONTENT WRAPPER START -->
    <div class="px-4 py-0 py-lg-4 border-top-0 super-admin-dashboard">
        <div class="row">
            @include('dashboard.update-message-dashboard')
            @includeIf('dashboard.update-message-module-dashboard')
            <x-cron-message :modal="true"></x-cron-message>
        </div>

        @if(user()->permission('view_companies'))
            <div class="row">
                @if($sidebarSuperadminPermissions['view_companies'] != 5 && $sidebarSuperadminPermissions['view_companies'] != 'none')
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <x-cards.widget :title="__('superadmin.dashboard.totalCompany')" :value="$totalCompanies"
                                        icon="building"/>
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <x-cards.widget :title="__('superadmin.dashboard.activeCompany')" :value="$activeCompanies"
                                        icon="store"/>
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <x-cards.widget :title="__('superadmin.dashboard.licenseExpired')"
                                        :value="$expiredCompanies"
                                        icon="ban"/>
                    </div>
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <x-cards.widget :title="__('superadmin.dashboard.inactiveCompany')"
                                        :value="$inactiveCompanies"
                                        icon="store-slash"/>
                    </div>
                @endif
                @if($sidebarSuperadminPermissions['view_packages'] != 5 && $sidebarSuperadminPermissions['view_packages'] != 'none')
                    <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                        <x-cards.widget :title="__('superadmin.dashboard.totalPackages')"
                                        :value="$totalPackages"
                                        icon="boxes"/>
                    </div>
                @endif
            </div>

            @if($sidebarSuperadminPermissions['manage_billing'] != 5 && $sidebarSuperadminPermissions['manage_billing'] != 'none')
                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">@lang('superadmin.dashboard.earningReports')</h5>
                                <span class="badge badge-light text-uppercase">@lang('app.total')</span>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-4">
                                    <div class="col-sm-4 mb-3 mb-sm-0">
                                        <h4 class="mb-0">{{ global_currency_format($earningTotals['all_time'], $dashboardCurrencyId) }}</h4>
                                        <small class="text-muted text-uppercase">@lang('superadmin.dashboard.totalEarnings')</small>
                                    </div>
                                    <div class="col-sm-4 mb-3 mb-sm-0">
                                        <h4 class="mb-0">{{ global_currency_format($earningTotals['current_year'], $dashboardCurrencyId) }}</h4>
                                        <small class="text-muted text-uppercase">@lang('superadmin.dashboard.earningsThisYear')</small>
                                    </div>
                                    <div class="col-sm-4">
                                        <h4 class="mb-0">{{ global_currency_format($earningTotals['current_month'], $dashboardCurrencyId) }}</h4>
                                        <small class="text-muted text-uppercase">@lang('superadmin.dashboard.earningsThisMonth')</small>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('app.month')</th>
                                            <th class="text-right">@lang('superadmin.dashboard.monthlyIncome')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($monthlyEarningsReport as $earningRow)
                                            <tr class="{{ $earningRow['highlight'] ? 'table-success font-weight-bold' : '' }}" @if($earningRow['highlight']) data-toggle="tooltip" title="{{ $earningRow['tooltip'] }}" @endif>
                                                <td>{{ $earningRow['label'] }}</td>
                                                <td class="text-right">{{ global_currency_format($earningRow['total'], $dashboardCurrencyId) }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-6 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('superadmin.dashboard.subscriptionReports')</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center mb-4">
                                    <div class="col-sm-6 mb-3 mb-sm-0">
                                        <h4 class="mb-0">{{ $subscriptionTotals['active'] }}</h4>
                                        <small class="text-muted text-uppercase">@lang('superadmin.dashboard.activeSubscriptions')</small>
                                    </div>
                                    <div class="col-sm-6">
                                        <h4 class="mb-0">{{ $subscriptionTotals['new_this_month'] }}</h4>
                                        <small class="text-muted text-uppercase">@lang('superadmin.dashboard.newSubscriptionsThisMonth')</small>
                                    </div>
                                </div>

                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('app.month')</th>
                                            <th class="text-right">@lang('superadmin.dashboard.monthlySubscriptions')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($monthlySubscriptionReport as $subscriptionRow)
                                            <tr class="{{ $subscriptionRow['highlight'] ? 'table-success font-weight-bold' : '' }}" @if($subscriptionRow['highlight']) data-toggle="tooltip" title="{{ $subscriptionRow['tooltip'] }}" @endif>
                                                <td>{{ $subscriptionRow['label'] }}</td>
                                                <td class="text-right">{{ $subscriptionRow['total'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-6 col-lg-6 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('superadmin.dashboard.topPayingCompanies')</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('app.name')</th>
                                            <th class="text-right">@lang('app.amount')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($topPayingCompanies as $company)
                                            <tr>
                                                <td>
                                                    @if($company->company)
                                                        <x-company :company="$company->company" />
                                                    @else
                                                        <span class="text-muted">@lang('app.na')</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">{{ global_currency_format($company->total, $dashboardCurrencyId) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">@lang('messages.noRecordFound')</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6 col-lg-6 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('superadmin.dashboard.paymentGatewayBreakdown')</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('modules.payments.paymentGateway')</th>
                                            <th class="text-right">@lang('app.amount')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($gatewayBreakdown as $gateway)
                                            <tr>
                                                <td class="text-capitalize">{{ $gateway['gateway'] }}</td>
                                                <td class="text-right">{{ global_currency_format($gateway['total'], $dashboardCurrencyId) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">@lang('messages.noRecordFound')</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-xl-4 col-lg-6 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('superadmin.dashboard.upcomingRenewals')</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('superadmin.company')</th>
                                            <th>@lang('superadmin.dashboard.nextChargeDate')</th>
                                            <th class="text-right">@lang('app.amount')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($upcomingRenewals as $renewal)
                                            <tr>
                                                <td>
                                                    @if($renewal->company)
                                                        <x-company :company="$renewal->company" />
                                                    @else
                                                        <span class="text-muted">@lang('app.na')</span>
                                                    @endif
                                                </td>
                                                <td>{{ optional($renewal->next_pay_date)->timezone(global_setting()->timezone)->translatedFormat(global_setting()->date_format) ?? '--' }}</td>
                                                <td class="text-right">{{ global_currency_format($renewal->total, $renewal->currency_id ?? $dashboardCurrencyId) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">@lang('messages.noRecordFound')</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-6 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('superadmin.dashboard.outstandingInvoices')</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('app.company_name')</th>
                                            <th>@lang('superadmin.dashboard.dueDate')</th>
                                            <th>@lang('app.status')</th>
                                            <th class="text-right">@lang('superadmin.dashboard.amountDue')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($outstandingInvoices as $invoice)
                                            @php
                                                $dueDate = $invoice->next_pay_date ?: $invoice->created_at;
                                            @endphp
                                            <tr>
                                                <td>
                                                    @if($invoice->company)
                                                        <x-company :company="$invoice->company" />
                                                    @else
                                                        <span class="text-muted">@lang('app.na')</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    {{ $dueDate ? $dueDate->timezone(global_setting()->timezone)->translatedFormat(global_setting()->date_format) : '--' }}
                                                </td>
                                                <td class="text-capitalize">{{ $invoice->status ? str_replace('_', ' ', $invoice->status) : __('app.na') }}</td>
                                                <td class="text-right">{{ global_currency_format($invoice->total ?? 0, $invoice->currency_id ?? $dashboardCurrencyId) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">@lang('messages.noRecordFound')</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-12 col-md-12 mt-4">
                        <div class="card h-100">
                            <div class="card-header">
                                <h5 class="mb-0">@lang('superadmin.dashboard.expiringSubscriptions')</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                    <table class="table table-sm table-striped table-borderless mb-0">
                                        <thead>
                                        <tr>
                                            <th>@lang('app.company_name')</th>
                                            <th>@lang('app.package')</th>
                                            <th>@lang('superadmin.dashboard.expiryDate')</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($expiringSubscriptions as $expiring)
                                            <tr>
                                                <td>
                                                    <x-company :company="$expiring" />
                                                </td>
                                                <td>{{ optional($expiring->package)->name ?? __('app.na') }}</td>
                                                <td>{{ optional($expiring->licence_expire_on)->timezone(global_setting()->timezone)->translatedFormat(global_setting()->date_format) ?? '--' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">@lang('messages.noRecordFound')</td>
                                            </tr>
                                        @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row">
                @if($sidebarSuperadminPermissions['view_companies'] != 5 && $sidebarSuperadminPermissions['view_companies'] != 'none')
                    <div class="col-sm-12 col-lg-6 mt-4">
                        @include('super-admin.dashboard.recent-registered-companies')
                    </div>
                    <div class="col-sm-12 col-lg-6 mt-4">
                        @include('super-admin.dashboard.top-user-count-companies')
                    </div>
                @endif
                @if($sidebarSuperadminPermissions['manage_billing'] != 5 && $sidebarSuperadminPermissions['manage_billing'] != 'none')
                    <div class="col-sm-12 col-lg-6 mt-4">
                        @include('super-admin.dashboard.recent-subscriptions')
                    </div>
                    <div class="col-sm-12 col-lg-6 mt-4">
                        @include('super-admin.dashboard.recent-license-expired')
                    </div>
                @endif
                @if($sidebarSuperadminPermissions['view_packages'] != 5 && $sidebarSuperadminPermissions['view_packages'] != 'none')
                    <div class="col-sm-12 col-lg-6 mt-4">
                        @include('super-admin.dashboard.package-company-count')
                    </div>
                @endif
                @if($sidebarSuperadminPermissions['view_companies'] != 5 && $sidebarSuperadminPermissions['view_companies'] != 'none')
                    <div class="col-sm-12 col-lg-6 mt-4">
                        @include('super-admin.dashboard.charts')
                    </div>
                @endif
            </div>
        @endif
    </div>
    <!-- CONTENT WRAPPER END -->
@endsection

@push('scripts')

    <script>
        $('#registration_year').change(function () {
            const year = $(this).val();

            let url = `{{ route('superadmin.super_admin_dashboard') }}`;
            const string = `?year=${year}`;
            url += string;

            window.location.href = url;
        });

        $('body').tooltip({
            selector: '[data-toggle="tooltip"]'
        });
    </script>

@endpush
