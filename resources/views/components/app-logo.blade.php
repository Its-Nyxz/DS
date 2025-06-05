@php
    $company = \App\Models\Companie::first();
    $companyName = $company->name ?? 'Management';
    $initials = collect(explode(' ', $companyName))
        ->filter() // hapus elemen kosong
        ->map(fn($word) => strtoupper(substr($word, 0, 1)))
        ->implode('');
@endphp

<div class="flex aspect-square size-8 items-center justify-center rounded-md bg-accent-content text-accent-foreground">
    <x-app-logo-icon class="size-5 fill-current text-white dark:text-black" />
</div>
<div class="ms-1 grid flex-1 text-start text-sm">
    <span class="font-bold">{{ $initials }}</span>
</div>
