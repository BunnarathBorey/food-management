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
       //
    }

    // profile

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
                'user_name' => 'nullable|string',
                'profile_picture' => 'nullable|file|mimes:jpeg,png|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            if (Auth::user()->tokenCan('admin:edit-profile')) {
                $users = Auth::user();
                $user_details = UserDetail::where('user_id', $users->id)->first();
                $user_details->user_name = empty($request->user_name) || null ? $user_details->user_name : $request->user_name;
                try {
                    // Usage for uploading profile picture
                    $profilePicture = $this->masterController->uploadFile($request->file('profile_picture'), 'user-profile', $user_details->profile_picture);
                    if ($profilePicture !== null) {
                        $user_details->profile_picture = $profilePicture;
                    }
                } catch (Exception $e) {
                    return response()->json([
                        'verified' => false,
                        'status' => 'error',
                        'message' => Str::limit($e->getMessage(), 150, '...'),
                    ], 500);
                }

                $user_details->updated_at = Carbon::now();
                $user_details->save();

                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'save',
                    'data' => [
                        'result' => $user_details,
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
       //
    }
}
