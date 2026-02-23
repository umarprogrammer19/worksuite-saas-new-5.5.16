<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\AccountBaseController;
use App\Models\Company;
use App\Models\SuperAdmin\GlobalInvoice;
use App\Models\SuperAdmin\GlobalSubscription;
use App\Models\SuperAdmin\Package;
use App\Scopes\ActiveScope;
use App\Traits\CurrencyExchange;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends AccountBaseController
{

    use AppBoot, CurrencyExchange;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.dashboard';
    }

    public function index()
    {

        $this->isCheckScript();

        return $this->superAdminDashboard();
    }

    public function checklist()
    {
        $this->isCheckScript();

        return view('super-admin.dashboard.checklist', $this->data);
    }

    public function superAdminDashboard()
    {
        $this->pageTitle = 'superadmin.superAdminDashboard';

        $select = ['id', 'company_name', 'package_type', 'created_at', 'package_id', 'logo', 'light_logo'];

        if (module_enabled('Subdomain')) {
            $select[] = 'sub_domain';
        }

        $this->recentRegisteredCompanies = Company::with('package:id,name')
            ->select($select)
            ->latest()
            ->limit(5)
            ->get();
        $this->recentSubscriptions = Company::with('package')->where('status', 'active')->whereNotNull('subscription_updated_at')->latest('subscription_updated_at')->limit(5)->get();
        $this->topCompaniesUserCount = Company::active()->select($select)->withCount(['users', 'employees', 'clients'])->orderBy('users_count', 'desc')->limit(5)->get();

        $this->recentLicenceExpiredCompanies = Company::with('package')
            ->where('status', 'license_expired')
            ->where(function ($query) {
                $query->where('licence_expire_on', '<', now()->format('Y-m-d'))
                    ->orWhere('licence_expire_on', '=', null);
            })
            ->latest('license_updated_at')
            ->limit(5)
            ->get();

        $companyStats = Company::withoutGlobalScope(ActiveScope::class)
            ->selectRaw('COUNT(*) as totalCompanies')
            ->selectRaw('SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as activeCompanies')
            ->selectRaw('SUM(CASE WHEN status = "inactive" THEN 1 ELSE 0 END) as inactiveCompanies')
            ->selectRaw('SUM(CASE WHEN status = "license_expired" THEN 1 ELSE 0 END) as expiredCompanies')
            ->selectRaw('logo')
            ->first();

        $this->totalCompanies = $companyStats->totalCompanies;
        $this->activeCompanies = $companyStats->activeCompanies;
        $this->inactiveCompanies = $companyStats->inactiveCompanies;
        $this->expiredCompanies = $companyStats->expiredCompanies;



        $this->packageCompanyCount = Package::where('default', '!=', 'trial')->withCount(['companies'])->orderBy('companies_count', 'desc')->limit(10)->get();
        $this->totalPackages = Package::where('default', '!=', 'trial')->count();
        $timezone = global_setting()->timezone;
        $now = Carbon::now($timezone);
        $year = $now->year;

        if (request()->year != '') {
            $year = request()->year;
        }

        $this->registrationsChart = $this->registrationsChart($year);

        $this->prepareEarningReports($now);

        return view('super-admin.dashboard.index', $this->data);
    }

    private function prepareEarningReports(Carbon $now): void
    {
        $endDate = $now->copy()->endOfMonth();

        $baseInvoiceQuery = GlobalInvoice::query();

        $paidInvoices = (clone $baseInvoiceQuery)->paid()->whereNotNull('pay_date');

        $firstInvoiceDate = (clone $paidInvoices)->min('pay_date');

        $startDate = $firstInvoiceDate ? Carbon::parse($firstInvoiceDate)->startOfMonth() : $now->copy()->startOfMonth();

        $activeSubscriptions = GlobalSubscription::active();
        $firstSubscriptionDate = (clone $activeSubscriptions)
            ->selectRaw('MIN(COALESCE(subscribed_on_date, created_at)) as first_date')
            ->value('first_date');

        if ($firstSubscriptionDate) {
            $subscriptionStart = Carbon::parse($firstSubscriptionDate)->startOfMonth();
            if ($subscriptionStart->lt($startDate)) {
                $startDate = $subscriptionStart;
            }
        }

        $monthlyInvoiceTotals = (clone $paidInvoices)
            ->selectRaw('YEAR(pay_date) as year, MONTH(pay_date) as month, SUM(total) as total')
            ->whereBetween('pay_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [$row->year . '-' . $row->month => (float)$row->total]);

        $maxMonthlyEarning = $monthlyInvoiceTotals->max() ?? 0;

        $months = collect();
        $cursor = $endDate->copy()->startOfMonth();

        while ($cursor->gte($startDate)) {
            $months->push($cursor->copy());
            $cursor->subMonth();
        }

        $this->monthlyEarningsReport = $months->map(function (Carbon $date) use ($monthlyInvoiceTotals, $maxMonthlyEarning) {
            $key = $date->year . '-' . $date->month;
            $total = round($monthlyInvoiceTotals[$key] ?? 0, 2);

            return [
                'label' => $date->translatedFormat('F Y'),
                'total' => $total,
                'highlight' => $maxMonthlyEarning > 0 && abs($total - $maxMonthlyEarning) < 0.01,
                'tooltip' => $maxMonthlyEarning > 0 && abs($total - $maxMonthlyEarning) < 0.01 ? __('superadmin.dashboard.highestEarningMonth') : null,
            ];
        });

        $this->earningTotals = [
            'all_time' => GlobalInvoice::paid()->sum('total'),
            'current_year' => GlobalInvoice::paid()->whereYear('pay_date', $now->year)->sum('total'),
            'current_month' => GlobalInvoice::paid()->whereYear('pay_date', $now->year)->whereMonth('pay_date', $now->month)->sum('total'),
        ];

        $subscriptionDateRange = [$startDate->toDateString(), $endDate->toDateString()];

        $monthlySubscriptionTotals = (clone $activeSubscriptions)
            ->selectRaw('YEAR(COALESCE(subscribed_on_date, created_at)) as year, MONTH(COALESCE(subscribed_on_date, created_at)) as month, COUNT(id) as total')
            ->whereRaw('COALESCE(subscribed_on_date, created_at) BETWEEN ? AND ?', $subscriptionDateRange)
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(fn($row) => [$row->year . '-' . $row->month => (int)$row->total]);

        $maxMonthlySubscriptions = $monthlySubscriptionTotals->max() ?? 0;

        $this->monthlySubscriptionReport = $months->map(function (Carbon $date) use ($monthlySubscriptionTotals, $maxMonthlySubscriptions) {
            $key = $date->year . '-' . $date->month;
            $total = $monthlySubscriptionTotals[$key] ?? 0;

            return [
                'label' => $date->translatedFormat('F Y'),
                'total' => $total,
                'highlight' => $maxMonthlySubscriptions > 0 && $total === $maxMonthlySubscriptions,
                'tooltip' => $maxMonthlySubscriptions > 0 && $total === $maxMonthlySubscriptions ? __('superadmin.dashboard.highestSubscriptionMonth') : null,
            ];
        });

        $this->subscriptionTotals = [
            'active' => (clone $activeSubscriptions)->count(),
            'new_this_month' => GlobalSubscription::active()
                ->whereRaw('COALESCE(subscribed_on_date, created_at) BETWEEN ? AND ?', [$now->copy()->startOfMonth()->toDateString(), $endDate->toDateString()])
                ->count(),
        ];

        $topCompanies = (clone $paidInvoices)
            ->select('company_id', DB::raw('SUM(total) as total'))
            ->whereNotNull('company_id')
            ->groupBy('company_id')
            ->orderByDesc('total')
            ->with('company:id,company_name')
            ->limit(10)
            ->get();

        $this->topPayingCompanies = $topCompanies->map(function ($row) {
            return (object) [
                'company' => $row->company,
                'total' => (float)$row->total,
            ];
        });

        $gatewayTotals = (clone $paidInvoices)
            ->select('gateway_name', DB::raw('SUM(total) as total'))
            ->groupBy('gateway_name')
            ->orderByDesc('total')
            ->get();

        $this->gatewayBreakdown = $gatewayTotals->map(function ($row) {
            return [
                'gateway' => $row->gateway_name ?: __('app.na'),
                'total' => (float)$row->total,
            ];
        });

        $this->dashboardCurrencyId = companyOrGlobalSetting()->currency_id;

        $renewalWindowEnd = $now->copy()->addDays(30)->endOfDay();

        $this->upcomingRenewals = (clone $paidInvoices)
            ->whereNotNull('next_pay_date')
            ->whereBetween('next_pay_date', [$now->copy()->startOfDay()->toDateString(), $renewalWindowEnd->toDateString()])
            ->with('company:id,company_name')
            ->orderBy('next_pay_date')
            ->limit(10)
            ->get();

        $this->outstandingInvoices = (clone $baseInvoiceQuery)
            ->where(function ($query) {
                $query->whereNull('pay_date')
                    ->orWhere('status', '!=', 'active');
            })
            ->whereBetween(DB::raw('COALESCE(next_pay_date, created_at)'), [$now->copy()->startOfDay()->toDateString(), $renewalWindowEnd->toDateString()])
            ->with(['company:id,company_name', 'currency:id,currency_symbol'])
            ->orderByRaw('COALESCE(next_pay_date, created_at) asc')
            ->limit(10)
            ->get();

        $this->expiringSubscriptions = Company::withoutGlobalScope(ActiveScope::class)
            ->select('id', 'company_name', 'logo', 'light_logo', 'licence_expire_on', 'package_id', 'package_type', 'status')
            ->whereNotNull('licence_expire_on')
            ->whereBetween('licence_expire_on', [$now->copy()->startOfDay()->toDateString(), $renewalWindowEnd->toDateString()])
            ->with('package:id,name')
            ->orderBy('licence_expire_on')
            ->limit(10)
            ->get();
    }

    public function registrationsChart($year): array
    {
        $companies = Company::whereYear('created_at', $year)->orderBy('created_at');
        $companies = $companies->groupBy('year', 'month')
            ->get([
                DB::raw('YEAR(created_at) year, MONTHNAME(created_at) month,MONTH(created_at) month_number'),
                DB::raw('count(id) as total')
            ]);

        $data['labels'] = $this->convertMonthToName($companies->pluck('month_number')->toArray());
        $data['values'] = $companies->pluck('total')->toArray();
        $data['colors'] = [$this->appTheme->header_color];
        $data['name'] = __('superadmin.dashboard.registrationsChart');

        return $data;
    }

    private function convertMonthToName($toArray): array
    {
        $labels = [];
        foreach ($toArray as $month) {
            $labels[] = now()->month($month)->translatedFormat('F');
        }

        return $labels;
    }
}
