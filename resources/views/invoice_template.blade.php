<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Invoice</title>
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
            background-color: lightgrey; /* Light grey background */
            color: black; /* Black text */
            padding: 5px; /* Thinner padding for a less bulky look */
        }
        table, th, td {
            border: 1px solid #ddd;
            border-collapse: collapse;
        }
        .center-align {
            text-align: center;
        }
        .right-align {
            text-align: right;
        }
        .label {
            width: 15%; /* Slimmer labels */
        }
        .value {
            width: 35%; /* Wider values */
        }
    </style>
</head>
<body>

    <!-- Header Section -->
    <div class="header">
        <img src="{{ asset('storage/uploads/s1.jpg') }}" alt="Logo">
    </div>

    <!-- Customer and Order Information -->
    <table class="customer-info">
        <tr>
            <td class="label">Client:</td>
            <td class="value">{{ $user->name }}</td>
            <td class="label">Order ID:</td>
            <td class="value">{{ $invoice->invoice_number }}</td>
        </tr>
        <tr>
            <td class="label">Address:</td>
            <td class="value">{{ $user->address_line_1 }}{{ !empty($user->address_line_1) && !empty($user->address_line_2) ? ', ' : '' }}{{ $user->address_line_2 }}</td>
            <td class="label">Order Date:</td>
            <td class="value">{{ $invoice->order_date }}</td>
        </tr>
        <tr>
            <td class="label">GSTIN:</td>
            <td class="value">{{ $user->gstin }}</td>
            <td class="label">Order Type:</td>
            <td class="value">{{ $invoice->type }}</td>
        </tr>
        <tr>
            <td class="label">Mobile:</td>
            <td class="value">{{ $user->mobile }}</td>
            <td class="label">Amount:</td>
            <td class="value">₹ {{ $invoice->amount }}</td>
        </tr>
    </table>

    <!-- Order Details -->
    <table class="order-summary">
        <thead>
            <tr>
                <th class="center-align">SN</th>
                <th>Photo</th>
                <th>Product Name</th>
                <th class="center-align">Qty</th>
                <th class="right-align">Unit Price (Rs.)</th>
                <th class="right-align">Total (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice_items as $index => $item)
                <tr>
                    <td class="center-align">{{ $index + 1 }}</td>
                    <td><img src="{{ Storage::url('uploads/products/' . $item->product_code . '.jpg') }}" alt="" style="height: 60px; width: 60px;"></td>
                    <td>{{ $item->product_name }}<br>SKU: {{ $item->product_code }}</td>
                    <td class="center-align">{{ $item->quantity }}</td>
                    <td class="right-align">₹ {{ $item->rate }}</td>
                    <td class="right-align">₹ {{ $item->total }}</td>
                </tr>
            @endforeach
            <tr>
                <td colspan="5" class="right-align">Total</td>
                <td class="right-align">₹ {{ $invoice->amount }}</td>
            </tr>
        </tbody>
    </table>

    <!-- QR Code and Footer -->
    <div style="position: fixed; bottom: 10px; width: 100%;">
        <div class="footer">
            <p>Thank you for working with us</p>
        </div>
    </div>

</body>
</html>
