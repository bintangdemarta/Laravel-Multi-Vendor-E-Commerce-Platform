<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .content {
            background: #f9fafb;
            padding: 30px;
        }

        .order-info {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .item {
            border-bottom: 1px solid #e5e7eb;
            padding: 10px 0;
        }

        .total {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
        }

        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 30px;
        }

        .btn {
            background: #4F46E5;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Order Confirmation</h1>
        </div>

        <div class="content">
            <p>Dear {{ $order->user->name }},</p>

            <p>Thank you for your order! Your order has been confirmed and is being processed.</p>

            <div class="order-info">
                <h2>Order Details</h2>
                <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
                <p><strong>Order Date:</strong> {{ $order->created_at->format('d F Y, H:i') }}</p>
                <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            </div>

            <div class="order-info">
                <h3>Items Ordered</h3>
                @foreach($order->items as $item)
                    <div class="item">
                        <p><strong>{{ $item->product_name }}</strong></p>
                        <p>Quantity: {{ $item->quantity }} × Rp {{ number_format($item->price, 0, ',', '.') }}</p>
                        <p>Subtotal: Rp {{ number_format($item->subtotal, 0, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>

            <div class="order-info">
                <h3>Shipping Address</h3>
                <p>{{ $order->shipping_recipient_name }}</p>
                <p>{{ $order->shipping_phone }}</p>
                <p>{{ $order->shipping_address }}</p>
                <p>{{ $order->shipping_district }}, {{ $order->shipping_city }}</p>
                <p>{{ $order->shipping_province }} {{ $order->shipping_postal_code }}</p>
            </div>

            <div class="order-info">
                <h3>Order Summary</h3>
                <p>Subtotal: Rp {{ number_format($order->subtotal, 0, ',', '.') }}</p>
                <p>Shipping: Rp {{ number_format($order->shipping_cost, 0, ',', '.') }}</p>
                <p>Tax (VAT 11%): Rp {{ number_format($order->tax_vat, 0, ',', '.') }}</p>
                <p class="total">Total: Rp {{ number_format($order->total, 0, ',', '.') }}</p>
            </div>

            <p style="text-align: center; margin-top: 30px;">
                <a href="{{ url('/orders/' . $order->order_number) }}" class="btn">View Order</a>
            </p>

            <p>If you have any questions, please contact our customer service.</p>

            <p>Best regards,<br>{{ config('app.name') }} Team</p>
        </div>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>

</html>