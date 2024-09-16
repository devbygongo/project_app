<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProductModel;

use App\Models\User;

use League\Csv\Reader;

use League\Csv\Statement;

use Hash;

use App\Models\CategoryModel;

use App\Models\SubCategoryModel;

class CsvImportController extends Controller
{
    //
    public function importProduct()
    {
        // URL of the CSV file from Google Sheets
        $get_product_csv_url = 'https://docs.google.com/spreadsheets/d/1oF0yBLb2GjMhBep8ZpmmTYjJoW8d6AcajGDATEvqZaU/pub?gid=0&single=true&output=csv';

        // Fetch the CSV content using file_get_contents
        $csvContent_product = file_get_contents($get_product_csv_url);

        // Fetch and parse the CSV
        $csv_product = Reader::createFromString($csvContent_product);

        $csv_product->setHeaderOffset(0); // Set the header offset
        

        $records_csv = (new Statement())->process($csv_product);

        $product_insert_response = null;
        $product_update_response = null;

        // Iterate through each record and create or update the product
        foreach ($records_csv as $record_csv) {
            $product_csv = ProductModel::where('sku', $record_csv['SKU'])->first();

            $basicPrice_product = $record_csv['Basic Price'] !== '' ? $record_csv['Basic Price'] : 0;
            $gstPrice_prduct = $record_csv['GST Price'] !== '' ? $record_csv['GST Price'] : 0;
            $filename = $record_csv['Product Code'];

            $category = $record_csv['Category'];
            $sub_category = $record_csv['Sub Category'];

            // $categoryModel = CategoryModel::firstOrCreate(['name' => $category]);

            $categoryNameSanitized = str_replace([' ', '/', '\\', ':', '*'], '_', strtolower($category));
            $imagePath = "/storage/uploads/category/{$categoryNameSanitized}.jpg";
            $category_imagePath_for_not_avaliable = "/storage/uploads/category/placeholder.jpg";

            if (file_exists(public_path($imagePath))) 
            {
                $categoryModel = CategoryModel::updateOrCreate([
                    'name' => $category,
                ], [
                    'image' => $imagePath,
                ]);
            }
            else 
            {
                $categoryModel = CategoryModel::updateOrCreate([
                    'name' => $category,
                ], [
                    'image' => $category_imagePath_for_not_avaliable,
                ]);
            }

            // Get the category ID for future use
            $category_id = $categoryModel->id;

            if (($sub_category != '')) 
            {
                // $subCategoryModel = SubCategoryModel::firstOrCreate([

                $subcategoryNameSanitized = str_replace([' ', '/', '\\', ':', '*'], '_', strtolower($sub_category));
                $subCategoryImagePath  = "/storage/uploads/category/{$subcategoryNameSanitized}.jpg";
                $sub_category_imagePath_for_not_avaliable = "/storage/uploads/sub_category/placeholder.jpg";

                if (file_exists(public_path($subCategoryImagePath))) 
                {
                    $subCategoryModel = SubCategoryModel::updateOrCreate([
                        'name' => $sub_category,
                        'category_id' => $category_id, // Include category_id in the search/creation criteria
                        'image' => $imagePath,
                    ]);
                }
                else 
                {
                    // Optionally continue without setting the image
                    $subCategoryModel = SubCategoryModel::updateOrCreate([
                        'name' => $sub_category,
                        'category_id' => $category_id,
                    ], [
                        'image' => $sub_category_imagePath_for_not_avaliable,
                    ]);
                }
            }

            if ($product_csv) 
            {
                // If product exists, update it
                $product_update_response = $product_csv->update([
                    'product_code' => $record_csv['Product Code'],
                    'product_name' => $record_csv['Product Name'],
                    'category' => $record_csv['Category'],
                    'sub_category' => $record_csv['Sub Category'],
                    'basic' => $basicPrice_product, // Ensure this is a valid number
                    'gst' => $gstPrice_prduct,     // Ensure this is a valid number
                    // 'product_image' => null, // Set this if you have the image URL or path
                    'product_image' => ('/storage/uploads/products/' . $filename . '.jpg'),
                ]);
            } 
            else 
            {
                // If product does not exist, create a new one
                $product_insert_response = ProductModel::create([
                    'sku' => $record_csv['SKU'],
                    'product_code' => $record_csv['Product Code'],
                    'product_name' => $record_csv['Product Name'],
                    'category' => $record_csv['Category'],
                    'sub_category' => $record_csv['Sub Category'],
                    'basic' => $basicPrice_product, // Ensure this is a valid number
                    'gst' => $gstPrice_prduct,     // Ensure this is a valid number
                    // 'product_image' => null, // Set this if you have the image URL or path
                    'product_image' => ('/storage/uploads/products/' . $filename. '.jpg'),
                ]);
            }
        }   
        if ($product_update_response == 1 || isset($product_insert_response)) {
            return response()->json(['message' => 'Products imported successfully'], 200);
        }
        else {
            return response()->json(['message' => 'Sorry, failed to imported successfully'], 400);
        }
    }

