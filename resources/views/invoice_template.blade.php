<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            padding-top: 15px;
        }
        .header img {
            width: 100%;
            display: block;
            height: auto;
        }
        .customer-info, .order-details, .order-summary {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .customer-info td, .order-details td, .order-summary th, .order-summary td {
            padding: 8px;
        }
        .order-summary th {
            background-color: grey;
            color: white;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            background-color: black;
            color: grey;
            padding: 10px;
        }
        table, th, td {
            border: 1px solid #ddd;
            border-collapse: collapse;
        }
        td {
            text-align: left;
        }
        .order-id-section {
            background-color: aliceblue;
            padding: 10px;
            text-align: right;
        }
        .total-in-words {
            margin-top: 10px;
        }
        .right-align {
            text-align: right;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="{{ asset('storage/uploads/s1.jpg') }}" alt="Logo">
    </div>

    <!-- Customer Information -->
    <table class="customer-info">
        <tr>
            <td>{{ $user_name }}</td>
            <td></td>
        </tr>
        <tr>
            <td>{{ $user_address1 }}@if(!empty($user_address1) && !empty($user_address2)), @endif{{ $user_address2 }}</td>
            <td></td>
        </tr>
        <tr>
            <td>GSTIN: {{ $user_gstin }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Phone: {{ $user_mobile }}</td>
            <td></td>
        </tr>
        <tr>
            <td class="order-id-section" colspan="2">
                <strong>Order ID:</strong> {{ $order_id }}<br>
                <strong>Order Date:</strong> {{ $order_date }}<br>
                <strong>Order Type:</strong> {{ $type }}<br>
                <strong>Amount:</strong> ₹ {{ $amount }}
            </td>
        </tr>
    </table>

    <!-- Order Details -->
    <table class="order-summary">
        <thead>
            <tr>
                <th>SN</th>
                <th>Photo</th>
                <th>Product Name</th>
                <th>Qty</th>
                <th>Unit Price (Rs.)</th>
                <th>Total (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td><img src="{{ Storage::url('uploads/products/' . $product_code . '.jpg') }}" alt="" style="height: 60px; width: 60px;"></td>
                <td>{{ $product_name }}<br>SKU: {{ $product_sku }}</td>
                <td>{{ $product_quantity }}</td>
                <td>₹ {{ $product_rate }}</td>
                <td>₹ {{ $product_total }}</td>
            </tr>
            <!-- Row for displaying total -->
            <tr>
                <td colspan="4">Total</td>
                <td colspan="2" class="right-align">₹ {{ $product_total }}</td>
            </tr>
        </tbody>
    </table>

    <!-- QR Code and Footer -->
    <div style="position: fixed; bottom: 10px; width: 100%;">
        <div style="float: right;">
            {!! $qrCode !!}
        </div>
        <div class="footer">
            <p>Thank you for working with us</p>
        </div>
    </div>

</body>
</html>
