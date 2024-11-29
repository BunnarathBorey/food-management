<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MasterController;
use App\Models\Food;
use App\Models\UserDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
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
        try{
            if(!Auth::check()){
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

            if($validator->fails()){
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ],400);
            }
            if(Auth::user()->tokenCan('admin:food-add')){
                $users = Auth::user();
                if($users->status !== 'admin'){
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' =>'no accessible',
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
                }
                else{
                    return response()->json([
                        'verified'=> false,
                        'status' => 'error',
                        'message' => 'please upload the image'
                    ]);
                }
                if($request->availability === 'available'){
                    $foods->availability = true;
                }
                if($request->availability === 'no available'){
                    $foods->availability = false;
                }
                $foods->created_at = Carbon::now();
                $foods->updated_at = Carbon::now();
                $foods->save();
                return response()->json([
                    'verified' => true,
                    'status' =>'success',
                    'message' => 'food create into the list already',
                    'data' => [
                        'result' => $foods,
                    ]
                ], 200);

            }else{
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'no accessible'
                ], 403);
            }
        }
        catch(Exception $e){
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }

    public function view(Request $request){
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


            if (Auth::user()->tokenCan('admin:food-view')) {
                $users = Auth::user();
                if($users->status !== 'admin'){
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' =>'no accessible',
                    ]);
                }
                $foods = Food::paginate($request->range);

                $tranFormCollection = $foods->getCollection()->transform(function ($food)  {

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
            } else {
                return response()->json([
                    'verified' => false,
                    'status ' => 'error',
                    'message' => 'no accessible',
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

    public function profile(){
        try{
            if(!Auth::check()){
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ]);
            }

            if(Auth::user()->tokenCan('admin:profile')){

                $users = Auth::user();
                $userId = $users->pluck('id');
                $user_details = UserDetail::whereIn('user_id', $userId)->get()->keyBy('user_id');
                $user_details = $user_details->map(function ($user_detail){
                    $user_detail->profile_picture = $this->masterController->appendBaseUrl($user_detail->profile_picture, 'user-profile');
                    return $user_detail;
                });
                $user_detail = $user_details->get($users->id) ?? null;
                if($users !== null){
                    $users['user_details'] = $user_detail;
                }
                else{
                    $users = null;
                }
                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'found',
                    'data' => [
                       'result' => $users
                    ]
                ], 200);
            }
            else{
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'no accessible'
                ], 403);
            }
        }
        catch(Exception $e){
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ],500);
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
        //
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
    public function update(Request $request, string $id)
    {
        try{
            if(!Auth::check()){
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

            if($validator->fails()){
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ],400);
            }
            if(Auth::user()->tokenCan('admin:food-update')){
                $users = Auth::user();
                if($users->status !== 'admin'){
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' =>'no accessible',
                    ]);
                }
                $foods = Food::where('id', $id)->first();
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
                    'status' =>'success',
                    'message' => 'food edit successfully',
                    'data' => [
                        'result' => $foods,
                    ]
                ], 200);

            }else{
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'no accessible'
                ], 403);
            }
        }
        catch(Exception $e){
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
                if($users->status !== 'admin'){
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' =>'no accessible',
                    ]);
                }

                $foods = Food::where('id', $id)->first();

                $imagePath = public_path($foods->food_image); // Get the full path
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
