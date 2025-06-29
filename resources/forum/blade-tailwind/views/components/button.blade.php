<button {{ $attributes->merge(['class' => 'bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-md inline-block transition-colors duration-200 shadow-sm hover:shadow-md']) }}>
    {{ $slot }}
</button>
