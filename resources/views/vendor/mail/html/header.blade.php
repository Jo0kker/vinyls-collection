@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel' || trim($slot) === config('app.name'))
<div style="display: flex; align-items: center; gap: 10px; color: #3B82F6; font-size: 24px; font-weight: bold;">
    <svg width="32" height="32" fill="currentColor" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/>
        <circle cx="12" cy="12" r="3" fill="currentColor"/>
    </svg>
    <span>Vinyls Collection</span>
</div>
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
