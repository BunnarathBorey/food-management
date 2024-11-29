<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LoginController extends Controller
{

    private $masterController;
    public function __construct()
    {
        $this->masterController = new MasterController();
    }

    /**
     * sign up
     */

    public function signUp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'gender' => 'required|string',
                'email' => 'required|string|email',
                'password' => 'required|string',
                'confirm_password' => 'required|string',
                'user_name' => 'required|string',
                'profile_picture' => 'nullable|file|mimes:jpeg,png|max:5000',
            ]);

            // checking if field empty
            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            //checking existing email
            $exsitingEmail = User::where('email', $request->email)->first();
            if ($exsitingEmail) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'email already exist',
                ]);
            }
            //checking existing username
            $exsitingUsername  = UserDetail::where('user_name', $request->user_name)->first();
            if ($exsitingUsername) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'username already exist',
                ]);
            }

            //password range checking
            if (strlen($request->password) < 8 || strlen($request->password) > 16) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'password must be higher than 8 and lower than 16',
                ]);
            }

            //confirm password range checking
            if (strlen($request->confirm_password) < 8 || strlen($request->confirm_password) > 16) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'password must be higher than 8 and lower than 16',
                ]);
            }

            //confirm password and password checking matching
            if (strcmp($request->confirm_password, $request->password)) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'not match',
                ]);
            }

            try {
                //user create
                $users = new User();
                $users->first_name = $request->first_name;
                $users->last_name = $request->last_name;
                $users->email = $request->email;
                $users->email_verified_at = null;
                $users->password = Hash::make($request->password);
                $users->status = 'user';
                $users->created_at = Carbon::now();
                $users->updated_at = Carbon::now();
                $users->save();

                //user detail create
                $user_details = new UserDetail();
                $user_details->user_id = $users->id;
                $user_details->user_name = $request->user_name;
                $user_details->gender = $request->gender;

                if ($request->hasFile('profile_picture')) {
                    //upload profile image
                    $profilePicture = $this->masterController->uploadFile($request->file('profile_picture'), 'user-profile', $user_details->profile_picture);
                    if ($profilePicture !== null) {
                        $user_details->profile_picture = $profilePicture;
                    }
                } else {
                    //copy image from default profile to user-profile
                    $defualtUserProfileImageUpload = 'default-profile/defualtImageProfile.png';
                    $newPathForDefualtUserProfileImage = public_path('user-profile');
                    $this->masterController->copyDefaultImage($defualtUserProfileImageUpload, $newPathForDefualtUserProfileImage, $user_details, 'profile_picture');
                }

                $user_details->created_at = Carbon::now();
                $user_details->updated_at = Carbon::now();
                $user_details->save();

                // sign up data
                $signUpData = [
                    'users' => $users,
                    'user_details' => $user_details,
                    'token' => $users->createToken('authToken', [


                    ])->plainTextToken
                ];

                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'sign up successfully',
                    'data' => [
                        'result' => $signUpData
                    ]
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => Str::limit($e->getMessage(), 150, '...'),
                ], 500);
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
     * sign in
     */

    public function signIn(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'email or password is not correct',
                ]);
            }

            if (strlen($request->password) < 8 || strlen($request->password) > 16) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'password must be higher then 8 or lower then 16',
                ]);
            }

            try {
                $users = Auth::user();

                $userId = $users->pluck('id');
                //get user detail by key user id
                $user_details = UserDetail::whereIn('user_id', $userId)->get()->keyBy('user_id');

                $user_details = $user_details->map(function ($user_detail) {
                    $user_detail->profile_picture = $this->masterController->appendBaseUrl($user_detail->profile_picture, 'user-profile');
                    return $user_detail;
                });

                $user_detail = $user_details->get($users->id) ?? null;
                if ($users !== null) {
                    $users['user_details'] = $user_detail;
                } else {
                    $users = null;
                }
                $signInData = [
                    'user' => $users,
                    'token' => $users->createToken('authToken', [


                    ])->plainTextToken
                ];
                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'login successfully',
                    'data' => [
                        'result' => $signInData
                    ]
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => Str::limit($e->getMessage(), 150, '...'),
                ], 500);
            }
        } catch (Exception $e) {
            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...'),
            ], 500);
        }
    }


    public function signUpAsAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'gender' => 'required|string',
                'email' => 'required|string|email',
                'password' => 'required|string',
                'confirm_password' => 'required|string',
                'user_name' => 'required|string',
                'profile_picture' => 'nullable|file|mimes:jpeg,png|max:5000',
            ]);

            // checking if field empty
            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            //checking existing email
            $exsitingEmail = User::where('email', $request->email)->first();
            if ($exsitingEmail) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'email already exist',
                ]);
            }
            //checking existing username
            $exsitingUsername  = UserDetail::where('user_name', $request->user_name)->first();
            if ($exsitingUsername) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'username already exist',
                ]);
            }

            //password range checking
            if (strlen($request->password) < 8 || strlen($request->password) > 16) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'password must be higher than 8 and lower than 16',
                ]);
            }

            //confirm password range checking
            if (strlen($request->confirm_password) < 8 || strlen($request->confirm_password) > 16) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'password must be higher than 8 and lower than 16',
                ]);
            }

            //confirm password and password checking matching
            if (strcmp($request->confirm_password, $request->password)) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'not match',
                ]);
            }

            try {
                //user create
                $users = new User();
                $users->first_name = $request->first_name;
                $users->last_name = $request->last_name;
                $users->email = $request->email;
                $users->email_verified_at = null;
                $users->password = Hash::make($request->password);
                $users->status = 'admin';
                $users->created_at = Carbon::now();
                $users->updated_at = Carbon::now();
                $users->save();

                //user detail create
                $user_details = new UserDetail();
                $user_details->user_id = $users->id;
                $user_details->user_name = $request->user_name;
                $user_details->gender = $request->gender;

                if ($request->hasFile('profile_picture')) {
                    //upload profile image
                    $profilePicture = $this->masterController->uploadFile($request->file('profile_picture'), 'user-profile', $user_details->profile_picture);
                    if ($profilePicture !== null) {
                        $user_details->profile_picture = $profilePicture;
                    }
                } else {
                    //copy image from default profile to user-profile
                    $defualtUserProfileImageUpload = 'default-profile/defualtImageProfile.png';
                    $newPathForDefualtUserProfileImage = public_path('user-profile');
                    $this->masterController->copyDefaultImage($defualtUserProfileImageUpload, $newPathForDefualtUserProfileImage, $user_details, 'profile_picture');
                }

                $user_details->created_at = Carbon::now();
                $user_details->updated_at = Carbon::now();
                $user_details->save();

                // sign up data
                $signUpData = [
                    'users' => $users,
                    'user_details' => $user_details,
                    'token' => $users->createToken('authToken', [
                        'admin:profile',


                        'admin:food-add',
                        'admin:food-view',
                        'admin:food-update',
                        'admin:food-remove',


                    ])->plainTextToken
                ];

                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'sign up successfully',
                    'data' => [
                        'result' => $signUpData
                    ]
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => Str::limit($e->getMessage(), 150, '...'),
                ], 500);
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
     * sign in
     */

    public function signInAsAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => $validator->errors(),
                ], 400);
            }

            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'email or password is not correct',
                ]);
            }

            if (strlen($request->password) < 8 || strlen($request->password) > 16) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'password must be higher then 8 or lower then 16',
                ]);
            }

            try {
                $users = Auth::user();

                $userId = $users->pluck('id');
                //get user detail by key user id
                $user_details = UserDetail::whereIn('user_id', $userId)->get()->keyBy('user_id');

                $user_details = $user_details->map(function ($user_detail) {
                    $user_detail->profile_picture = $this->masterController->appendBaseUrl($user_detail->profile_picture, 'user-profile');
                    return $user_detail;
                });

                $user_detail = $user_details->get($users->id) ?? null;
                if ($users !== null) {
                    $users['user_details'] = $user_detail;
                } else {
                    $users = null;
                }
                $signInData = [
                    'user' => $users,
                    'token' => $users->createToken('authToken', [
                        'admin:profile',

                        'admin:food-add',
                        'admin:food-view',
                        'admin:food-update',
                        'admin:food-remove',


                    ])->plainTextToken
                ];
                return response()->json([
                    'verified' => true,
                    'status' => 'success',
                    'message' => 'login successfully',
                    'data' => [
                        'result' => $signInData
                    ]
                ], 200);
            } catch (Exception $e) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => Str::limit($e->getMessage(), 150, '...'),
                ], 500);
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
     * logout.
     */
    public function logout()
    {

        try {
            if (!Auth::check()) {
                return response()->json([
                    'verified' => false,
                    'status' => 'error',
                    'message' => 'please login'
                ], 501);
            }

            session()->forget('token');
            $user = Auth::user();
            $user->tokens()->delete();

            return response()->json([
                'verified' => true,
                'status' => 'success',
                'message' =>  'logged out',
            ], 200);
        } catch (Exception $e) {

            return response()->json([
                'verified' => false,
                'status' => 'error',
                'message' => Str::limit($e->getMessage(), 150, '...')
            ], 500);
        }
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
    public function create()
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
