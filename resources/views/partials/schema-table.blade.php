@php
    $hasDetails = collect($rows)->contains(
        fn (array $row): bool => $row['description'] !== null || $row['enum'] !== [] || $row['default'] !== null,
    );
@endphp
<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th scope="col">Name</th>
            <th scope="col">Type</th>
            <th scope="col">Required</th>
            @if ($hasDetails)
                <th scope="col">Description</th>
            @endif
        </tr>
        </thead>
        <tbody>
        @foreach ($rows as $row)
            <tr>
                <td><code>{{ $row['name'] }}</code></td>
                <td><span class="type">{{ $row['type'] }}</span></td>
                <td>{!! $row['required'] ? '<span class="req">yes</span>' : '<span class="opt">no</span>' !!}</td>
                @if ($hasDetails)
                <td>
                    {{ $row['description'] }}
                    @if ($row['enum'])
                        <span class="enum">one of
                            @foreach ($row['enum'] as $value)<code>{{ $value }}</code>@if (! $loop->last), @endif @endforeach
                        </span>
                    @endif
                    @if ($row['default'] !== null)
                        <span class="default">defaults to <code>{{ $row['default'] }}</code></span>
                    @endif
                </td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
