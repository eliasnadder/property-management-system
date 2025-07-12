<?php

namespace App\Http\Controllers;

use App\Models\Requestt;
use App\Models\Subscription;
use App\Models\Office;
use App\Http\Controllers\Controller;

class AdminController extends Controller
{
    // عرض قائمة الطلبات المعلقة
    public function pandingRequest()
    {
        $requests = Requestt::where('status', 'pending')->with('requestable')->paginate(10);
        return response()->json($requests);
    }

    // قبول الطلب (تغيير الحالة إلى approved)
    public function approveProperty($id)
    {
        $request = \App\Models\Requestt::find($id);

        if (!$request) {
            return response()->json(['error' => 'الطلب غير موجود.'], 404);
        }

        $request->status = 'accepted';  // تأكد أنها نصية
        $request->save();

        // تفعيل العقار المرتبط عند الموافقة
        $property = $request->requestable;
        if ($property) {
            $property->update(['is_available' => true]); // تفعيل العقار
        }

        return response()->json(['message' => 'تم قبول الطلب وتفعيل العقار.']);
    }

    // رفض الطلب (تغيير الحالة إلى rejected)
    public function rejectProperty($id)
    {
        $requestt = Requestt::findOrFail($id);
        $requestt->status = 'rejected';
        $requestt->save();

        return response()->json(['message' => 'تم رفض الطلب.']);
    }

    // عرض الاشتراكات المعلقة
    public function pendingSubscription()
    {
        $pendingSubscriptions = Subscription::with('office')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($pendingSubscriptions);
    }

    public function approveSubscription($id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status !== 'pending') {
            return response()->json(['message' => 'الاشتراك تمت معالجته مسبقاً.'], 400);
        }

        // تعيين تاريخ البدء والانتهاء
        $start = now();
        $end = $subscription->subscription_type === 'monthly' ? $start->copy()->addMonth() : $start->copy()->addYear();

        $subscription->update([
            'starts_at' => $start,
            'expires_at' => $end,
            'status' => 'active',
        ]);

        return response()->json(['message' => 'تمت الموافقة على الاشتراك بنجاح.']);
    }

    public function rejectSubscription($id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->status !== 'pending') {
            return response()->json(['message' => 'الاشتراك تمت معالجته مسبقاً.'], 400);
        }

        $subscription->update(['status' => 'rejected']);

        return response()->json(['message' => 'تم رفض الاشتراك.']);
    }

    public function approveOfficeRequest($requestId)
    {
        $request = Office::findOrFail($requestId);

        if ($request->status !== 'pending') {
            return response()->json(['message' => 'تمت معالجة هذا الطلب سابقًا.'], 400);
        }

        // تحديث حالة الطلب إلى approved
        $request->update(['status' => 'approved']);
        $request->update(['free_ads' => 2]);

        return response()->json(['message' => 'تمت الموافقة على المكتب وإنشاؤه بنجاح.'], 200);
    }

    public function rejectOfficeRequest($requestId)
    {
        $office = Office::findOrFail($requestId); // ← تعديل من OfficeController إلى Office

        if ($office->status !== 'pending') {
            return response()->json(['message' => 'تمت معالجة هذا الطلب سابقًا.'], 400);
        }

        $office->delete(); // ← حذف المكتب بالكامل

        return response()->json(['message' => 'تم رفض الطلب وحذف المكتب بنجاح.'], 200);
    }

    public function getOfficesByViews()
    {
        $offices = Office::with('image')->orderBy('views', 'desc')->get();

        if ($offices->isEmpty()) {
            return response()->json([
                'message' => 'لم يتم إضافة مكاتب للتطبيق بعد.'
            ], 404);
        }

        return response()->json([
            'message' => 'تم جلب المكاتب حسب عدد المشاهدات.',
            'data' => $offices
        ], 200);
    }

    public function getOfficesByFollowers()
    {
        $offices = Office::with('image')->orderBy('followers_count', 'desc')->get();

        if ($offices->isEmpty()) {
            return response()->json([
                'message' => 'لم يتم إضافة مكاتب للتطبيق بعد.'
            ], 404);
        }

        return response()->json([
            'message' => 'تم جلب المكاتب حسب عدد المتابعين.',
            'data' => $offices
        ], 200);
    }
}
