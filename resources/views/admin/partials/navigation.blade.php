@php($navViewModel = app(\App\Services\AdminNavigation\AdminNavigationRenderService::class)->sidebarViewModel())

@include('admin.components.navigation.sidebar', ['viewModel' => $navViewModel])
