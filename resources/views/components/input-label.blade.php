@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-lg font-medium text-gray-700 dark:text-gray-300']) }}>
    {{ $value ?? $slot }}
</label>
