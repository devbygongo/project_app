<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
        }
        .header img {
            width: 100%;
        }
        .customer-info, .order-details {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }
        .customer-info td, .order-details td {
            padding: 8px;
        }
        .order-summary {
            margin-top: 20px;
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 20px;
        }
        .order-summary th, .order-summary td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            color: red;
        }
        table, th, td {
            border: 4px solid white;
            border-collapse: collapse;
        }
        th {
            background-color: rgb(219, 243, 219);
        }
        td {
            text-align: center;
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="{{ asset('storage/uploads/s1.jpg') }}" alt="Logo">
    </div>

    <!-- Customer Information -->
    <table width="100%" class="customer-info">
        <tr>
            <td width="60%">
                <strong>Customer Info:</strong><br>
                The Amazing Store<br>
                {{ $user_name }}<br>
                {{ $user_address1 }}@if(!empty($user_address1) && !empty($user_address2)), @endif{{ $user_address2 }}<br>
                GSTIN: {{ $user_gstin }}<br>
                Phone: {{ $user_mobile }}
            </td>
            <td width="40%" style="background-color: aliceblue; padding: 10px; text-align:right;">
                <strong>Order ID.:</strong> {{ $order_id }}<br>
                <strong>Order Date:</strong> {{ $order_date }}<br>
                <strong>Order Type:</strong> {{ $type }}<br>
                <strong>Amount:</strong> ₹ 894
            </td>
        </tr>
    </table>

    <!-- Order Details -->
    <table class="order-summary">
        <thead>
            <tr>
                <th style="text-align:left;">Product Name</th>
                <th>Quantity</th>
                <th>Unit Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="text-align:left;">
                    <img src="{{ Storage::url('uploads/products/' . $product_code . '.jpg') }}" alt="" style="height: 60px; width: 60px;">
                    {{ $product_name }}<br>SPRAYER 16L<br>SKU: 1073
                </td>
                <td>{{ $product_quantity }}</td>
                <td>{{ $product_rate }}</td>
                <td>{{ $product_total }}</td>
            </tr>
        </tbody>
    </table>

    <!-- QR Code and Grand Total -->
    <table width="100%" style="padding: 10px; margin-top: 20px;">
        <tr>
        <td width="50%">
            {!! $qrCode !!}
        </td>
            <td width="50%" style="text-align:right;">
                <h3>Grand Total: {{ $product_total }}</h3>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <p>Thank you for Working with us</p>
    </div>

</body>
</html>
