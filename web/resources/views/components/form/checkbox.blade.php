@props(['name', 'label', 'checked' => false])

<div class="flex items-center">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $name }}"
        value="1"
        {{ old($name, $checked) ? 'checked' : '' }}
        {{ $attributes->merge(['class' => 'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500']) }}
    >
    <label for="{{ $name }}" class="ml-2 block text-sm text-gray-700">
        {{ $label }}
    </label>
</div>
