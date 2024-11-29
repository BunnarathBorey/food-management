<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use App\Http\Controllers\MasterController;
use App\Models\Food;


class FoodController extends Controller
{

    private $masterController;
    public function __construct()
    {
        $this->masterController = new MasterController();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ]);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric',
                'category' => 'required|string',
                'food_image' => 'nullable|file|mimes:jpeg,png|max:5000',
                'availability' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }
            if (Auth::user()->tokenCan('admin:food-add')) {
                $users = Auth::user();
                if ($users->status !== 'admin') {
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' => 'no accessible',
                    ]);
                }
                $foods = new Food();
                $foods->name = $request->name;
                $foods->description = $request->description;
                $foods->price = $request->price;
                $foods->category = $request->category;
                if ($request->hasFile('food_image')) {
                    //upload food image
                    $foodImage = $this->masterController->uploadFile($request->file('food_image'), 'food-image', $foods->food_image);
                    if ($foodImage !== null) {
                        $foods->food_image = $foodImage;
                    }
                } else {
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' => 'please upload the image'
                    ]);
                }
                if ($request->availability === 'available') {
                    $foods->availability = true;
                }
                if ($request->availability === 'no available') {
                    $foods->availability = false;
                }
                $foods->created_at = Carbon::now();
                $foods->updated_at = Carbon::now();
                $foods->save();
                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'food create into the list already',
                    'data' => [
                        'result' => $foods,
                    ]
                ], 200);
            } else {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'no accessible'
                ], 403);
            }
        } catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }

    public function view(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ]);
            }

            $validator = Validator::make($request->all(), [
                'page' => 'required|numeric',
                'range' => 'required|numeric',
                'category' => 'nullable|string', // Optional: Filter by category
                'availability' => 'nullable|boolean', // Optional: Filter by availability
                'price_min' => 'nullable|numeric', // Optional: Filter by minimum price
                'price_max' => 'nullable|numeric', // Optional: Filter by maximum price
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 400);
            }

            $page = $request->get('page', 1);
            Paginator::currentPageResolver(function () use ($page) {
                return $page;
            });

            $query = Food::query();

            // Apply filters if provided
            if ($request->filled('category')) {
                $query->where('category', $request->category);
            }

            if ($request->filled('availability')) {
                $query->where('availability', $request->availability);
            }

            if ($request->filled('price_min')) {
                $query->where('price', '>=', $request->price_min);
            }

            if ($request->filled('price_max')) {
                $query->where('price', '<=', $request->price_max);
            }

            // Paginate results
            $foods = $query->paginate($request->range);

            // Transform the collection
            $tranFormCollection = $foods->getCollection()->transform(function ($food) {
                $food->food_image = $this->masterController->appendBaseUrl($food->food_image, 'food-image');
                return $food;
            });
            $foods->setCollection($tranFormCollection);

            return response()->json([
                'verified' => true,
                'status' => 'success',
                'message' => 'found',
                'data' => [
                    'result' => $foods,
                ]
            ], 200);




        } catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            if (!Auth::check()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ]);
            }

            $foods = Food::where('id', $id)->first();

            if(!$foods){
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'not found'
                ], 404);
            }

            // Directly modify the single food item
            $foods->food_image = $this->masterController->appendBaseUrl($foods->food_image, 'food-image');

            return response()->json([
                'verified' => true,
                'status' => 'success',
                'message' => 'found',
                'data' => [
                    'result' => $foods
                ]
            ]);

        }
        catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ]);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string',
                'description' => 'nullable|string',
                'price' => 'nullable|numeric',
                'category' => 'nullable|string',
                'food_image' => 'nullable|file|mimes:jpeg,png',
                'availability' => 'nullable|string',
                'food_id' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }
            if (Auth::user()->tokenCan('admin:food-update')) {
                $users = Auth::user();
                if ($users->status !== 'admin') {
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' => 'no accessible',
                    ]);
                }
                $foods = Food::where('id', $request->food_id)->first();
                $foods->name = empty($request->name) ? $foods->name : $request->name;
                $foods->description = empty($request->description) ? $foods->description : $request->description;
                $foods->price = empty($request->price) ? $foods->price : $request->price;
                $foods->category = empty($request->category) ? $foods->category : $request->category;
                try {
                    // Usage for uploading profile picture
                    $foodImage = $this->masterController->uploadFile($request->file('food_image'), 'food-image', $foods->food_image);
                    if ($foodImage !== null) {
                        $foods->food_image = $foodImage;
                    }
                } catch (Exception $e) {
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' => Str::limit($e->getMessage(), 150, '...'),
                    ], 500);
                }

                if (!empty($request->availability)) {
                    if ($request->availability === 'available') {
                        $foods->availability = true;
                    } elseif ($request->availability === 'no available') {
                        $foods->availability = false;
                    } else {
                        // Keep the current value
                        $foods->availability = $foods->availability;
                    }
                } else {
                    // If `availability` is not provided, keep the current value
                    $foods->availability = $foods->availability;
                }

                $foods->created_at = Carbon::now();
                $foods->updated_at = Carbon::now();
                $foods->save();
                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'food edit successfully',
                    'data' => [
                        'result' => $foods,
                    ]
                ], 200);
            } else {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'no accessible'
                ], 403);
            }
        } catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            if (!Auth::check()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ]);
            }
            if (Auth::user()->tokenCan('admin:food-remove')) {
                $users = Auth::user();
                if ($users->status !== 'admin') {
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' => 'no accessible',
                    ]);
                }

                $foods = Food::where('id', $id)->first();

                $imagePath = public_path('food-image/' . $foods->food_image); // Append the actual image filename
                if ($foods->food_image && file_exists($imagePath)) {
                    unlink($imagePath); // Delete the file
                }


                $foods->delete();
                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'food have delete successfully',
                    'data' => [
                        'result' => $foods,
                    ]
                ], 200);
            } else {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'no accessible'
                ], 403);
            }
        } catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }
}
