<tr>
    <td class="center-align">{{ $stock_index + 1 }}</td>
    <td><img src="{{ url($stock_item->stock_product->product_image) }}" 
             alt="Product Image" 
             style="height: 60px; width: 60px;"></td>
    <td>{{ $stock_item->product_name }}<br>Part No: {{ $stock_item->product_code }}
    
    @if(! empty($stock_item->size))
        <span style="background: yellow;">
            Size: {{ $stock_item->size }}
        </span><br>
    @endif
    <br>{{ $stock_item->remarks }}</td>
    <td class="center-align">{{ $godown_name ?? 'N/A' }}</td>
    <td class="center-align">{{ $stock_item->quantity }}</td>
    <td class="center-align">{{ strtoupper($stock_item->type) }}</td>
</tr>
