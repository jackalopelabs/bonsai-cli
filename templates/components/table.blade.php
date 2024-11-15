{{-- bonsai-cli/templates/components/table.blade.php --}}
@props([
    'products', // Expecting an array of product details
    'dropdownOptions', // Expecting an array of dropdown options
    'selectedOption' // The currently selected option
])

<div>
    <!-- Dropdown for selection -->
    <div class="mb-4 text-center">
        <div class="inline-block relative">
            <select id="productSelection" name="productSelection" class="mt-1 appearance-none bg-white border-none py-2 pl-3 pr-10 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                @foreach($dropdownOptions as $option)
                    <option value="{{ $option }}" {{ $selectedOption == $option ? 'selected' : '' }}>{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>
    

    <!-- Product Table -->
    <div class="min-w-full align-middle inline-block my-24">
        <table class="min-w-full">
            <thead>
                <tr>
                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Product
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        REF
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Dimensions
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Quantity/Box
                    </th>
                    <th class="px-6 py-3 border-b border-gray-200 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        HCPCS
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white bg-opacity-25 divide-y divide-gray-200">
                @foreach($products as $product)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product['name'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product['ref'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product['dimensions'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product['woundPadSize'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product['quantityPerBox'] }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $product['hcpcs'] }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
