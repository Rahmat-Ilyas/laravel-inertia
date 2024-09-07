<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::when(request()->q, function ($products) {
            $products = $products->where('title', 'like', '%' . request()->q . '%');
        })->latest()->paginate(5);
        
        return Inertia::render('Apps/Products/Index', [
            'products' => $products,
        ]);
    }

    public function create()
    {
        $categories = Category::all();
        return Inertia::render('Apps/Products/Create', [
            'categories' => $categories
        ]);
    }

    public function edit(Product $product)
    {
        $categories = Category::all();

        return Inertia::render('Apps/Products/Edit', [
            'product' => $product,
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'image'         => 'required|image|mimes:jpeg,jpg,png|max:2000',
            'barcode'       => 'required|unique:products',
            'title'         => 'required',
            'description'   => 'required',
            'category_id'   => 'required',
            'buy_price'     => 'required',
            'sell_price'    => 'required',
            'stock'         => 'required',
        ]);
        
        $image = $request->file('image');
        $image->storeAs('public/products', $image->hashName());
        
        Product::create([
            'image'         => $image->hashName(),
            'barcode'       => $request->barcode,
            'title'         => $request->title,
            'description'   => $request->description,
            'category_id'   => $request->category_id,
            'buy_price'     => $request->buy_price,
            'sell_price'    => $request->sell_price,
            'stock'         => $request->stock,
        ]);
        
        return redirect()->route('apps.products.index');
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'barcode'       => 'required|unique:products,barcode,' . $product->id,
            'title'         => 'required',
            'description'   => 'required',
            'category_id'   => 'required',
            'buy_price'     => 'required',
            'sell_price'    => 'required',
            'stock'         => 'required',
        ]);

        if ($request->file('image')) {
            Storage::disk('local')->delete('public/products/' . basename($product->image));
            
            $image = $request->file('image');
            $image->storeAs('public/products', $image->hashName());
            
            $product->update([
                'image' => $image->hashName(),
                'barcode'       => $request->barcode,
                'title'         => $request->title,
                'description'   => $request->description,
                'category_id'   => $request->category_id,
                'buy_price'     => $request->buy_price,
                'sell_price'    => $request->sell_price,
                'stock'         => $request->stock,
            ]);
        }
        
        $product->update([
            'barcode'       => $request->barcode,
            'title'         => $request->title,
            'description'   => $request->description,
            'category_id'   => $request->category_id,
            'buy_price'     => $request->buy_price,
            'sell_price'    => $request->sell_price,
            'stock'         => $request->stock,
        ]);
        
        return redirect()->route('apps.products.index');
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        Storage::disk('local')->delete('public/products/' . basename($product->image));
        $product->delete();
        
        return redirect()->route('apps.products.index');
    }
}
