<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    // Defined cache duration (10 minutes)
    protected $cacheDuration = 600; // 10 minutes in seconds

    // Fetch data from external API
    private function fetchProducts()
    {
        // Check if data is cached
        if (Cache::has('products')) {
            return Cache::get('products');
        }

        // Fetch data from external API if not cached
        //$response = Http::get('https://dummyjson.com/products');

        //disable the SSL
        $response = Http::withOptions(['verify' => false])->get('https://dummyjson.com/products');

        // Handle failed requests
        if ($response->failed()) {
            return response()->json(['error' => 'Failed to retrieve data from external API'], 500);
        }

        // Cache the data for 10 minutes
        $products = $response->json();
        Cache::put('products', $products, $this->cacheDuration);

        return $products;
    }

    // Show the list of all products
    public function list()
    {
        $products = $this->fetchProducts();

        // Check for any API fetch errors
        if (!is_array($products)) {
            // Return error response from fetchProducts
            return $products; 
        }

        return response()->json($products);
    }

    // Search products by name (case-insensitive, partial match)
    public function search(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1',
        ]);

        $products = collect($this->fetchProducts()['products']);
        $keyword = strtolower($request->input('name'));

        $filtered = $products->filter(function ($product) use ($keyword) {
            return strpos(strtolower($product['title']), $keyword) !== false;
        });

        return response()->json($filtered->values(), 200);
    }

    // Filter products by category and price range
    public function filter(Request $request)
    {
        $request->validate([
            'category' => 'required|string|min:1',
            'min_price' => 'numeric|min:0',
            'max_price' => 'numeric|min:0|gte:min_price',
        ]);

        $products = collect($this->fetchProducts()['products']);
        $category = strtolower($request->input('category'));
        $minPrice = $request->input('min_price', 0);
        $maxPrice = $request->input('max_price', PHP_INT_MAX);

        $filtered = $products->where('category', $category)
            ->whereBetween('price', [$minPrice, $maxPrice]);

        return response()->json($filtered->values(), 200);
    }

    // Sort products by various fields (ascending or descending)
    public function sort(Request $request)
    {
        $request->validate([
            'sort_by' => 'required|in:price,title',
            'order' => 'required|in:asc,desc',
        ]);

        $products = collect($this->fetchProducts()['products']);
        $sortBy = $request->input('sort_by', 'price');
        $order = $request->input('order', 'asc');

        $sorted = $products->sortBy($sortBy, SORT_REGULAR, $order == 'desc');

        return response()->json($sorted->values(), 200);
    }

    // Get product details by ID
    public function show($id)
    {
        $products = collect($this->fetchProducts()['products']);
        $product = $products->where('id', $id)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        return response()->json($product, 200);
    }

    // Update product price locally
    public function updatePrice(Request $request, $id)
    {
        // Validate price input
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $products = collect($this->fetchProducts()['products']);
        $product = $products->where('id', $id)->first();

        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }

        // Update price locally (not in external API)
        $product['price'] = $request->input('price');

        return response()->json($product, 200);
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

        $products = collect($this->fetchProducts()['products']);
        $name = strtolower($request->input('name'));
        $category = strtolower($request->input('category'));
        $minPrice = $request->input('min_price', 0);
        $maxPrice = $request->input('max_price', PHP_INT_MAX);
        $sortBy = $request->input('sort_by', 'price');
        $order = $request->input('order', 'asc');

        $filtered = $products->filter(function ($product) use ($name, $category, $minPrice, $maxPrice) {
            return (!$name || strpos(strtolower($product['title']), $name) !== false)
                && (!$category || $product['category'] === $category)
                && $product['price'] >= $minPrice
                && $product['price'] <= $maxPrice;
        });

        $sorted = $filtered->sortBy($sortBy, SORT_REGULAR, $order == 'desc');

        return response()->json($sorted->values(), 200);
    }

    //Definition of bulk operation for update
    public function bulkUpdate(Request $request)
{
    $request->validate([
        'updates' => 'required|array',
        'updates.*.id' => 'required|integer',
        'updates.*.price' => 'nullable|numeric|min:0',
        'updates.*.category' => 'nullable|string|min:1',
    ]);

    $products = collect($this->fetchProducts()['products']);
    $updates = $request->input('updates');

    foreach ($updates as $update) {
        $product = $products->where('id', $update['id'])->first();

        if ($product) {
            if (isset($update['price'])) {
                $product['price'] = $update['price'];
            }
            if (isset($update['category'])) {
                $product['category'] = $update['category'];
            }
        }
    }

    // Return the updated products
    return response()->json($products->values(), 200);
}

}
