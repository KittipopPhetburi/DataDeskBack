@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="https://datacomm-asia.com/wp-content/uploads/2025/02/Completely-logo-01-1024x1024.png" class="logo" alt="DataComAsia Logo" style="height: 150px; max-height: 150px; width: auto;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
