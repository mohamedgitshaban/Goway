<table>
    <thead>
        <tr>
            @foreach(array_keys($items->first()->toArray()) as $col)
                <th>{{ $col }}</th>
            @endforeach
        </tr>
    </thead>

    <tbody>
        @foreach($items as $item)
            <tr>
                @foreach($item->toArray() as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
