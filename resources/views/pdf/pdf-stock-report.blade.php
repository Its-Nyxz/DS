<!DOCTYPE html>
<html>

<head>
    <title>Data Stock</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
        }
    </style>
</head>

<body>
    <h1>Data Stock</h1>

    <table>
        <thead>
            <tr>
                <th>Item Name</th>
                <th>SKU</th>
                <th>Current Stock</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($itemsWithStock as $item)
                <tr>
                    <td>{{ $item['item_name'] }}</td>
                    <td>{{ $item['sku'] }}</td>
                    <td>{{ $item['current_stock'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
