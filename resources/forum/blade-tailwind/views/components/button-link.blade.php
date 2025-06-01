<a {{ $attributes->merge(['class' => 'bg-gray-300 hover:bg-gray-200 text-gray-600 font-semibold py-2.5 px-5 rounded-lg transition-all duration-200 cursor-pointer inline-flex items-center justify-center shadow-sm hover:shadow-md']) }}>
    {{ $slot }}
</a>
