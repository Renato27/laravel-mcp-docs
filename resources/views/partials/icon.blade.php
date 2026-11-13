@php
    $paths = [
        'info' => '<circle cx="8" cy="8" r="6.25"/><path d="M8 7.25v4"/><path d="M8 5.1v.05"/>',
        'plug' => '<path d="M6 2v3M10 2v3"/><path d="M4.5 5h7v3a3.5 3.5 0 0 1-7 0z"/><path d="M8 11.5V14"/>',
        'tool' => '<path d="M10.2 2.6a3.2 3.2 0 0 0-4.1 4.1l-3.5 3.5a1.2 1.2 0 0 0 0 1.7l1.5 1.5a1.2 1.2 0 0 0 1.7 0l3.5-3.5a3.2 3.2 0 0 0 4.1-4.1L11.6 7.4 8.6 4.4z"/>',
        'doc' => '<path d="M9 1.75H4.5a1 1 0 0 0-1 1v10.5a1 1 0 0 0 1 1h7a1 1 0 0 0 1-1V5.25z"/><path d="M9 1.75v3.5h3.5"/><path d="M6 8.5h4M6 11h3"/>',
        'chat' => '<path d="M13.25 9.5a1.5 1.5 0 0 1-1.5 1.5H6l-3 2.25V4a1.5 1.5 0 0 1 1.5-1.5h7.25a1.5 1.5 0 0 1 1.5 1.5z"/>',
        'server' => '<rect x="2.25" y="2.75" width="11.5" height="4.25" rx="1"/><rect x="2.25" y="9" width="11.5" height="4.25" rx="1"/><path d="M4.75 4.9v.05M4.75 11.1v.05"/>',
        'sun' => '<circle cx="8" cy="8" r="2.75"/><path d="M8 1.5v1.4M8 13.1v1.4M14.5 8h-1.4M2.9 8H1.5M12.6 3.4l-1 1M4.4 11.6l-1 1M12.6 12.6l-1-1M4.4 4.4l-1-1"/>',
        'moon' => '<path d="M13 9.4A5.6 5.6 0 0 1 6.6 3a5.75 5.75 0 1 0 6.4 6.4z"/>',
        'monitor' => '<rect x="2" y="3" width="12" height="8" rx="1"/><path d="M6 13.5h4M8 11v2.5"/>',
    ];
@endphp
<svg class="icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false"
     fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round">
    {!! $paths[$name] ?? '' !!}
</svg>
