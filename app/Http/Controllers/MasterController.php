<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MasterController extends Controller
{
    /**
     * upload image
     */


     public function uploadFile($file, $directory, $existingFileName)
     {
         if ($file) {
             $originalName = $file->getClientOriginalName();
             $extension = $file->getClientOriginalExtension();
             $nameWithoutExtension = str_replace("." . $extension, "", $originalName);
             $encryptedName = base64_encode($nameWithoutExtension);
             $encryptedNameWithExtension = $encryptedName . '.' . $extension;

             // If file with the same name already exists, generate a unique filename
             if (file_exists(public_path($directory . '/' . $encryptedNameWithExtension))) {
                 $uuid = Str::uuid()->toString();
                 $encryptedNameWithExtension = $uuid . '.' . $extension;
             }

             $file->move($directory, $encryptedNameWithExtension);

             // If there was an existing file, delete it
             if (!empty($existingFileName)) {
                 $existingFilePath = public_path($directory . '/' . $existingFileName);
                 if (file_exists($existingFilePath)) {
                     unlink($existingFilePath);
                 }
             }

             return $encryptedNameWithExtension;
         }
         return null;
     }

     /**
      * copy image
      */


     public function copyDefaultImage($originalImagePath, $destinationFolder, $userDetail, $attribute)
     {
         // Ensure destination folder exists

         if (!File::isDirectory($destinationFolder)) {
             File::makeDirectory($destinationFolder, 0755, true, true);
         }

         // Extract file extension and generate a unique filename
         $extension = pathinfo($originalImagePath, PATHINFO_EXTENSION);
         // $fileName = time() . '.' . $extension;
         $nameWithoutExtension = str_replace("." . $extension, "", $originalImagePath);
         $encryptedName = base64_encode($nameWithoutExtension);
         $encryptedNameWithExtension = $encryptedName . '.' . $extension;


         $destinationPath = $destinationFolder . '/' . $encryptedNameWithExtension;
         if (File::exists($destinationPath)) {
             // Generate a UUID to ensure uniqueness
             $uuid = Str::uuid();
             $uniqueFileName = $uuid . '.' . $extension;
             $destinationPath = $destinationFolder . '/' . $uniqueFileName;
         }

         File::copy(public_path($originalImagePath), $destinationPath);

         // Update the user detail attribute with the filename
         $userDetail->$attribute = basename($destinationPath);
     }

     /**
      * url display
      */
     public function appendBaseUrl($path, $baseUrl)
     {
         // Check if the path is already a full URL
         if (!filter_var($path, FILTER_VALIDATE_URL)) {
             // If not, append the base URL
             return url($baseUrl . '/' . $path);
         }
         return $path;
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
