<?php

namespace Modules\Dashboard\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Contract\App\Models\Contract;
use Modules\Invoice\App\Models\Invoice;
use Modules\Payment\App\Models\Payment;
use Modules\Guarantee\App\Models\Guarantee;
use Modules\Amendment\App\Models\Amendment;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * آمار کلی قراردادها
     */
    public function contractStats(): JsonResponse
    {
        $stats = [
            'total' => Contract::count(),
            'active' => Contract::where('status', 'active')->count(),
            'expiring' => Contract::expiringSoon(30)->count(),
            'total_amount' => Contract::where('status', 'active')->sum('current_amount'),
            'upstream' => Contract::where('type', 'upstream')->count(),
            'downstream' => Contract::where('type', 'downstream')->count(),
            'total_guarantees' => Guarantee::where('status', 'active')->count(),
            'expiring_guarantees' => Guarantee::expiringSoon(30)->count(),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * آمار صورت‌وضعیت‌ها
     */
    public function invoiceStats(): JsonResponse
    {
        $stats = [
            'pending' => Invoice::where('status', 'submitted')->count(),
            'approved' => Invoice::where('status', 'approved')->count(),
            'paid' => Invoice::where('status', 'paid')->count(),
            'pending_amount' => Invoice::where('status', 'submitted')->sum('net_amount'),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * نمودار وضعیت قراردادها
     */
    public function contractStatusChart(): JsonResponse
    {
        $data = Contract::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $labels = [
            'draft' => 'پیش‌نویس',
            'active' => 'فعال',
            'expiring' => 'در حال اتمام',
            'completed' => 'خاتمه یافته',
            'terminated' => 'فسخ',
        ];

        return response()->json([
            'data' => [
                'labels' => array_map(fn($s) => $labels[$s] ?? $s, array_keys($data->toArray())),
                'values' => array_values($data->toArray()),
            ]
        ]);
    }

    /**
     * نمودار مبالغ قراردادها
     */
    public function contractAmountChart($period = 'monthly'): JsonResponse
    {
        // پیاده‌سازی بر اساس period (monthly/quarterly/yearly)
        // این یک نمونه ساده است
        $months = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور'];

        return response()->json([
            'data' => [
                'labels' => $months,
                'new_contracts' => [10, 15, 12, 18, 20, 25],
                'payments' => [8, 12, 10, 15, 18, 22],
            ]
        ]);
    }

    /**
     * قراردادهای در حال اتمام
     */
    public function expiringContracts($days = 30): JsonResponse
    {
        $contracts = Contract::expiringSoon($days)
            ->with(['party'])
            ->orderBy('end_date')
            ->limit(10)
            ->get();

        return response()->json(['data' => $contracts]);
    }

    /**
     * ضمانت‌نامه‌های در حال اتمام
     */
    public function expiringGuarantees($days = 30): JsonResponse
    {
        $guarantees = Guarantee::expiringSoon($days)
            ->with(['contract'])
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        return response()->json(['data' => $guarantees]);
    }

    /**
     * داشبورد پیمانکار
     */
    public function contractorDashboard(): JsonResponse
    {
        $user = auth()->user();

        if (!$user->party || $user->party->role !== 'contractor') {
            return response()->json(['data' => []]);
        }

        $contracts = $user->party->contracts()
            ->with(['amendments', 'invoices', 'guarantees', 'signatures'])
            ->get();

        $stats = [
            'activeContracts' => $contracts->where('status', 'active')->count(),
            'pendingSignatures' => $contracts->flatMap->signatures->where('status', 'pending')->where('signer_id', $user->id)->count(),
            'totalAmount' => $contracts->sum('current_amount'),
            'expiringContracts' => $contracts->filter->isExpiring()->count(),
            'paidAmount' => $contracts->flatMap->invoices->where('status', 'paid')->sum('net_amount'),
            'unpaidAmount' => $contracts->flatMap->invoices->whereIn('status', ['approved', 'submitted'])->sum('net_amount'),
            'contracts' => $contracts->take(5),
            'pendingSignaturesList' => $contracts->flatMap->signatures->where('status', 'pending')->where('signer_id', $user->id)->take(5),
            'pendingInvoices' => $contracts->flatMap->invoices->whereIn('status', ['submitted', 'approved'])->take(5),
            'expiringGuarantees' => $contracts->flatMap->guarantees->filter->isExpiring()->take(5),
        ];

        return response()->json(['data' => $stats]);
    }

    /**
     * فعالیت‌های اخیر
     */
    public function recentActivities($limit = 10): JsonResponse
    {
        // ترکیب فعالیت‌ها از مدل‌های مختلف
        $activities = collect();

        // قراردادهای جدید
        Contract::latest()->limit($limit)->get()->each(function ($c) use (&$activities) {
            $activities->push([
                'title' => "قرارداد {$c->contract_number} ایجاد شد",
                'date' => $c->created_at,
                'icon' => 'pi pi-file-plus',
                'color' => 'blue',
            ]);
        });

        // الحاقیه‌ها
        Amendment::latest()->limit($limit)->get()->each(function ($a) use (&$activities) {
            $activities->push([
                'title' => "الحاقیه {$a->amendment_number} ثبت شد",
                'date' => $a->created_at,
                'icon' => 'pi pi-file-edit',
                'color' => 'orange',
            ]);
        });

        // مرتب‌سازی و محدود کردن
        $activities = $activities->sortByDesc('date')->take($limit)->values();

        return response()->json(['data' => $activities]);
    }
}
