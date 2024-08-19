<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\ProductModel;

use App\Models\User;

use League\Csv\Reader;

use League\Csv\Statement;

class CsvImportController extends Controller
{
    //
    public function importProduct()
    {
        dd("iii");
        // URL of the CSV file from Google Sheets
        $get_csv_url = 'https://docs.google.com/spreadsheets/d/1oF0yBLb2GjMhBep8ZpmmTYjJoW8d6AcajGDATEvqZaU/pub?gid=0&single=true&output=csv';

        // Fetch and parse the CSV
        $csv = Reader::createFromPath($get_csv_url, 'r');
        $csv->setHeaderOffest(1); // Set the header offset

        $records = (new Statement())->process($csv);

        // Iterate through each record and create or update the product
        foreach ($records as $record) {
            $product = Product::where('sku', $record['SKU'])->first();

            if ($product) 
            {
                // If product exists, update it
                $product->update([
                    'product_code' => $record['Product Code'],
                    'product_name' => $record['Product Name'],
                    'category' => $record['Category'],
                    'sub_category' => $record['Sub Category'],
                    'basic' => $record['Basic Price'],
                    'gst' => $record['GST Price'],
                    'product_image' => null, // Set this if you have the image URL or path
                ]);
            } 
            else 
            {
                // If product does not exist, create a new one
                Product::create([
                    'sku' => $record['SKU'],
                    'product_code' => $record['Product Code'],
                    'product_name' => $record['Product Name'],
                    'category' => $record['Category'],
                    'sub_category' => $record['Sub Category'],
                    'basic' => $record['Basic Price'],
                    'gst' => $record['GST Price'],
                    'product_image' => null, // Set this if you have the image URL or path
                ]);
            }
        }   
    }
}
