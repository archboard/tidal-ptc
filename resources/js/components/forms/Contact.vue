<template>
  <form class="space-y-8 divide-y divide-gray-200">
    <div class="space-y-8 divide-y divide-gray-200">
      <div>
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">Profile</h3>
          <p class="mt-1 text-sm text-gray-500">This information will be displayed publicly so be careful what you share.</p>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
          <div class="sm:col-span-6">
            <label for="photo" class="block text-sm font-medium text-gray-700"> Photo </label>
            <div class="mt-1 flex items-center">
              <span class="h-12 w-12 rounded-full overflow-hidden bg-gray-100">
                <svg class="h-full w-full text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 20.993V24H0v-2.996A14.977 14.977 0 0112.004 15c4.904 0 9.26 2.354 11.996 5.993zM16.002 8.999a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
              </span>
              <button type="button" class="ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Change</button>
            </div>
          </div>          
        </div>
      </div>

      <div class="pt-8">

      <!-- Email and phone details  -->
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">Contact Details</h3>
        </div>
        <div class="mt-4 mb-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">

          <div class="sm:col-span-2">
            <label for="phone" class="block text-sm font-medium text-gray-700"> Cell Phone (with country code)</label>
            <div class="mt-1">
              <input id="phone" name="cellPhone" type="number" autocomplete="phone" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="email" class="block text-sm font-medium text-gray-700"> Work email </label>
            <div class="mt-1">
              <input id="email" name="workEmail" type="email" autocomplete="email" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="email" class="block text-sm font-medium text-gray-700"> Personal email </label>
            <div class="mt-1">
              <input id="email" name="personalEmail" type="email" autocomplete="email" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>
          
        </div>

      <!-- Mailing Address -->
        <div>
          <h3 class="text-lg leading-6 font-medium text-gray-900">Mailing Address</h3>
          <p class="mt-1 text-sm text-gray-500">Use a permanent address where you can receive mail.</p>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
          <div class="sm:col-span-3">
            <label for="first-name" class="block text-sm font-medium text-gray-700"> First name </label>
            <div class="mt-1">
              <input type="text" name="first-name" id="first-name" autocomplete="given-name" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-3">
            <label for="last-name" class="block text-sm font-medium text-gray-700"> Last name </label>
            <div class="mt-1">
              <input type="text" name="last-name" id="last-name" autocomplete="family-name" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-4">
            <label for="street-address" class="block text-sm font-medium text-gray-700"> Street address </label>
            <div class="mt-1">
              <input type="text" name="street-address" id="street-address" autocomplete="street-address" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="city" class="block text-sm font-medium text-gray-700"> City </label>
            <div class="mt-1">
              <input type="text" name="city" id="city" autocomplete="address-level2" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="region" class="block text-sm font-medium text-gray-700"> State / Province </label>
            <div class="mt-1">
              <input type="text" name="region" id="region" autocomplete="address-level1" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="postal-code" class="block text-sm font-medium text-gray-700"> ZIP / Postal code </label>
            <div class="mt-1">
              <input type="text" name="postal-code" id="postal-code" autocomplete="postal-code" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" />
            </div>
          </div>

          <div class="sm:col-span-2">
            <label for="country" class="block text-sm font-medium text-gray-700"> Country </label>
            <div class="mt-1">
              <select id="country" name="country" autocomplete="country-name" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md">
                <option>China</option>
                <option>UAE</option>
                <option>United States</option>
                <option>Canada</option>
                <option>Mexico</option>
              </select>
            </div>
          </div>   

        </div>
      </div>      
    </div>

    <div class="pt-5">
      <div class="flex justify-start">
        <button type="submit" class="py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Save</button>
        <button type="button" class="ml-3 inline-flex justify-center bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">Cancel</button>
      </div>
    </div>
  </form>
</template>

<script setup>

</script>