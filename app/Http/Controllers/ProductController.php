<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use App\Models\Product;

class ProductController extends Controller
{
   // Fetch data from external API
   private function fetchProducts()
   {
       // Check if products already exist in the database
       if (Product::count() > 0) {
           Log::info('Fetched products from the database.', ['count' => Product::count()]);
           return Product::all(); // Fetch products from the database
       }

       // Fetch data from external API
       $response = Http::withOptions(['verify' => false])->get('https://dummyjson.com/products');

       // Handle failed requests
       if ($response->failed()) {
           Log::error('Failed to retrieve data from external API.', ['status' => $response->status(), 'body' => $response->body()]);
           return response()->json(['error' => 'Failed to retrieve data from external API'], 500);
       }

       // Extract the products array from the API response
       $products = $response->json()['products'];

       // Log the number of products fetched from the API
       Log::info('Fetched products from external API.', ['count' => count($products)]);

       // Insert only the selected fields into the database
       foreach ($products as $product) {
           try {
               Product::create([
                   'title' => $product['title'],
                   'price' => $product['price'],
                   'description' => $product['description'],
                   'category' => $product['category'],
               ]);
           } catch (\Exception $e) {
               // Log any errors that occur during insertion
               Log::error('Error inserting product into database.', [
                   'error' => $e->getMessage(),
                   'product' => $product
               ]);
           }
       }

       // Return all products from the database
       return Product::all();
   }


   public function list(Request $request)
   {
       // Fetch products from the database or external API
       $products = $this->fetchProducts(); // Call the fetch method here
   
       // Pagination settings
       $page = $request->input('page', 1); // Default to page 1
       $perPage = $request->input('per_page', 10); // Default to 10 items per page
   
       // Paginate the products
       $paginatedProducts = new LengthAwarePaginator(
           $products->forPage($page, $perPage),
           $products->count(),
           $perPage,
           $page,
           ['path' => $request->url(), 'query' => $request->query()]
       );
   
       // Return the paginated data as JSON
       return response()->json($paginatedProducts);
   }
   

    // Search products by name (case-insensitive, partial match)
    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1',
        ]);

        $keyword = strtolower($request->input('name'));

        // Search for products using a database query
        $products = Product::where('title', 'like', '%' . $keyword . '%')->get();

        return response()->json($products);
    }

    // Filter products by category and price range
    public function filter(Request $request)
    {
        $request->validate([
            'category' => 'required|string|min:1',
            'min_price' => 'numeric|min:0',
            'max_price' => 'numeric|min:0|gte:min_price',
        ]);

        $category = strtolower($request->input('category'));
        $minPrice = $request->input('min_price', 0);
        $maxPrice = $request->input('max_price', PHP_INT_MAX);

        // Filter using a database query
        $products = Product::where('category', $category)
            ->whereBetween('price', [$minPrice, $maxPrice])
            ->get();

        return response()->json($products);
    }

    // Sort products by various fields (ascending or descending)
    public function sort(Request $request)
    {
        $request->validate([
            'sort_by' => 'required|in:price,title',
            'order' => 'required|in:asc,desc',
        ]);

        $sortBy = $request->input('sort_by', 'price');
        $order = $request->input('order', 'asc');

        // Sort using a database query
        $products = Product::orderBy($sortBy, $order)->get();

        return response()->json($products);
    }

    // Get product details by ID
    public function show($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    // Update product price
    public function updatePrice(Request $request, $id)
    {
        // Validate price input
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Update the price
        $product->price = $request->input('price');
        $product->save();

        return response()->json($product);
    }

    // Complex query (search, filter, sort)
    public function complexQuery(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|min:1',
            'category' => 'nullable|string|min:1',
            'min_price' => 'numeric|min:0',
            'max_price' => 'numeric|min:0|gte:min_price',
            'sort_by' => 'nullable|in:price,title',
            'order' => 'nullable|in:asc,desc',
        ]);

        $query = Product::query();

        // Apply search
        if ($request->filled('name')) {
            $query->where('title', 'like', '%' . strtolower($request->input('name')) . '%');
        }

        // Apply category filter
        if ($request->filled('category')) {
            $query->where('category', strtolower($request->input('category')));
        }

        // Apply price range filter
        $query->whereBetween('price', [
            $request->input('min_price', 0),
            $request->input('max_price', PHP_INT_MAX)
        ]);

        // Apply sorting
        if ($request->filled('sort_by') && $request->filled('order')) {
            $query->orderBy($request->input('sort_by'), $request->input('order'));
        }

        // Get the filtered, sorted results
        $products = $query->get();

        return response()->json($products);
    }
}
