<tr>
  <td class="center-align">{{ $index + 1 }}</td>
  <td>
    <img
      src="{{ Storage::url('uploads/products_pdf/' . $item->product_code . '.jpg') }}"
      alt="Product Image"
      style="height: 60px; width: 60px"
    />
  </td>
  <td>
    {{ $item->product_name }}<br />Part No: {{ $item->product->product_code
    }}<br />
    @if(! empty($item->size))
        <span style="background: yellow;">
            Size: {{ $item->size }}
        </span><br>
    @endif
    <!-- <span style="color: red;">{{ $item->current_stock }}</span> -->
    <span style="color: red;">{{ $item->pending_qty }}</span> | 
    <span style="color: green;">( {{ $item->balance_stock }} Post Reservation )</span><br />
    
    <br /><span style="background: yellow">{{ $item->remarks }}</span>
  </td>
  <td class="center-align">{{ $item->quantity }}</td>
</tr>
