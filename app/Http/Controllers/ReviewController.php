<?php

namespace App\Http\Controllers\API;

use App\Models\Office;
use App\Models\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Traits\UploadImagesTrait;

class ReviewController extends Controller
{
    use UploadImagesTrait;
    //تابع يقوم بتقييم المكتب
    public function rateOffice(Request $request, $office_id)
    {
        $user = auth()->user();

        // التحقق من صحة قيمة التقييم فقط (id يتم استقباله من الباراميتر)
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        // التأكد من وجود المكتب
        $office = Office::find($office_id);

        if (!$office) {
            return response()->json([
                'message' => 'المكتب غير موجود.',
            ], 404);
        }

        // التحقق من وجود تقييم سابق
        $existing = Review::where('user_id', $user->id)
            ->where('reviewable_id', $office->id)
            ->where('reviewable_type', Office::class)
            ->first();

        if ($existing) {
            if ($existing->rating == $validated['rating']) {
                return response()->json([
                    'message' => 'لقد قمت بإدخال نفس التقييم السابق.',
                ], 200);
            } else {
                $existing->update([
                    'rating' => $validated['rating'],
                ]);

                return response()->json([
                    'message' => 'تم تحديث التقييم بنجاح.',
                    'data' => $existing,
                ]);
            }
        }

        // إنشاء تقييم جديد
        $review = Review::create([
            'user_id' => $user->id,
            'rating' => $validated['rating'],
            'reviewable_id' => $office->id,
            'reviewable_type' => Office::class,
        ]);

        return response()->json([
            'message' => 'تم التقييم بنجاح.',
            'data' => $review,
        ]);
    }

    //تابع يقوم بجلب تقييم المكتب
    public function getRating($office_id)
    {
        // تحقق من أن المكتب موجود
        $office = Office::find($office_id);

        if (!$office) {
            return response()->json([
                'message' => 'المكتب غير موجود.',
            ], 404);
        }

        // حساب التقييم المتوسط
        $average = $office->reviews()->avg('rating');

        return response()->json([
            'office_id' => $office->id,
            'average_rating' => round($average, 2),
        ]);
    }
}
