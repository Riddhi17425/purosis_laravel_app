@extends('layouts.email')

@section('content')
    <p style="font-size: 16px; color: #333;">
        Hello {{ $order->distributor->name ?? $order->distributor->full_name ?? 'Distributor' }},
    </p>

    <p style="font-size: 15px; color: #555;">
        Thank you for your order. Your purchase order has been placed successfully.
    </p>

    <h3 style="margin-top: 25px; color: #111827;">Order Information</h3>
    <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
        <tr>
            <td width="35%"><strong>Order Number:</strong></td>
            <td>{{ $order->order_number ?? ('#'.$order->id) }}</td>
        </tr>
        <tr>
            <td><strong>Order Date:</strong></td>
            <td>{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y h:i A') }}</td>
        </tr>
        <tr>
            <td><strong>Transportation Type:</strong></td>
            <td>{{ ucfirst($order->type ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>Status:</strong></td>
            <td>{{ ucfirst($order->status ?? '-') }}</td>
        </tr>
        <tr>
            <td><strong>Total Weight:</strong></td>
            <td>{{ number_format($order->total_weight ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total CBM:</strong></td>
            <td>{{ number_format($order->total_cbm ?? 0, 2) }}</td>
        </tr>
        @if(!empty($order->remarks))
        <tr>
            <td><strong>Remarks:</strong></td>
            <td>{{ $order->remarks }}</td>
        </tr>
        @endif
    </table>

    <h3 style="margin-top: 25px; color: #111827;">Billing Address</h3>
    <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
        <tr>
            <td>
                {{ $order->billingAddress->address_line_1 ?? $order->billingAddress->address ?? '-' }}<br>
                {{ $order->billingAddress->address_line_2 ?? '' }}
                @if(!empty($order->billingAddress->city)), {{ $order->billingAddress->city }} @endif
                @if(!empty($order->billingAddress->state)), {{ $order->billingAddress->state }} @endif
                @if(!empty($order->billingAddress->country)), {{ $order->billingAddress->country }} @endif
                @if(!empty($order->billingAddress->pincode)) - {{ $order->billingAddress->pincode }} @endif
            </td>
        </tr>
    </table>

    <h3 style="margin-top: 25px; color: #111827;">Shipping Address</h3>
    <table width="100%" cellpadding="8" cellspacing="0" style="margin-bottom: 20px; border-collapse: collapse;">
        <tr>
            <td>
                {{ $order->shippingAddress->address_line_1 ?? $order->shippingAddress->address ?? '-' }}<br>
                {{ $order->shippingAddress->address_line_2 ?? '' }}
                @if(!empty($order->shippingAddress->city)), {{ $order->shippingAddress->city }} @endif
                @if(!empty($order->shippingAddress->state)), {{ $order->shippingAddress->state }} @endif
                @if(!empty($order->shippingAddress->country)), {{ $order->shippingAddress->country }} @endif
                @if(!empty($order->shippingAddress->pincode)) - {{ $order->shippingAddress->pincode }} @endif
            </td>
        </tr>
    </table>

    <h3 style="margin-top: 25px; color: #111827;">Order Items</h3>
    <table width="100%" cellpadding="10" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; border: 1px solid #ddd; margin-bottom: 20px;">
        <thead style="background-color: #f3f4f6;">
            <tr>
                <th align="left">#</th>
                <th align="left">Product</th>
                <th align="left">Color</th>
                <th align="left">Qty</th>
                <th align="left">Price</th>
                <th align="left">Weight</th>
                <th align="left">CBM</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->orderProducts as $key => $item)
                <tr>
                    <td>{{ $key + 1 }}</td>
                    <td>{{ $item->product->product_name ?? '-' }}</td>
                    <td>{{ $item->color_code ?? '-' }}</td>
                    <td>{{ $item->qty ?? 0 }}</td>
                    <td>₹{{ number_format($item->price ?? 0, 2) }}</td>
                    <td>{{ number_format($item->total_weight ?? 0, 2) }}</td>
                    <td>{{ number_format($item->total_cbm ?? 0, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">No order items found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p style="font-size: 15px; color: #555;">
        We will process your order shortly.
    </p>

    <p style="margin-top: 25px; font-size: 15px; color: #333;">
        Regards,<br>
        {{ config('app.name') }}
    </p>
@endsection