<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report</title>
    <style>
        /* Include all relevant styling */
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; }
        .header { width: 100%; padding-top: 15px; }
        .header img { width: 100%; height: auto; }
        .customer-info, .order-summary { width: 100%; margin-top: 20px; border-collapse: collapse; border: 1px solid #ddd;}
        .order-summary th, .order-summary td { padding: 8px; border: 1px solid #ddd; }
        .center-align { text-align: center; }
        .right-align { text-align: right; }
        .footer { text-align: center; background-color: lightgrey; color: black; padding: 10px; font-size: 16px; }
        .order-title { text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0 10px; }
        .customer-info td { border: 1px solid #ddd; padding: 2px; }
    </style>
</head>
<body>

<div class="order-title">Stock Order</div>

<table class="customer-info">
    <tr>
        <td>Order ID:</td><td>{{ $order->order_id }}</td>
        <td>Order Date:</td><td>{{ \Carbon\Carbon::parse($order->order_date)->format('d/m/Y') }}
        </td>
    </tr>
    <tr>
        <td>User:</td><td>{{ $order->user->name }}</td>
        <td>Remarks:</td><td>{{ $order->remarks }}</td>
    </tr>
</table>

<table class="order-summary">
    <thead>
        <tr>
            <th class="center-align">SN</th>
            <th>Photo</th>
            <th>Product Name</th>
            <th>Godown</th>
            <th class="center-align">Qty</th>
            <th class="right-align">Type</th>
        </tr>
    </thead>
    <tbody>
