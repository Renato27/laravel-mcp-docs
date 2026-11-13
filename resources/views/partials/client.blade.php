{{--
    One client's instructions. Platforms that read identically share a single
    walkthrough, so the OS selector appears only where something genuinely
    differs -- a menu path, a shortcut, or a config file location.
--}}
@php($multiple = count($walkthroughs) > 1)

@if ($video ?? null)
    <figure class="client-video">
        <video controls preload="metadata" playsinline src="{{ $video['src'] }}"
               @if ($video['poster']) poster="{{ $video['poster'] }}" @endif
               aria-label="{{ $video['caption'] ?? 'Connecting '.$clientLabel }}"></video>
        @if ($video['caption'])
            <figcaption>{{ $video['caption'] }}</figcaption>
        @endif
    </figure>
@endif

@if ($multiple)
    <div class="os-select">
        <span class="os-label" id="os-label-{{ $id }}">Your system</span>
        <div class="tablist tablist-os" data-os-tabs role="tablist" aria-labelledby="os-label-{{ $id }}">
            @foreach ($walkthroughs as $i => $walkthrough)
                <button type="button" role="tab"
                        data-os-values="{{ $walkthrough->value() }}"
                        id="tab-{{ $id }}-os{{ $i }}"
                        aria-controls="panel-{{ $id }}-os{{ $i }}"
                        aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                        tabindex="{{ $i === 0 ? '0' : '-1' }}">{{ $walkthrough->label() }}</button>
            @endforeach
        </div>
    </div>
@endif

@foreach ($walkthroughs as $i => $walkthrough)
    @if ($multiple)
        <div role="tabpanel" class="tabpanel os-panel"
             id="panel-{{ $id }}-os{{ $i }}"
             aria-labelledby="tab-{{ $id }}-os{{ $i }}"
             @if ($i !== 0) hidden @endif>
            @include('mcp-docs::partials.walkthrough', [
                'walkthrough' => $walkthrough,
                'id' => $id.'-os'.$i,
                'docsUrl' => $docsUrl,
                'clientLabel' => $clientLabel,
            ])
        </div>
    @else
        @include('mcp-docs::partials.walkthrough', [
            'walkthrough' => $walkthrough,
            'id' => $id,
            'docsUrl' => $docsUrl,
            'clientLabel' => $clientLabel,
        ])
    @endif
@endforeach
