<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Selling Products Pamphlet</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; font-size: 12px; }
        .header { width: 100%; padding-top: 10px; text-align: center; }
        .header img { max-width: 200px; height: auto; }
        .title { text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 8px; text-align: left; }
        th.center, td.center { text-align: center; }
        th.right, td.right { text-align: right; }
        img.product-image { width: 60px; height: 60px; object-fit: contain; }
        .category-row { background-color: #f2f2f2; font-weight: bold; text-align: left; }
    </style>
</head>
<body>

<div class="header">
    <img src="{{ asset('storage/uploads/logo.png') }}" alt="Company Logo">
</div>

<div class="title">Top Selling Products - Last 3 Months</div>

<table>
    <thead>
        <tr>
            <th class="center">SN</th>
            <th>Photo</th>
            <th>Product Name</th>
            <th>Category</th>
            <th class="right">Price (Rs.)</th>
        </tr>
    </thead>
    <tbody>
        @php
            $sn = 1;
        @endphp

        @foreach($items as $item)
        <tr>
            <td class="center">{{ $sn++ }}</td>
            <td class="center">
                @php
                    $imagePath = storage_path('app/public/uploads/' . $item->product_code . '.jpg');
                @endphp
                @if(file_exists($imagePath))
                    <img src="{{ $imagePath }}" alt="Product Image" class="product-image">
                @else
                    <span>No Image</span>
                @endif
            </td>
            <td>{{ $item->product_name }}<br>
                Part No: {{ $item->product->product_code ?? 'N/A' }}<br>
                @if(!empty($item->size))
                    <span style="background: yellow;">Size: {{ $item->size }}</span><br>
                @endif
                @if(!empty($item->remarks))
                    <span style="background: yellow;">{{ $item->remarks }}</span><br>
                @endif
            </td>
            <td>{{ $item->product->category ?? 'N/A' }}</td>
            <td class="right">₹ {{ number_format((float)($item->product->basic ?? $item->rate), 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer" style="text-align: center; margin-top: 20px; font-size: 14px;">
    <p>Thank you for working with us</p>
</div>

</body>
</html>