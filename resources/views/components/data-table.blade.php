@props([
    'id' => null,
    'class' => '',
    'order' => null,
    'noExport' => '',
])

<div class="table-responsive">
    <table
        @if($id) id="{{ $id }}" @endif
        {{ $attributes->merge([
            'class' => trim('table align-middle data-table '.$class),
            'data-order' => $order,
            'data-no-export' => $noExport,
        ]) }}
    >
        {{ $slot }}
    </table>
</div>
