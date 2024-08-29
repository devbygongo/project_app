<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProductModel;

use App\Models\User;

use League\Csv\Reader;

use League\Csv\Statement;

use Hash;

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

        // Iterate through each record and create or update the product
        foreach ($records_csv as $record_csv) {
            $product_csv = ProductModel::where('sku', $record_csv['SKU'])->first();

            $basicPrice_product = $record_csv['Basic Price'] !== '' ? $record_csv['Basic Price'] : 0;
            $gstPrice_prduct = $record_csv['GST Price'] !== '' ? $record_csv['GST Price'] : 0;
            $filename = $record_csv['Product Code'];

            if ($product_csv) 
            {
                // If product exists, update it
                $product_csv->update([
                    'product_code' => $record_csv['Product Code'],
                    'product_name' => $record_csv['Product Name'],
                    'category' => $record_csv['Category'],
                    'sub_category' => $record_csv['Sub Category'],
                    'basic' => $basicPrice_product, // Ensure this is a valid number
                    'gst' => $gstPrice_prduct,     // Ensure this is a valid number
                    // 'product_image' => null, // Set this if you have the image URL or path
                    'product_image' => ('storage/uploads/products/' . $filename . '.jpg'),
                ]);
            } 
            else 
            {
                // If product does not exist, create a new one
                ProductModel::create([
                    'sku' => $record_csv['SKU'],
                    'product_code' => $record_csv['Product Code'],
                    'product_name' => $record_csv['Product Name'],
                    'category' => $record_csv['Category'],
                    'sub_category' => $record_csv['Sub Category'],
                    'basic' => $basicPrice_product, // Ensure this is a valid number
                    'gst' => $gstPrice_prduct,     // Ensure this is a valid number
                    // 'product_image' => null, // Set this if you have the image URL or path
                    'product_image' => ('storage/uploads/products/' . $filename. '.jpg'),
                ]);
            }
        }   
        return response()->json(['message' => 'Products imported successfully'], 200);
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

        // Iterate through each record and create or update the product
        foreach ($records_user as $record_user) {
            $user_csv = User::where('mobile', $record_user['Mobile '])->first();

            // Handle potential empty values for email, pincode, and markup
            $email_user = !empty($record_user['Email']) ? $record_user['Email'] : null;
            $pincode_user = $record_user['Pincode'] !== '' ? $record_user['Pincode'] : 0;
            $markup_user = $record_user['Mark Up'] !== '' ? $record_user['Mark Up'] : 0;

            if ($user_csv) 
            {
                // If product exists, update it
                $user_csv->update([
                    'name' => $record_user['Name'],
                    'email' => $email_user,
                    'password' => bcrypt($record_user['Mobile ']),
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
                // If product does not exist, create a new one
                User::create([
                    'mobile' => $record_user['Mobile '],
                    'name' => $record_user['Name'],
                    'email' => $email_user,
                    'password' => bcrypt($record_user['Mobile ']),
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
        return response()->json(['message' => 'Users imported successfully'], 200);
    }
}