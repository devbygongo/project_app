<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

use App\Models\ProductModel;

use App\Models\User;

use App\Models\OrderModel;

use App\Models\OrderItemsModel;

use App\Models\CartModel;

use App\Models\CounterModel;

use App\Models\CategoryModel;

use App\Models\SubCategoryModel;

class ViewController extends Controller
{
    //
    public function product()
    {
        // $get_product_details = ProductModel::select('SKU','product_code','product_name','category','sub_category','product_image','basic','gst','mark_up')->get();
        $get_product_details = ProductModel::select('product_code','product_name','category','sub_category','product_image','basic','gst')->get();
        

        if (isset($get_product_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_product_details
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function lng_product($lang = 'eng')
    {
        $get_product_details = ProductModel::select('product_code','product_name', 'name_in_hindi','name_in_telugu','category','sub_category','product_image','basic','gst')
                                            ->whereIn('type', ['MACHINE', 'ACCESSORIES'])
                                            ->get();
        
        $processed_prd_rec = $get_product_details->map(function($prd_rec) use ($lang)
        {
            $product_name = $prd_rec->product_name;

            if($lang === 'hin' && !empty($prd_rec->name_in_hindi))
            {
                $product_name = $prd_rec->name_in_hindi;
            }

            elseif ($lang === 'tlg' && !empty($prd_rec->name_in_telugu)) {
                $product_name = $prd_rec->name_in_telugu;
            }

            return [
                // 'SKU' => $prd_rec->SKU,
                'product_code' => $prd_rec->product_code,
                'product_name' => $product_name,
                'category' => $prd_rec->category,
                'sub_category' => $prd_rec->sub_category,
                'product_image' => $prd_rec->product_image,
                'basic' => $prd_rec->basic,
                'gst' => $prd_rec->gst,
            ];
        });


        if (isset($get_product_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $processed_prd_rec,
                'fetch_records' => count($processed_prd_rec)
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function get_product(Request $request)
    {
        // Retrieve offset and limit from the request with default values
        $offset = $request->input('offset', 0); // Default to 0 if not provided
        $limit = $request->input('limit', 10);  // Default to 10 if not provided
        $user_id = $request->input('user_id');  // Assuming the user ID is provided in the request

        // Ensure the offset and limit are integers and non-negative
        $offset = max(0, (int) $offset);
        $limit = max(1, (int) $limit);

        // Retrieve filter parameters if provided
        $search = $request->input('search', null);
        $category = $request->input('category', null);
        $subCategory = $request->input('sub_category', null);

        // Get the user type
		$user_type = User::select('type')->where('id', $user_id)->first();

		if ($user_type && $user_type->type == 'special') {
			// If user type is 'special', select special columns but alias them as 'basic' and 'gst'
			$query = ProductModel::select(
				'product_code', 
				'product_name', 
				'category', 
				'sub_category', 
				'product_image', 
				DB::raw('special_basic as basic'), 
				DB::raw('special_gst as gst'),
                'out_of_stock',
                'yet_to_launch'
			);
		}  else if ($user_type && $user_type->type == 'outstation') {
            // If user type is 'special', select special columns but alias them as 'basic' and 'gst'
            $query = ProductModel::select(
				'product_code', 
				'product_name', 
				'category', 
				'sub_category', 
				'product_image', 
				DB::raw('outstation_basic as basic'), 
				DB::raw('outstation_gst as gst'),
                'out_of_stock',
                'yet_to_launch'
			);
        } else {
			// Default columns for non-special users
			$query = ProductModel::select(
				'product_code', 
				'product_name', 
				'category', 
				'sub_category', 
				'product_image', 
				'basic', 
				'gst'
			);
		}

        // Apply search filter if provided
        if ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        }

        // Apply category filter if provided
        if ($category) {
            $query->where('category', $category);
        }

        // Apply sub-category filter if provided
        if ($subCategory) {
            $query->where('sub_category', $subCategory);
        }

        // Apply pagination
        $query->skip($offset)->take($limit);
        $get_products = $query->get();

        // Check if products are found
        if (isset($get_products) && !$get_products->isEmpty()) {

            // Loop through each product to check if it's in the cart
            foreach ($get_products as $product) {
                // Check if the product is in the user's cart
                $cart_item = CartModel::where('user_id', $user_id)
                    ->where('product_code', $product->product_code)
                    ->first();

                // If the product is in the cart, set cart details
                if ($cart_item) {
                    $product->in_cart = true;
                    $product->cart_quantity = $cart_item->quantity;
                    $product->cart_type = $cart_item->type;
                } else {
                    // If the product is not in the cart
                    $product->in_cart = false;
                    $product->cart_quantity = null;  // or 0, depending on your preference
                    $product->cart_type = null;
                }
            }

            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_products
            ], 200);

        } else {
            return response()->json([
                'message' => 'Failed to fetch data!',
            ], 404);
        }
    }

    public function lng_get_product(Request $request, $lang = 'eng')
    {
        // Retrieve input parameters with defaults
        $offset = max(0, (int) $request->input('offset', 0));
        $limit = max(1, (int) $request->input('limit', 10));
        // $user_id = $request->input('user_id');
        $search = $request->input('search', null);
        $category = $request->input('category', null);
        $subCategory = $request->input('sub_category', null);

        $get_user = Auth::User();

        if ($get_user->role == 'user') {
            $user_id = $get_user->id;
            // Update the app_status column
            User::where('id', $user_id)->update(['app_status' => 1]);
        } else {
            $request->validate([
                'user_id' => 'required',
            ]);
            $user_id = $request->input('user_id');
        }

        // Get the user type
		$user_type = User::select('type')->where('id', $user_id)->first();

		if ($user_type && $user_type->type == 'special') {
			// If user type is 'special', select special columns but alias them as 'basic' and 'gst'
			$query = ProductModel::select(
				'product_code', 
				'product_name', 
				'category', 
				'sub_category', 
				'product_image', 
				DB::raw('special_basic as basic'), 
				DB::raw('special_gst as gst'),
                'out_of_stock',
                'yet_to_launch'
			);
		} else if ($user_type && $user_type->type == 'outstation') {
                // If user type is 'special', select special columns but alias them as 'basic' and 'gst'
                $query = ProductModel::select(
                    'product_code', 
                    'product_name', 
                    'category', 
                    'sub_category', 
                    'product_image', 
                    DB::raw('outstation_basic as basic'), 
                    DB::raw('outstation_gst as gst'),
                    'out_of_stock',
                    'yet_to_launch'
                );
        } else if ($user_type && $user_type->type == 'zeroprice') {


            // If user type is 'special', select special columns but alias them as 'basic' and 'gst'
            $query = ProductModel::select(
                'product_code', 
                'product_name', 
                'category', 
                'sub_category', 
                'product_image', 
                DB::raw('0 as basic'), 
                DB::raw('0 as gst'), 
                'out_of_stock',
                'yet_to_launch'
            );

        } else {
			// Default columns for non-special users
			$query = ProductModel::select(
				'product_code', 
				'product_name', 
				'category', 
				'sub_category', 
				'product_image', 
				'basic', 
				'gst',
                'out_of_stock',
                'yet_to_launch'
			);
		}

        // Apply filters
        if ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        }
        if ($category) {
            $query->where('category', $category);
        }
        if ($subCategory) {
            $query->where('sub_category', $subCategory);
        }

        // Apply pagination and get products
        $get_products = $query->skip($offset)->take($limit)->get();

        // Process products for language and cart details
        $processed_prd_lang_rec = $get_products->map(function ($prd_rec) use ($lang, $user_id) {
            // Set product name based on the selected language
            $product_name = $prd_rec->product_name;
            if ($lang === 'hin' && !empty($prd_rec->name_in_hindi)) {
                $product_name = $prd_rec->name_in_hindi;
            } elseif ($lang === 'tlg' && !empty($prd_rec->name_in_telugu)) {
                $product_name = $prd_rec->name_in_telugu;
            }

            // Check if the product is in the user's cart
            $cart_item = CartModel::where('user_id', $user_id)
                ->where('product_code', $prd_rec->product_code)
                ->first();

            // Return processed product data
            return [
                // 'SKU' => $prd_rec->SKU,
                'product_code' => $prd_rec->product_code,
                'product_name' => $product_name,
                'category' => $prd_rec->category,
                'sub_category' => $prd_rec->sub_category,
                'product_image' => $prd_rec->product_image,
                'basic' => $prd_rec->basic,
                'gst' => $prd_rec->gst,
                'out_of_stock' => $prd_rec->out_of_stock,
                'yet_to_launch' => $prd_rec->yet_to_launch,
                'in_cart' => $cart_item ? true : false,
                'cart_quantity' => $cart_item->quantity ?? null,
                'cart_type' => $cart_item->type ?? null,
                'cart_remarks' => $cart_item->remarks ?? null,
            ];
        });

        // Return response based on the result
        return $processed_prd_lang_rec->isEmpty()
        ? response()->json(['Failed to fetch data!'], 404)
        : response()->json(['message' => 'Fetch data successfully!',
                'data' => $processed_prd_lang_rec,
                'count' => count($processed_prd_lang_rec)], 200);
    }

    public function get_spares(Request $request, $lang = 'eng', $code = null)
    {
        $get_user = Auth::User();

        if ($get_user->role == 'user') {
            $user_id = $get_user->id;

            // Fetch user type
            $user_type = User::select('type')->where('id', $user_id)->first();
        }
        else{
            // $request->validate([
            //     'user_id' => 'required',
            // ]);
            // $user_id = $request->input('user_id');
            // $user_type = User::select('type')->where('id', $user_id)->first();
            $user_type = (object) ['type' => 'normal'];
        }

        // Base query for products
        $productQuery = ProductModel::select('product_code', 'product_name', 'name_in_hindi', 'name_in_telugu', 'category', 'sub_category', 'product_image', 'out_of_stock', 'yet_to_launch');

        // Add pricing columns dynamically based on user type
        if ($user_type && $user_type->type == 'special') {
            $productQuery->addSelect(
                DB::raw('special_basic as basic'), 
                DB::raw('special_gst as gst')
            );
        } elseif ($user_type && $user_type->type == 'outstation') {
            $productQuery->addSelect(
                DB::raw('outstation_basic as basic'), 
                DB::raw('outstation_gst as gst')
            );
        } elseif ($user_type && $user_type->type == 'zeroprice') {
            $productQuery->addSelect(
                DB::raw('0 as basic'), 
                DB::raw('0 as gst')
            );
        } else {
            $productQuery->addSelect('basic', 'gst');
        }

        // Filter products by type and optionally machine part number
        $productQuery->where('type', 'SPARE');

        if ($code !== null) {
            $productQuery->where('machine_part_no', 'like', "%{$code}%");
        }

        // Execute query
        $get_spare_product = $productQuery->get();

        // Map the results for the response
        $spare_prd_rec = $get_spare_product->map(function ($spare_prd_rec) use ($lang) {
            $product_name = $spare_prd_rec->product_name;

            // Language-specific product names
            if ($lang === 'hin' && !empty($spare_prd_rec->name_in_hindi)) {
                $product_name = $spare_prd_rec->name_in_hindi;
            } elseif ($lang === 'tlg' && !empty($spare_prd_rec->name_in_telugu)) {
                $product_name = $spare_prd_rec->name_in_telugu;
            }

            return [
                'product_code' => $spare_prd_rec->product_code,
                'product_name' => $product_name,
                'category' => $spare_prd_rec->category,
                'sub_category' => $spare_prd_rec->sub_category,
                'product_image' => $spare_prd_rec->product_image,
                'basic' => $spare_prd_rec->basic,
                'gst' => $spare_prd_rec->gst,
                'out_of_stock' => $spare_prd_rec->out_of_stock,
                'yet_to_launch' => $spare_prd_rec->yet_to_launch,
            ];
        });

        // Return response
        return isset($spare_prd_rec) && $spare_prd_rec->isNotEmpty()
            ? response()->json(['message' => 'Fetch data successfully!', 'data' => $spare_prd_rec, 'fetch_records' => count($spare_prd_rec)], 200)
            : response()->json(['message' => 'Failed to get data'], 404);
    }


    public function categories()
    {
        // Fetch all categories with their product count
        $categories = CategoryModel::withCount('get_products')->get();

        // Filter and format the categories data for a JSON response
		$formattedCategories = $categories->map(function ($category) {
			// Only include categories with products_count > 0
			if ($category->get_products_count > 0) {
				return [
					'category_id' => $category->id,
					'category_name' => $category->name,
					'category_image' => $category->image,
					'products_count' => $category->get_products_count,
				];
			}
			return null; // Return null for categories with 0 products
		})->filter(); // Remove null values

        if (isset($formattedCategories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $formattedCategories,
                'count' => count($formattedCategories),
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function sub_categories($category = null)
    {
        // Convert the string of category IDs to an array, e.g., '1,2' -> [1, 2]
        $categoryIds = $category ? explode(',', $category) : [];

        // // Fetch subcategories filtered by category_id if provided
        // $sub_categories = SubCategoryModel::withCount('products')
        // ->when($category, function ($query, $category) {
        //     // Filter subcategories by the category_id if a category is provided
        //     return $query->where('category_id', $category);
        // })->get();

        // Fetch subcategories filtered by multiple category_ids if provided
        $sub_categories = SubCategoryModel::withCount('products')
        ->when(!empty($categoryIds), function ($query) use ($categoryIds) {
            // Filter subcategories by multiple category_ids using whereIn
            return $query->whereIn('category_id', $categoryIds);
        })->get();

        // Format the categories data for a JSON response
        $formattedSubCategories = $sub_categories->map(function ($sub_category) {
            return [
                'sub_category_name' => $sub_category->name,
                'sub_category_image' => $sub_category->image,
                'sub_products_count' => $sub_category->products_count,
            ];
        });
        
        if (isset($formattedSubCategories)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $formattedSubCategories,
                'count' => count($formattedSubCategories),
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 400);
        }    
    }

    public function lng_sub_categories($category = null, $lang = 'eng')
    {
        $categoryIds = $category ? explode(',', $category) : [];

        // Fetch subcategories filtered by multiple category_ids if provided
        $sub_categories = SubCategoryModel::withCount('products')
        ->when(!empty($categoryIds), function ($query) use ($categoryIds) 
        {
            // Filter subcategories by multiple category_ids using whereIn
            return $query->whereIn('category_id', $categoryIds);
        })
        ->get();

        // Format the subcategories data for a JSON response
        $formattedSubCategories = $sub_categories->map(function ($sub_category) use ($lang) 
        {
            // Set the sub-category name based on the selected language
            $sub_category_name = $sub_category->name; // Default to English

            if ($lang === 'hin' && !empty($sub_category->name_in_hindi)) {
                $sub_category_name = $sub_category->name_in_hindi;
            } elseif ($lang === 'tlg' && !empty($sub_category->name_in_telugu)) {
                $sub_category_name = $sub_category->name_in_telugu;
            }

            return [
                'sub_category_name' => $sub_category_name,
                'sub_category_image' => $sub_category->image,
                'sub_products_count' => $sub_category->products_count,
            ];
        });
        
        return $formattedSubCategories->isEmpty()
        ? response()->json(['Failed get data successfully!'], 404)
        : response()->json(['message' => 'Fetch data successfully!',
                'data' => $formattedSubCategories,
                'count' => count($formattedSubCategories)], 200);
    }

    public function lng_categories($lang = 'eng')
	{
		// Fetch all categories with their product count
		$categories = CategoryModel::withCount('get_products')->get();

		// Format and filter the categories data for a JSON response
		$formattedCategories = $categories->map(function ($category) use ($lang) {
			$category_name = $category->name;

			// Set category name based on language
			if ($lang === 'hin' && !empty($category->name_in_hindi)) {
				$category_name = $category->name_in_hindi;
			} elseif ($lang === 'tlg' && !empty($category->name_in_telugu)) {
				$category_name = $category->name_in_telugu;
			}

			// Return category details if products count > 0, otherwise return null
			return $category->get_products_count > 0 ? [
				'category_id' => $category->id,
				'category_name' => $category_name,
				'category_image' => $category->image,
				'products_count' => $category->get_products_count,
			] : null;
		})->filter(); // Filter out null values

		// Check if the filtered categories are empty and return response
		return $formattedCategories->isEmpty()
			? response()->json(['message' => 'No categories with products found!'], 404)
			: response()->json([
				'message' => 'Fetch data successfully!',
				'data' => $formattedCategories->values(), // Re-index filtered array
				'count' => $formattedCategories->count(),
			], 200);
	}


    public function user($lang = 'eng')
    {
        $get_user_details = User::select('id', 'name', 'name_in_hindi', 'name_in_telugu', 'email', 'mobile', 'role', 'address_line_1', 'address_line_2', 'city', 'pincode', 'gstin', 'state', 'country', 'is_verified', 'type', 'app_status')
                                ->where('role', 'user')->orderBy('updated_at', 'desc')
                                ->get();
    
        $processed_rec_user = $get_user_details->map(function ($record) use ($lang) {
            $name = $record->name;
    
            if ($lang == 'hin' && !empty($record->name_in_hindi)) {
                $name = $record->name_in_hindi;
            } elseif ($lang == 'tlg' && !empty($record->name_in_telugu)) {
                $name = $record->name_in_telugu;
            }
    
            return [
                'id' => $record->id,
                'name' => $name,
                'email' => $record->email,
                'mobile' => $record->mobile,
                'city' => $record->city,
                'role' => ucfirst($record->role),
                'address' => implode(', ', array_filter([$record->address_line_1, $record->address_line_2, $record->city, $record->state, $record->pincode, $record->country])),
                'gstin' => $record->gstin,
                'type' => $record->type,
                'app_status' => $record->app_status,
                'verified' => $record->is_verified,
            ];
        });
    
        $types = [
            ['value' => 'normal', 'name' => 'Normal'],
            ['value' => 'special', 'name' => 'Special'],
            ['value' => 'outstation', 'name' => 'Outstation'],
            ['value' => 'zeroprice', 'name' => 'Zero Price'],
        ];
    
        return $processed_rec_user->isEmpty()
            ? response()->json(['Failed get data successfully!'], 404)
            : response()->json(['Fetch data successfully!', 'data' => $processed_rec_user, 'types' => $types], 200);
    }

    public function find_user($search = null)
    {   
        if ($search == null) {
            $get_user_details = User::select('id','name','email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country')
                                ->get();     
        }
        else {
            $get_user_details = User::select('id','name','email','mobile','role','address_line_1','address_line_2','city','pincode','gstin','state','country')
                                ->where('name', $search)
                                ->orWhere('mobile', $search)
                                ->get();     
        }

        if (isset($get_user_details) && (!$get_user_details->isEmpty())) {
            return response()->json([
                'message' => 'Fetch record successfully!',
                'data' => $get_user_details
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function user_details()
    {
        $get_user_id = Auth::id();
        
        $get_user_details = User::select('id','name','email','mobile','address_line_1','address_line_2','city','pincode','gstin','state','country')->where('id', $get_user_id)->get();
        

        if (isset($get_user_details)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_user_details
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function orders()
    {
        $get_all_orders = OrderModel::with('user')->orderBy('created_at', 'desc')->get();

        if (isset($get_all_orders)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_all_orders
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function orders_user_id(Request $request, $id = null)
    {
        $get_user = Auth::User();

        if ($get_user->role == 'user') {
            $id = $get_user->id;
        } else {
            $request->validate([
                'user_id' => 'required',
            ]);
            $id = $request->input('user_id');
        }

        // Fetch all orders and their associated order items with product image
        $get_user_orders = OrderModel::when($id, function ($query, $id) {
            return $query->where('user_id', $id);
        })
        ->with(['order_items' => function($query) {
            // Eager load product relationship and append the product_image field
            $query->with('product:id,product_code,product_image');
        }])
        ->orderBy('created_at', 'desc')
        ->get();

        // Modify the order items to append the product image directly
        $get_user_orders->each(function($order) {
            $order->order_items->each(function($orderItem) {
                $orderItem->product_image = $orderItem->product->product_image ?? null;
                unset($orderItem->product); // Remove the product object after extracting the image
            });
        });

        if ($get_user_orders->isEmpty()) {
            return response()->json([
                'message' => 'Sorry, no data available!',
            ], 404);
        } else {
            return response()->json([
                'message' => 'Fetched data successfully!',
                'data' => $get_user_orders
            ], 200);
        }
    }

    public function order_items()
    {
        $get_all_order_items = OrderItemsModel::with('get_orders')->get();
        

        if (isset($get_all_order_items)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_all_order_items
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function orders_items_order_id($id)
    {
        $get_items_for_orders = OrderItemsModel::where('orderID', $id)->get();
        // $get_items_for_orders = OrderItemsModel::where('order_id', $id)
        // ->join()
        // ->get();
        

        if (isset($get_items_for_orders)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_items_for_orders
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function cart()
    {
        // Retrieve all records with their associated user and product data
        $get_all_cart_records = CartModel::with(['get_users', 'get_products'])->get();
        

        // Transform the data if needed
        $formattedData = $get_all_cart_records->map(function ($item) {
			
            return [
                'id' => $item->id, // Adjust as necessary
                'user' => $item->get_users ? [
                    'id' => $item->get_users->id,
                    'name' => $item->get_users->name, // Adjust fields as necessary
                ] : null,
                'product' => $item->get_products ? [
                    'product_code' => $item->get_products->product_code,
                    'name' => $item->get_products->product_name, // Adjust fields as necessary
                ] : null,
            ];
        });
        if (isset($formattedData)) {
            return response()->json([
                'message' => 'Fetch all recods successfully!',
                'data' => $formattedData
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed fetch records successfully!',
            ], 400);
        }    
    }

    public function cart_user($id = null)
    {
        $get_user = Auth::User();

        if ($get_user->role == 'admin') {

            $user_type = User::select('type')->where('id', $id)->first();
            $basic_column = 'basic';
            $gst_column = 'gst';

            if ($user_type && $user_type->type == 'special') {
                $basic_column = DB::raw('special_basic as basic');
                $gst_column = DB::raw('special_gst as gst');
            } elseif ($user_type && $user_type->type == 'outstation') {
                $basic_column = DB::raw('outstation_basic as basic');
                $gst_column = DB::raw('outstation_gst as gst');
            } elseif ($user_type && $user_type->type == 'zeroprice') {
                $basic_column = DB::raw('0 as basic');
                $gst_column = DB::raw('0 as gst');
            }
            
            $get_items_for_user = CartModel::where('t_cart.user_id', $id)
                ->join('t_products', 't_cart.product_code', '=', 't_products.product_code')
                ->select(
                    't_cart.id',
                    't_cart.user_id',
                    't_cart.product_code',
                    't_cart.product_name',
                    't_cart.rate',
                    't_cart.quantity',
                    't_cart.amount',
                    't_cart.type',
                    't_cart.remarks',
                    't_cart.created_at',
                    't_cart.updated_at',
                    't_products.product_image',
                    $basic_column,
                    $gst_column
                )
                ->get();

            $cart_data_count = count($get_items_for_user);
        } else {

            $user_type = User::select('type')->where('id', $get_user->id)->first();
            $basic_column = 'basic';
            $gst_column = 'gst';

            if ($user_type && $user_type->type == 'special') {
                $basic_column = DB::raw('special_basic as basic');
                $gst_column = DB::raw('special_gst as gst');
            } elseif ($user_type && $user_type->type == 'outstation') {
                $basic_column = DB::raw('outstation_basic as basic');
                $gst_column = DB::raw('outstation_gst as gst');
            } elseif ($user_type && $user_type->type == 'zeroprice') {
                $basic_column = DB::raw('0 as basic');
                $gst_column = DB::raw('0 as gst');
            }
            
            $get_items_for_user = CartModel::where('t_cart.user_id', $get_user->id)
                ->join('t_products', 't_cart.product_code', '=', 't_products.product_code')
                ->select(
                    't_cart.id',
                    't_cart.user_id',
                    't_cart.product_code',
                    't_cart.product_name',
                    't_cart.rate',
                    't_cart.quantity',
                    't_cart.amount',
                    't_cart.type',
                    't_cart.remarks',
                    't_cart.created_at',
                    't_cart.updated_at',
                    't_products.product_image',
                    $basic_column,
                    $gst_column
                )
                ->get();
        }


    
        if (isset($get_items_for_user)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_items_for_user,
                'record count' => count($get_items_for_user)
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function counter()
    {
        $get_counter_records = CounterModel::all();
        
        if (isset($get_counter_records)) {
            return response()->json([
                'message' => 'Fetch data successfully!',
                'data' => $get_counter_records
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Failed get data successfully!',
            ], 404);
        }    
    }

    public function dashboard_details()
    {
        $get_product_numbers = ProductModel::count();
        $get_user_numbers = User::count();
        $get_order_numbers = OrderModel::count();

        $get_dashboard_records = array([
            'total_users' => $get_user_numbers,
            'total_products' => $get_product_numbers,
            'total_orders' => $get_order_numbers,
        ]);
        
        if (isset($get_dashboard_records)) {
            return response()->json([
                'message' => 'Fetch records successfully!',
                'data' => $get_dashboard_records
            ], 200);
        }

        else {
            return response()->json([
                'message' => 'Sorry, failed get records',
            ], 404);
        }    
    }

    public function return_order($orderId)
    {
        // \DB::enableQueryLog();
        $get_order_details = OrderModel::with('order_items')
                                        ->where('id', $orderId)
                                        ->get();
                                        // dd(\DB::getQueryLog());


        if ($get_order_details) 
        {
            if ($get_order_details[0]->type == 'Basic') {
                $get_invoice_id = CounterModel::where('name', 'invoice_basic')
                                                ->get();

                $return_invoice_id = $get_invoice_id[0]->prefix.$get_invoice_id[0]->counter.$get_invoice_id[0]->postfix;
            }
            else {
                $get_invoice_id = CounterModel::where('name', 'invoice_basic')
                ->get();

                $return_invoice_id = $get_invoice_id[0]->prefix.$get_invoice_id[0]->counter.$get_invoice_id[0]->postfix;
            }

            $formatted_order_record = 
            [
                'id' => $get_order_details[0]->id,
                'order_id' => $get_order_details[0]->order_id,
                'user_id' => $get_order_details[0]->user_id,
                'order_date' => $get_order_details[0]->order_date ? $get_order_details[0]->order_date : null,
                'amount' => $get_order_details[0]->amount,
                'status' => $get_order_details[0]->status,
                'type' => ucfirst($get_order_details[0]->type),
                'order_invoice' => $get_order_details[0]->order_invoice,
                'order_invoice_id' => $return_invoice_id,
                'order_items' => $get_order_details[0]->order_items->map(function ($item) {
                    return 
                    [
                        'product_code' => $item->product_code,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'rate' => $item->rate,
                        'type' => ucfirst($item->type ?? '')  
                    ];
                })->toArray()
            ];
        }                                                                    
        

        if (empty($formatted_order_record)) 
        {
            return response()->json(['message' => 'Failed to get order records!'], 400);
        } 
        else 
        {
            return response()->json([
                'message' => 'Fetch records successfully!',
                'data' => $formatted_order_record
            ], 200);
        }
    }

    // return blade file
    
    public function login_view()
    {
        return view('login');
    }

    public function user_view()
    {
        return view('view_user');
    }
}