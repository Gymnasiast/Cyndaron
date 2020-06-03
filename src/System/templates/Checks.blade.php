@extends ('Index')

@section ('contents')
    @include('Widget/PageTabs', ['subPages' => $pageTabs, 'urlPrefix' => '/system/', 'currentPage' => $currentPage ])

    <div class="container-fluid tab-contents">
        <h3>Maprechten</h3>
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Map</th>
                    <th>Resultaat</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($folderResults as $folder => $result)
                    <tr>
                        <td>{{ $folder }}</td>
                        <td>{{ $result ? 'OK' : 'Incorrect' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection