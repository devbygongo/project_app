<tr>
    <td class="center-align">{{ $index + 1 }}</td>
    <td><img src="{{ Storage::url('uploads/products_pdf/' . $item->product_code . '.jpg') }}" alt="Product Image" style="height: 60px; width: 60px;"></td>
    <td>{{ $item->product_name }}<br>Part No: {{ $item->product->product_code }}<br>{{ $item->remarks }}</td>
    <td class="center-align">{{ $item->quantity }}</td>
</tr>
