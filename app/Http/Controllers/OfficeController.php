<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;
use App\Traits\UploadImagesTrait;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class OfficeController extends Controller
{
    use UploadImagesTrait;

    public function registerOffice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:3,50',
            'phone' => 'required|digits:10',
            'type' => 'required',
            'description' => 'nullable|string|max:1000',
            'location' => 'nullable|string|max:255',
            'document' => 'required|file|mimes:pdf|max:2048',
            'url' => 'nullable|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }


        DB::beginTransaction();

        try {
            $documentPath = $request->file('document')->store('documents', 'public');

            $office = \App\Models\Office::create([
                'name' => $request->name,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'description' => $request->description,
                'location' => $request->location,
                'document_path' => $documentPath,
                'status' => 'pending',
                'free_ads' => 0,
                'followers_count' => 0,
                'views' => 0,
            ]);


            if ($request->hasFile('url')) {
                $imageUrl = $this->uploadImage($request->file('url'), 'offices');
                $office->image()->create([
                    'url' => $imageUrl,
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'تم إرسال طلب فتح مكتب بنجاح. سيتم مراجعته من قبل الإدارة.',
                'status' => 'pending',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }


    public function requestSubscription(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_type' => 'required|in:monthly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $office = auth('office-api')->user();

        // تحقق إذا عنده اشتراك نشط حالي
        $activeSubscription = $office->subscriptions()
            ->where('status', 'active')
            ->where('starts_at', '<=', now())
            ->where('expires_at', '>', now())
            ->first();

        if ($activeSubscription) {
            return response()->json([
                'message' => 'لديك اشتراك نشط حالياً. لا يمكنك إرسال طلب جديد حتى ينتهي الاشتراك الحالي.',
            ], 400);
        }

        // تحقق إذا عنده طلب اشتراك معلق
        $pendingRequest = $office->subscriptions()
            ->where('status', 'pending')
            ->first();

        if ($pendingRequest) {
            return response()->json([
                'message' => 'لقد قمت بالفعل بإرسال طلب اشتراك قيد الانتظار. الرجاء انتظار موافقة الإدارة.',
            ], 400);
        }

        // تحديد السعر
        $price = $request->subscription_type === 'monthly' ? 50 : 500;

        // إنشاء طلب الاشتراك
        $subscription = $office->subscriptions()->create([
            'subscription_type' => $request->subscription_type,
            'price' => $price,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'تم إرسال طلب الاشتراك، بانتظار موافقة الإدارة.',
            'subscription' => $subscription,
        ]);
    }




    public function getAllOfficePropertyVideos($id)
    {
        try {


            $office = Office::find($id);

            if (!$office) {
                return response()->json(['message' => 'لا يوجد هذا المكتب .'], 404);
            }


            $propertiesWithVideos = $office->properties()->with('video')->get();

            // هون رح يعمل فلتره العقارات يلي ما الها فيديوهات بشيلا وبيرجع برتبا
            $videos = $propertiesWithVideos->pluck('video')->filter()->values();

            return response()->json([
                'message' => 'تم جلب فيديوهات العقارات بنجاح.',
                'data' => $videos,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب الفيديوهات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getAllOfficeProperties($id)
    {
        try {
            $office = Office::find($id);

            if (!$office) {
                return response()->json(['message' => 'لا يوجد هذا المكتب.'], 404);
            }

            // جلب العقارات الخاصة بالمكتب بدون علاقة الفيديو
            $properties = $office->properties()->with(['owner', 'images'])->get();

            return response()->json([
                'message' => 'تم جلب العقارات الخاصة بالمكتب بنجاح.',
                'data' => $properties,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب العقارات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOfficePropertyCount($id)
    {
        try {

            $office = Office::find($id);

            if (!$office) {
                return response()->json(['message' => 'لا يوجد هذا المكتب .'], 404);
            }

            $count = $office->properties()->count();

            return response()->json([
                'message' => 'تم جلب عدد العقارات بنجاح.',
                'count' => $count,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب عدد العقارات.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function followOffice(Request $request, $officeId)
    {
        try {
            $authUser = JWTAuth::parseToken()->authenticate();

            if (!$authUser) {
                return response()->json(['message' => 'غير مصرح, يجب تسجيل الخول اولا'], 401);
            }

            $office = Office::find($officeId);

            if (!$office) {
                return response()->json(['message' => 'المكتب غير موجود'], 404);
            }

            // تحقق إذا المتابعة موجودة
            $alreadyFollowing = $office->followers()->where('user_id', $authUser->id)->exists();

            if ($alreadyFollowing) {
                return response()->json(['message' => 'أنت تتابع هذا المكتب مسبقًا'], 409);
            }

            // تنفيذ المتابعة
            $office->followers()->attach($authUser->id);

            // زيادة عدد المتابعين
            $office->increment('followers_count');

            return response()->json(['message' => 'تمت متابعة المكتب بنجاح'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء المتابعة',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getFollowersCount($officeId)
    {
        $office = Office::find($officeId);

        if (!$office) {
            return response()->json(['message' => 'المكتب غير موجود'], 404);
        }

        $count = $office->followers()->count();

        return response()->json([
            'message' => 'عدد المتابعين للمكتب',
            'followers_count' => $count,
        ], 200);
    }
    //تابع يجلب معلومات المكتب
    public function showOffice($id)
    {
        try {
            // جلب المكتب أو إرجاع 404 إذا غير موجود
            $office = Office::findOrFail($id);

            // زيادة عدد المشاهدات بشكل أوتوماتيكي
            $office->increment('views');

            // إرجاع بيانات المكتب
            return response()->json([
                'message' => 'تم جلب بيانات المكتب بنجاح.',
                'data' => $office,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء جلب بيانات المكتب.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    //تابع يجلب عدد المشاهدين
    public function getOfficeViews($office_id)
    {
        $office = Office::find($office_id);

        if (!$office) {
            return response()->json([
                'message' => 'المكتب غير موجود.',
            ], 404);
        }

        return response()->json([
            'message' => 'تم جلب عدد المشاهدين بنجاح.',
            'office_id' => $office_id,
            'views' => $office->views,
        ], 200);
    }
    //تابع يجيب المتابعين
    public function GetOfficeFollowers($id)
    {

        $office_id = $id;


        if (!Office::find($id)) {
            return response()->json(['message' => 'this office doesnt exist'], 404);
        }
        $followers = DB::table('office_followers')
            ->join('users', 'office_followers.user_id', '=', 'users.id')
            ->where('office_followers.office_id', $office_id)
            ->select('users.*')
            ->get();

        if ($followers->isEmpty()) {
            return response()->json(['message' => 'this office dont have followers'], 404);
        }


        return response()->json(['followers' => $followers], 200);
    }
}