    public function importUser()
    {
        // URL of the CSV file from Google Sheets
        $get_product_user_url = 'https://docs.google.com/spreadsheets/d/1oF0yBLb2GjMhBep8ZpmmTYjJoW8d6AcajGDATEvqZaU/pub?gid=1797389278&single=true&output=csv';

        // Fetch the CSV content using file_get_contents
        $csvContent_user = file_get_contents($get_product_user_url);

        // Fetch and parse the CSV
        $csv_user = Reader::createFromString($csvContent_user);

        $csv_user->setHeaderOffset(0); // Set the header offset
        

        $records_user = (new Statement())->process($csv_user);

        $get_insert_response = null;
        $get_update_response = null;

        // Iterate through each record and create or update the product
        foreach ($records_user as $record_user) {

            if (strlen($record_user['Mobile']) == 10) {
                // If it's 10 digits, add '+91' prefix
                $mobile = '+91' . $record_user['Mobile'];
            } elseif (strlen($record_user['Mobile']) == 12) {
                // If it's 12 digits, add '+' prefix
                $mobile = '+' . $record_user['Mobile'];
            } else {
                $mobile = $record_user['Mobile'];
            }

            $user_csv = User::where('mobile', $mobile)->first();

            // Handle potential empty values for email, pincode, and markup
            $email_user = !empty($record_user['Email']) ? $record_user['Email'] : null;
            $pincode_user = $record_user['Pincode'] !== '' ? $record_user['Pincode'] : 0;
            $markup_user = $record_user['Mark Up'] !== '' ? $record_user['Mark Up'] : 0;

            if ($user_csv) 
            {
                // If user exists, update it
                $get_update_response = $user_csv->update([
                    'name' => $record_user['Name'],
                    'email' => $email_user,
                    'password' => bcrypt($mobile),
                    'otp' => null,
                    'expires_at' => null,
                    'address_line_1' => $record_user['Address Line 1'],
                    'address_line_2' => $record_user['Address Line 2'],
                    'city' => $record_user['City'],
                    'pincode' => $pincode_user,// Ensure this is a valid number
                    'gstin' => $record_user['GSTIN'],
                    'state' => $record_user['State'],
                    'country' => $record_user['Country'],
                    'markup' => $markup_user, // Ensure this is a valid number
                ]);
            } 
            else 
            {
                // If user does not exist, create a new one
                $get_insert_response = User::create([
                    'mobile' => $mobile,
                    'name' => $record_user['Name'],
                    'email' => $email_user,
                    'password' => bcrypt($mobile),
                    'otp' => null,
                    'expires_at' => null,
                    'address_line_1' => $record_user['Address Line 1'],
                    'address_line_2' => $record_user['Address Line 2'],
                    'city' => $record_user['City'],
                    'pincode' => $pincode_user,// Ensure this is a valid number
                    'gstin' => $record_user['GSTIN'],
                    'state' => $record_user['State'],
                    'country' => $record_user['Country'],
                    'markup' => $markup_user, // Ensure this is a valid number
                ]);
            }
        }   

        if ($get_update_response == 1 || isset($get_insert_response)) {
            return response()->json(['message' => 'Users imported successfully'], 200);
        }
        else {
            return response()->json(['message' => 'Sorry, failed to imported successfully'], 400);
        }
    }
}