@php
    $items = [];

    if (session('status')) {
        $items[] = [
            'severity' => 'success',
            'title' => 'Operation reussie',
            'message' => (string) session('status'),
            'timeout' => 5000,
        ];
    }

    if (session('error')) {
        $items[] = [
            'severity' => 'critical',
            'title' => 'Action impossible',
            'message' => (string) session('error'),
            'timeout' => 8500,
        ];
    }
@endphp

<x-admin.ui.notifications :items="$items" :floating="true" />
