@extends('layouts.email')

@section('content')

@php
    $isApproved = $order->shipping_status === 'approved';
    $statusText = $isApproved ? 'approved' : 'declined';
@endphp

<tr>
    <td style="padding: 40px;">
        <h1 style="margin: 0 0 10px 0; font-family: 'Times New Roman', Times, serif; font-size: 28px; color: #222222; font-weight: bold;">Hello {{ $order->distributor->name ?? $order->distributor->full_name ?? 'Distributor' }},</h1>
        <p style="margin: 0 0 40px 0; font-family: Arial, sans-serif; font-size: 16px; color: #777777;">Your order has been {{ $statusText }} by admin. Please find the updated order details below.</p>

        <h3 style="margin: 0 0 10px 0; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; background-color: #222222; padding: 10px 15px; border-radius: 4px; letter-spacing: 0.5px;">Order Information</h3>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
            style="border-collapse: collapse;">
            <tr> 
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Order Number:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ $order->order_number ?? ('#'.$order->id) }}</td>
            </tr>
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Order Date:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ \Carbon\Carbon::parse($order->created_at)->format('d M Y h:i A') }}</td>
            </tr>
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Transportation Type:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ ucfirst($order->type ?? '-') }}</td>
            </tr>
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Status:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ ucfirst($order->status ?? '-') }}</td>
            </tr>
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Shipping Status:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ ucfirst($order->shipping_status ?? '-') }}</td>
            </tr>
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Total Weight:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ $order->total_weight ?? null }}</td>
            </tr>
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Total CBM:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ $order->total_cbm ?? null }}</td>
            </tr>
            @if(!empty($order->remarks))
            <tr>
                <td width="35%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #888888;">Remarks:</td>
                <td width="65%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;">{{ $order->remarks ?? '-' }}</td>
            </tr>
            @endif
        </table>

        <h3 style="margin: 30px 0 10px 0; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; background-color: #222222; padding: 10px 15px; border-radius: 4px; letter-spacing: 0.5px;">Billing Address</h3>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                style="border-collapse: collapse;">
                <tr>
                    <td width="100%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;"> {{ $order->billingAddress->address_line_1 ?? $order->billingAddress->address ?? '-' }}
                    {{ $order->billingAddress->address_line_2 ?? '' }}
                    @if(!empty($order->billingAddress->city)), {{ $order->billingAddress->city }} @endif
                    @if(!empty($order->billingAddress->state)), {{ $order->billingAddress->state }} @endif
                    @if(!empty($order->billingAddress->country)), {{ $order->billingAddress->country }} @endif
                    @if(!empty($order->billingAddress->pincode)) - {{ $order->billingAddress->pincode }} @endif</td>
                </tr>
            </table>

        <h3 style="margin: 30px 0 10px 0; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; background-color: #222222; padding: 10px 15px; border-radius: 4px; letter-spacing: 0.5px;">Shipping Address</h3>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                <tr>
                    <td width="100%" style="border: 1px solid #dddddd; padding: 15px; font-family: Arial, sans-serif; font-size: 14px; color: #555555;"> {{ $order->shippingAddress->address_line_1 ?? $order->shippingAddress->address ?? '-' }}
                    {{ $order->shippingAddress->address_line_2 ?? '' }}
                    @if(!empty($order->shippingAddress->city)), {{ $order->shippingAddress->city }} @endif
                    @if(!empty($order->shippingAddress->state)), {{ $order->shippingAddress->state }} @endif
                    @if(!empty($order->shippingAddress->country)), {{ $order->shippingAddress->country }} @endif
                    @if(!empty($order->shippingAddress->pincode)) - {{ $order->shippingAddress->pincode }} @endif</td>
                </tr>
            </table>

        <h3 style="margin: 30px 0 10px 0; font-family: Arial, sans-serif; font-size: 15px; font-weight: bold; color: #ffffff; background-color: #222222; padding: 10px 15px; border-radius: 4px; letter-spacing: 0.5px;">Order Items</h3>
            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f0f0f0;">
                        <th align="left" style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #333333; padding: 10px 12px; border: 1px solid #dddddd;">#</th>
                        <th align="left" style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #333333; padding: 10px 12px; border: 1px solid #dddddd;">Product</th>
                        <th align="left" style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #333333; padding: 10px 12px; border: 1px solid #dddddd;">Color</th>
                        <th align="left" style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #333333; padding: 10px 12px; border: 1px solid #dddddd;">Qty</th>
                        <th align="left" style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #333333; padding: 10px 12px; border: 1px solid #dddddd;">Weight</th>
                        <th align="left" style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #333333; padding: 10px 12px; border: 1px solid #dddddd;">CBM</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($order->orderProducts as $key => $item)
                        <tr>
                            <td style="font-family: Arial, sans-serif; font-size: 14px; color: #555555; padding: 10px; border: 1px solid #ddd;">{{ $key + 1 }}</td>
                            <td style="font-family: Arial, sans-serif; font-size: 14px; color: #555555; padding: 10px; border: 1px solid #ddd;">{{ $item->product->product_name ?? '-' }}</td>
                            <td style="font-family: Arial, sans-serif; font-size: 14px; color: #555555; padding: 10px; border: 1px solid #ddd;">{{ $item->color_code ?? '-' }}</td>
                            <td style="font-family: Arial, sans-serif; font-size: 14px; color: #555555; padding: 10px; border: 1px solid #ddd;">{{ $item->qty ?? 0 }}</td>
                            <td style="font-family: Arial, sans-serif; font-size: 14px; color: #555555; padding: 10px; border: 1px solid #ddd;">{{ $item->total_weight ?? null }}</td>
                            <td style="font-family: Arial, sans-serif; font-size: 14px; color: #555555; padding: 10px; border: 1px solid #ddd;">{{ $item->total_cbm ?? null }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" align="center" style="font-family: Arial, sans-serif; font-size: 14px; color: #888888; padding: 15px; border: 1px solid #ddd;">No order items found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

    </td>
</tr>
@endsection