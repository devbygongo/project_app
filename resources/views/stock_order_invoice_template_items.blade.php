<tr>
    <td class="center-align">{{ $stock_index + 1 }}</td>
    <td><img src="{{ Storage::url('uploads/products_pdf/' . $stock_item->product_code . '.jpg') }}" alt="Product Image" style="height: 60px; width: 60px;"></td>
    <td>{{ $stock_item->product_name }}<br>Part No: {{ $stock_item->product->product_code }}<br>{{ $stock_item->remarks }}</td>
    <td class="center-align">{{ $stock_order->godown->name ?? 'N/A' }}</td>
    <td class="center-align">{{ $item->quantity }}</td>
    <td class="center-align">{{ strtoupper($item->type) }}</td>
</tr>
