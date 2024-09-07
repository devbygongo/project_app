<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <title>Proforma Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .content {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .red-block {
            background-color: red;
            color: white;
            padding: 10px 20px;
            text-align: left;
        }

        /* .invoice-header {
            text-align: center;
            margin-bottom: 20px;
        } */

        .invoice-header h1 {
            margin: 0;
        }

        .customer-details, .order-summary {
            margin-bottom: 10px;
            padding: 0 20px;
        }

        .order-summary {
            margin-bottom: 20px;
            padding: 0 20px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .invoice-table th, .invoice-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        .invoice-table th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .total-section {
            text-align: right;
            padding-right: 20px;
            margin-bottom: 20px;
        }

        .qr-code {
            text-align: center;
            margin-bottom: 20px;
        }

        .invoice-footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.8em;
        }
    </style>
</head>
<body>
    <div class="content">
        <h1 align="center">PROFORMA INVOICE</h1>
        <div class="red-block">
            <h2>MAZING RETAIL PRIVATE LIMITED</h2>
            <p>71/6 A, Ground Floor, Rama Road Industrial Area, New Delhi – 110015</p>
            <p>GSTIN: 07AAOCM7588A1Z3</p>
        </div>
        <!-- <div class="invoice-header">
            <h1>PROFORMA INVOICE</h1>
            <p>Dot Com Solutions Pvt.Ltd</p>
            <p>71/6 A, Ground Floor, Rama Road Industrial Area, New Delhi – 110015</p>
            <p>GSTIN: 07AAOCM7588A1Z3</p>
        </div> -->

        <div class="row">
            <div class="customer-details">
                <h3>Customer Info:</h3>
                <!-- <p>The Mazing.Store</p> -->
                <p>{{ $user_name }}</p>
                <p>
                {{ $user_address1 }}@if(!empty($user_address1) && !empty($user_address2)), @endif{{ $user_address2 }}        </p>
                <p>Email: {{ $user_mobile }}</p>
                <p>Phone: {{ $user_mobile }}</p>

                <p>GSTIN: 07AAOCM7588A1Z3</p>
            </div>
            <div class="or">
                <p><strong>Order Id.:</strong> {{ $order_id }}</p>
                <p><strong>Order Date:</strong> {{ $order_date }}</p>
                <p><strong>Order Type:</strong> {{ $type }}</p>
            </div>
        </div>
        <div class="order-summary">
            <h3>Order Summary</h3>
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Product Code</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $product_name }}</td>
                        <td>{{ $product_code }}</td>
                        <td> {{ $product_quantity }}</td>
                        <td>{{ $product_rate }}</td>
                        <td>{{ $product_total }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        

        <!-- <div class="total-section">
            <p><strong>Sub Total:</strong> ₹ 894</p>
            <p><strong>Shipping cost:</strong> ₹ 0</p>
            <p><strong>Total Tax:</strong> ₹ 136</p>
            <p><strong>Grand Total:</strong> ₹ 894</p>
        </div> -->

        <div class="qr-code">
            <h3>QR Code</h3>
            <div>{!! $qrCode !!}</div>
        </div>

        <div class="invoice-footer">
            <p>Thank you for your purchase!</p>
        </div>
    </div>
</body>
</html>
