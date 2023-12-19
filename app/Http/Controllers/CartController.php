<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Http\Resources\CartItemCollection as CartItemCollection;
use App\Models\Order;
use App\Models\Product;
use Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{

    /**
     * Store a newly created Cart in storage and return the data to the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $userID =  auth()->user()->id;

        $cart = Cart::firstOrCreate(['userID' => $userID],['key' => md5(uniqid(rand(), true))]);
		
        return response()->json([
            'Message' => 'A new cart have been created for you!',
            'cartID' => $cart->id,
            'cartKey' => $cart->key,
        ], 201);

    }

    /**
     * Display the specified Cart.
     *
     * @param  \App\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function show(Cart $cart, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cartKey' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $cartKey = $request->input('cartKey');
        if ($cart->key == $cartKey) {
            return response()->json([
                'cart' => $cart->id,
                'Items in Cart' => new CartItemCollection($cart->items),
            ], 200);

        } else {

            return response()->json([
                'message' => 'The CarKey you provided does not match the Cart Key for this Cart.',
            ], 404);
        }

    }

    /**
     * Remove the specified Cart from storage.
     *
     * @param  \App\Cart  $cart
     * @return \Illuminate\Http\Response
     */
    public function destroy(Cart $cart, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cartKey' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $cartKey = $request->input('cartKey');
		$cart = $cart->where('key',$cartKey)->first();
        if ($cart) {
            $cart->delete();
            return response()->json('Cart destroyed', 204);
        } else {
            return response()->json([
                'message' => 'The CarKey you provided does not match the Cart Key for this Cart.',
            ], 404);
        }

    }

    /**
     * Adds Products to the given Cart;
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Cart  $cart
     * @return void
     */
    public function addProducts(Request $request, $cartId)
    {
        $validator = Validator::make($request->all(), [
            'cartKey' => 'required',
            'productID' => 'required',
            'quantity' => 'required|numeric|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $cartKey = $request->input('cartKey');
        $productID = $request->input('productID');
        $quantity = $request->input('quantity');

        //Check if the CarKey is Valid
		$cart = Cart::findOrFail($cartId);
		
        if ($cart->key == $cartKey) {
            //Check if the proudct exist or return 404 not found.
            try { $Product = Product::findOrFail($productID);} catch (ModelNotFoundException $e) {
                return response()->json([
                    'message' => 'The Product you\'re trying to add does not exist.',
                ], 404);
            }

            //check if the the same product is already in the Cart, if true update the quantity, if not create a new one.
            $cartItem = CartItem::where(['cart_id' => $cart->getKey(), 'product_id' => $productID])->first();
            if ($cartItem) {
                $cartItem->quantity = $quantity;
                CartItem::where(['cart_id' => $cart->getKey(), 'product_id' => $productID])->update(['quantity' => $quantity]);
            } else {
                CartItem::create(['cart_id' => $cart->getKey(), 'product_id' => $productID, 'quantity' => $quantity]);
            }

            return response()->json(['message' => 'The Cart was updated with the given product information successfully'], 200);

        } else {

            return response()->json([
                'message' => 'The CarKey you provided does not match the Cart Key for this Cart.',
            ], 404);
        }

    }
	
	public function removeProduct(Request $request, $cartId)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

		$item = CartItem::where(['cart_id' => $cartId,'product_id' => $request->product_id])->first();	
		$item->delete();
		
        $cart = Cart::findOrFail($cartId);

        return response()->json($cart, 200);
    }

    /**
     * checkout the cart Items and create and order.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Cart  $cart
     * @return void
     */
    public function checkout(Cart $cart, Request $request)
    {

        $userID =  auth()->user()->id;

        $validator = Validator::make($request->all(), [
            'cartKey' => 'required',
            'name' => 'required',
            'adress' => 'required',
            'credit_card_number' => 'required',
            'expiration_year' => 'required',
            'expiration_month' => 'required',
            'cvc' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $cartKey = $request->input('cartKey');
        if ($cart->key == $cartKey) {
            $name = $request->input('name');
            $adress = $request->input('adress');
            $creditCardNumber = $request->input('credit card number');
            $TotalPrice = (float) 0.0;
            $items = $cart->items;

            foreach ($items as $item) {

                $product = Product::find($item->product_id);
                $price = $product->price;
                $inStock = $product->UnitsInStock;
                if ($inStock >= $item->quantity) {

                    $TotalPrice = $TotalPrice + ($price * $item->quantity);

                    $product->UnitsInStock = $product->UnitsInStock - $item->quantity;
                    $product->save();
                } else {
                    return response()->json([
                        'message' => 'The quantity you\'re ordering of ' . $item->Name .
                        ' isn\'t available in stock, only ' . $inStock . ' units are in Stock, please update your cart to proceed',
                    ], 400);
                }
            }

            /**
             * Credit Card information should be sent to a payment gateway for processing and validation,
             * the response should be dealt with here, but since this is a dummy project we'll
             * just assume that the information is sent and the payment process was done succefully,
             */

            $PaymentGatewayResponse = true;
            $transactionID = md5(uniqid(rand(), true));

            if ($PaymentGatewayResponse) {
                $order = Order::create([
                    'products' => json_encode(new CartItemCollection($items)),
                    'totalPrice' => $TotalPrice,
                    'name' => $name,
                    'address' => $adress,
                    'userID' => isset($userID) ? $userID : null,
                    'transactionID' => $transactionID,
                ]);

                $cart->delete();

                return response()->json([
                    'message' => 'you\'re order has been completed succefully, thanks for shopping with us!',
                    'orderID' => $order->getKey(),
                ], 200);
            }
        } else {
            return response()->json([
                'message' => 'The CarKey you provided does not match the Cart Key for this Cart.',
            ], 404);
        }

    }

}
