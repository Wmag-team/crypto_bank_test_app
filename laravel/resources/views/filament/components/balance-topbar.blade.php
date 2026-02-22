@php
    $user = auth()->user();
    if ($user) {
        $user->refresh();
    }
@endphp
@if ($user)
    <div class="fi-topbar-item flex items-center gap-x-2 rtl:gap-x-reverse">
        <span class="fi-topbar-item-label text-sm font-medium text-gray-950 dark:text-white">
            Баланс: {{ number_format((float) $user->balance, 2) }}
        </span>
    </div>
@endif
