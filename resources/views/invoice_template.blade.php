<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .invoice-header, .invoice-footer {
            text-align: center;
            margin-bottom: 20px;
        }

        .invoice-header h1 {
            margin-bottom: 0;
        }

        .invoice-details, .customer-details, .order-summary {
            margin-bottom: 20px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .invoice-table th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .qr-code {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>Invoice</h1>
        <p><strong>Order ID:</strong> {{ $order_id }}</p>
        <p><strong>Order Date:</strong> {{ $order_data }}</p>
    </div>

    <div class="customer-details">
        <h3>Customer Details</h3>
        <p><strong>Name:</strong> {{ $user_name }}</p>
        <p><strong>Mobile:</strong> {{ $user_mobile }}</p>
        <p><strong>Address:</strong> {{ $user_address1 }} {{ $user_address2 }}</p>
        <p><strong>GSTIN:</strong> {{ $user_gstin }}</p>
    </div>

    <div class="order-summary">
        <h3>Order Summary</h3>
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Amount</th>
                    <th>Type</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $order_id }}</td>
                    <td>{{ number_format($amount, 2) }}</td>
                    <td>{{ $type }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="qr-code">
        <h3>QR Code</h3>
        <div>{!! $qrCode !!}</div>
    </div>

    <div class="invoice-footer">
        <p>Thank you for your purchase!</p>
    </div>
</body>
</html>