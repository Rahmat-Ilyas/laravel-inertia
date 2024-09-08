<?php

namespace App\Http\Controllers\Apps;

use Inertia\Inertia;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        $carts = Cart::with('product')->where('cashier_id', Auth::user()->id)->latest()->get();
        
        $customers = Customer::latest()->get();

        return Inertia::render('Apps/Transactions/Index', [
            'carts'         => $carts,
            'carts_total'   => $carts->sum('price'),
            'customers'     => $customers
        ]);
    }
    
    public function searchProduct(Request $request)
    {
        $product = Product::where('barcode', $request->barcode)->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'data'    => $product
            ]);
        }

        return response()->json([
            'success' => false,
            'data'    => null
        ]);
    }

    public function addToCart(Request $request)
    {
        if (Product::whereId($request->product_id)->first()->stock < $request->qty) {
            return redirect()->back()->with('error', 'Out of Stock Product!.');
        }
        
        $cart = Cart::with('product')
            ->where('product_id', $request->product_id)
            ->where('cashier_id', Auth::user()->id)
            ->first();

        if ($cart) {
            $cart->increment('qty', $request->qty);
            $cart->price  = $cart->product->sell_price * $cart->qty;

            $cart->save();
        } else {
            Cart::create([
                'cashier_id'    => Auth::user()->id,
                'product_id'    => $request->product_id,
                'qty'           => $request->qty,
                'price'         => $request->sell_price * $request->qty,
            ]);
        }

        return redirect()->route('apps.transactions.index')->with('success', 'Product Added Successfully!.');
    }
    
    public function destroyCart(Request $request)
    {
        $cart = Cart::with('product')->whereId($request->cart_id)->first();  
        $cart->delete();

        return redirect()->back()->with('success', 'Product Removed Successfully!.');
    }
    
    public function store(Request $request)
    {
        $length = 10;
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
        }
        
        $invoice = 'TRX-' . Str::upper($random);
        
        $transaction = Transaction::create([
            'cashier_id'    => Auth::user()->id,
            'customer_id'   => $request->customer_id,
            'invoice'       => $invoice,
            'cash'          => $request->cash,
            'change'        => $request->change,
            'discount'      => $request->discount,
            'grand_total'   => $request->grand_total,
        ]);

        $carts = Cart::where('cashier_id', Auth::user()->id)->get();
        foreach ($carts as $cart) {
            $transaction->details()->create([
                'transaction_id'    => $transaction->id,
                'product_id'        => $cart->product_id,
                'qty'               => $cart->qty,
                'price'             => $cart->price,
            ]);
            
            $total_buy_price  = $cart->product->buy_price * $cart->qty;
            $total_sell_price = $cart->product->sell_price * $cart->qty;
            $profits = $total_sell_price - $total_buy_price;
            
            $transaction->profits()->create([
                'transaction_id'    => $transaction->id,
                'total'             => $profits,
            ]);
            
            $product = Product::find($cart->product_id);
            $product->stock = $product->stock - $cart->qty;
            $product->save();
        }
        Cart::where('cashier_id', Auth::user()->id)->delete();

        return response()->json([
            'success' => true,
            'data'    => $transaction
        ]);
    }
    
    public function print(Request $request)
    {
        $transaction = Transaction::with('details.product', 'cashier', 'customer')->where('invoice', $request->invoice)->firstOrFail();
        return view('print.nota', compact('transaction'));
    }
}
