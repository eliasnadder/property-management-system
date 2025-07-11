<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Traits\UploadImagesTrait;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;

class PropertyController extends Controller
{
    use UploadImagesTrait;
    public function propertyStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'governorate' => 'required|string|max:255',
            'location' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'area' => 'nullable|numeric',
            'floor_number' => 'nullable|integer',
            'ad_type' => 'required|in:sale,rent',
            'type' => 'required|in:apartment,villa,office,land,commercial,farm,building,chalet',
            'position' => 'required|in:sale,sold,rent',
            'bathrooms' => 'required|integer',
            'rooms' => 'required|integer',
            'seller_type' => 'required|in:owner,agent,developer',
            'direction' => 'required|string',
            'furnishing' => 'required|in:furnished,unfurnished,semi-furnished',
            'url' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'Vurl' => 'nullable|mimes:mp4,mov,avi,wmv|max:10000',
            'is_offer' => 'nullable|boolean',
            'offer_expires_at' => 'required_if:is_offer,true|nullable|date',
            'currency' => 'nullable|in:SYP,USD,EUR',
            'features' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $authOffice = auth('office-api')->user();

            if (!$authOffice || !$authOffice instanceof \App\Models\Office) {
                return response()->json(['error' => 'Only offices can create properties'], 403);
            }

            $subscription = $authOffice->currentSubscription;

            if ($authOffice->free_ads == 0 && (!$subscription || $subscription->status !== 'active' || $subscription->expires_at < now())) {
                return response()->json([
                    'message' => 'انتهت الإعلانات المجانية ولا يوجد اشتراك مفعل. الرجاء طلب اشتراك من الإدارة.',
                    'subscription_status' => optional($subscription)->status,
                ], 403);
            }

            // إنشاء العقار بوضعية "غير مفعل" بانتظار موافقة الإدارة
            $property = Property::create([
                'owner_id' => $authOffice->id,
                'owner_type' => \App\Models\Office::class,
                'ad_number' => strtoupper(substr($request->governorate, 0, 2)) . '-' . strtoupper(uniqid()),
                'title' => $request->title,
                'description' => $request->description,
                'price' => $request->price,
                'governorate' => $request->governorate,
                'location' => $request->location,
                'latitude' => floatval(trim($request->latitude)),
                'longitude' => floatval(trim($request->longitude)),
                'area' => $request->area,
                'floor_number' => $request->floor_number,
                'ad_type' => $request->ad_type,
                'type' => $request->type,
                'position' => $request->position,
                'is_offer' => $request->is_offer ?? false,
                'offer_expires_at' => $request->offer_expires_at ?? now()->addDays(5),
                'currency' => $request->currency ?? 'USD',
                'views' => 0,
                'bathrooms' => $request->bathrooms,
                'rooms' => $request->rooms,
                'seller_type' => $request->seller_type,
                'direction' => $request->direction,
                'furnishing' => $request->furnishing,
                'features' => $request->features,
                // أو is_active => false حسب تصميمك
            ]);

            if ($request->hasFile('url')) {
                $path = $this->uploadImage($request->file('url'), 'property');
                $property->images()->create(['url' => $path]);
            }

            if ($request->hasFile('Vurl')) {
                $file = $request->file('Vurl');
                $fileName = uniqid() . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('properties/videos', $fileName, 'public');
                $videoUrl = 'storage/' . $filePath;

                $property->video()->create([
                    'vurl' => $videoUrl,
                    'videoable_id' => $property->id,
                    'videoable_type' => Property::class,
                ]);
            }

            // خصم إعلان واحد
            $authOffice->free_ads -= 1;
            $authOffice->save();

            // إنشاء الطلب
            \App\Models\Requestt::create([
                'office_id' => $authOffice->id,
                'requestable_id' => $property->id,
                'requestable_type' => \App\Models\Property::class,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'تم إرسال طلب إنشاء العقار بنجاح. سيتم مراجعته من قبل الإدارة.',
                'data' => $property->load('images', 'video'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'حدث خطأ أثناء إنشاء الإعلان.',
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ], 500);
        }
    }


    public function changePropertyStatus(Request $request, $propertyId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:available,sold,rented',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'بيانات غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $authUser = JWTAuth::parseToken()->authenticate();

            if (!$authUser) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }

            $property = Property::find($propertyId);

            if (!$property) {
                return response()->json(['message' => 'العقار غير موجود'], 404);
            }

            // تحقق إذا العقار يخص المستخدم أو مكتبه (حسب نظامك)
            // مثلاً، إذا عندك علاقة بين العقار وصاحب أو المكتب:

            if ($property->owner_id !== $authUser->id) {
                return response()->json(['message' => 'غير مصرح بتعديل هذا العقار'], 403);
            }

            // تحديث الحالة
            $property->status = $request->status;
            $property->save();

            return response()->json([
                'message' => 'تم تحديث حالة العقار بنجاح',
                'property' => $property,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'حدث خطأ أثناء تحديث الحالة',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    //تابع يجلب جميع العقارات
    public function getAllproperty()
    {
        $properties = Property::with(['owner', 'images', 'video'])->paginate(20);
        if (!$properties) {
            return response()->json(['message' => 'not found'], 404);
        }

        return response()->json($properties);
    }

    public function showProperty($id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'not found'], 404);
        }

        $property->views += 1;
        $property->save();

        // تحميل العلاقات
        $property->load(['owner', 'images', 'video']);

        // جلب العقارات المرتبطة
        $relaitedproperties = Property::where('type', $property->type)
            ->where('ad_type', $property->ad_type)
            ->with(['owner', 'images', 'video'])
            ->get();

        return response()->json([
            'property' => $property->with(['owner', 'images', 'video'])->get(),
            'relaitedproperties' => $relaitedproperties
        ]);
    }


    public function getPropertyVideos()
    {
        $videos = Video::where('videoable_type', Property::class)
            ->with('videoable') // هذا سيجلب معلومات العقار المرتبط
            ->get();

        if ($videos->isEmpty()) {
            return response()->json([
                'message' => 'لا توجد فيديوهات لعقارات حالياً.',
                'data' => []
            ], 404); // يمكن تغيير الكود إلى 200 لو أردت
        }

        return response()->json([
            'message' => 'تم جلب فيديوهات العقارات بنجاح.',
            'data' => $videos
        ]);
    }
    //تابع بجيب العقارات يلي عليها عرض آخر 3 أيام بدءاً من الأحدث
    public function getRecentOffers()
    {
        // حساب التاريخ قبل 3 أيام
        $recentDate = now()->subDays(3);

        // جلب العقارات التي عليها عروض خلال آخر 3 أيام
        $properties = Property::where('is_offer', true)
            ->where('created_at', '>=', $recentDate)
            ->orderBy('created_at', 'desc')
            ->get();

        // التحقق من وجود عروض
        if ($properties->isEmpty()) {
            return response()->json(['message' => 'لا توجد عروض حالياً'], 200);
        }

        return response()->json($properties);
    }


    public function searchByAdNumber($ad_number)
    {
        $property = Property::with(['owner', 'images', 'video'])
            ->where('ad_number', $ad_number)
            ->first();

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        return response()->json($property);
    }

    public function filter(Request $request)
    {
        $query = Property::with(['owner', 'images', 'video']);

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('views')) {
            if ($request->views === 'most') {
                $query->orderBy('views', 'desc');
            } elseif ($request->views === 'least') {
                $query->orderBy('views', 'asc');
            }
        }
        $properties = $query->paginate(20);

        if ($properties->isEmpty()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($properties);
    }
}
