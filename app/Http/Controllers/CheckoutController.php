<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\CartService;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        $cart = $this->cartService->getCart($user);
        $cartSummary = $this->cartService->getCartSummary($cart);

        if ($cartSummary['total_items'] === 0) {
            return redirect()->route('cart')->with('error', 'Your cart is empty');
        }

        $addresses = Address::where('user_id', $user->id)
            ->with('district.city.province')
            ->get()
            ->map(function ($address) {
                return [
                    'id' => $address->id,
                    'label' => $address->label,
                    'recipient_name' => $address->recipient_name,
                    'phone' => $address->phone,
                    'address_line' => $address->address_line,
                    'district' => $address->district->name,
                    'city' => $address->district->city->name,
                    'province' => $address->district->city->province->name,
                    'postal_code' => $address->postal_code,
                    'is_default' => $address->is_default,
                ];
            });

        return Inertia::render('Checkout/Index', [
            'addresses' => $addresses,
            'cart' => $cartSummary,
        ]);
    }

    public function success(Request $request)
    {
        $orderId = $request->query('order_id');
        $order = null;

        if ($orderId) {
            $order = \App\Models\Order::where('id', $orderId)
                ->where('user_id', $request->user()->id)
                ->first();
        }

        return Inertia::render('Checkout/Success', [
            'order' => $order,
        ]);
    }

    public function failed(Request $request)
    {
        return Inertia::render('Checkout/Failed', [
            'errorMessage' => $request->query('message'),
        ]);
    }
}
