<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Requestt;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RequestController extends Controller
{
    public function getActiveSubscriptionsOffice()
    {
        $office = auth('office-api')->user();

        // جلب الاشتراكات ذات الحالة active للمكتب الحالي
        $activeSubscriptions = $office->subscriptions()
            ->where('status', 'active')
            ->get();

        if ($activeSubscriptions->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد اشتراكات نشطة لهذا المكتب.',
            ], 200);
        }

        return response()->json([
            'message' => 'تم جلب الاشتراكات النشطة بنجاح.',
            'data' => $activeSubscriptions,
        ], 200);
    }

    public function getRejectedSubscriptionsOffice()
    {
        $office = auth('office-api')->user();

        $rejectedSubscriptions = $office->subscriptions()
            ->where('status', 'rejected')
            ->get();

        if ($rejectedSubscriptions->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد اشتراكات مرفوضة لهذا المكتب.',
            ], 200);
        }

        return response()->json([
            'message' => 'تم جلب الاشتراكات المرفوضة بنجاح.',
            'data' => $rejectedSubscriptions,
        ], 200);
    }


    public function getPendingSubscriptionsOffice()
    {
        $office = auth('office-api')->user();

        $pendingSubscriptions = $office->subscriptions()
            ->where('status', 'pending')
            ->get();

        if ($pendingSubscriptions->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد اشتراكات معلقة لهذا المكتب.',
            ], 200);
        }

        return response()->json([
            'message' => 'تم جلب الاشتراكات المعلقة بنجاح.',
            'data' => $pendingSubscriptions,
        ], 200);
    }

    public function getPendingRequestsOffice(Request $request)
    {
        $office = auth('office-api')->user(); // المكتب المسجل

        // جلب الطلبات اللي requestable_type = Property::class و status = pending
        $pendingPropertyRequests = Requestt::where('office_id', $office->id)
            ->where('status', 'pending')
            ->where('requestable_type', Property::class)
            ->with('requestable') // لتحميل بيانات العقار المرتبط بالطلب
            ->get();

        if ($pendingPropertyRequests->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد طلبات عقارات معلقة لهذا المكتب.',
            ], 200);
        }

        return response()->json([
            'message' => 'تم جلب طلبات العقارات المعلقة بنجاح.',
            'data' => $pendingPropertyRequests,
        ], 200);
    }


    public function getAcceptedRequestsOffice()
    {
        $office = auth('office-api')->user();

        $acceptedRequests = Requestt::where('office_id', $office->id)
            ->where('status', 'accepted')
            ->where('requestable_type', Property::class)
            ->with('requestable')
            ->get();

        if ($acceptedRequests->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد طلبات عقارات مقبولة لهذا المكتب.',
            ], 200);
        }

        return response()->json([
            'message' => 'تم جلب طلبات العقارات المقبولة بنجاح.',
            'data' => $acceptedRequests,
        ], 200);
    }

    public function getRejectedRequestsOffice()
    {
        $office = auth('office-api')->user();

        $rejectedRequests = Requestt::where('office_id', $office->id)
            ->where('status', 'rejected')
            ->where('requestable_type', Property::class)
            ->with('requestable')
            ->get();

        if ($rejectedRequests->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد طلبات عقارات مرفوضة لهذا المكتب.',
            ], 200);
        }

        return response()->json([
            'message' => 'تم جلب طلبات العقارات المرفوضة بنجاح.',
            'data' => $rejectedRequests,
        ], 200);
    }
}
