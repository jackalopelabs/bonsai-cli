@props([
    'rows' => [],
    'columns' => [],
])

<div class="">
    <table {{ $attributes->merge(['class' => 'w-full text-left my-6']) }}>
        <thead>
            <tr>
                @foreach ($columns as $column)
                    <th scope="col" class="border-b border-b-gray-300 bg-gray-100 p-3">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    @foreach ($row as $column)
                        <td class="border-b border-b-gray-300 p-3">{!! $column !!}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
