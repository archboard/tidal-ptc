<!-- This example requires Tailwind CSS v2.0+ -->
<template>
  <div class="bg-white px-4 py-5 border-b border-gray-200 sm:px-6">
    <div class="-ml-4 -mt-4 flex justify-between items-center flex-wrap sm:flex-nowrap">
      <div class="ml-4 mt-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <img class="h-12 w-12 rounded-full" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="" />
          </div>
          <div class="ml-4">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Tom Cook</h3>
            <!-- <p class="text-sm text-gray-500">
              <a href="#"> @tom_cook </a>
            </p> -->
          </div>
        </div>
      </div>
      <!-- <div class="ml-4 mt-4 flex-shrink-0 flex">
        <button type="button" class="relative inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          <PhoneIcon class="-ml-1 mr-2 h-5 w-5 text-gray-400" aria-hidden="true" />
          <span> Phone </span>
        </button>
        <button type="button" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
          <MailIcon class="-ml-1 mr-2 h-5 w-5 text-gray-400" aria-hidden="true" />
          <span> Email </span>
        </button>
      </div> -->
    </div>
  </div>
    <div class="px-4 py-5 sm:px-6">
      <h3 class="text-lg leading-6 font-medium text-gray-900">Qualifications</h3>
    </div>
  <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
      <dl class="grid grid-cols-1 gap-x-4 gap-y-8 sm:grid-cols-3">
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Degree</dt>
          <dd class="mt-1 text-sm text-gray-900">BM of Music</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">University</dt>
          <dd class="mt-1 text-sm text-gray-900">Cedarville</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Graduation Year</dt>
          <dd class="mt-1 text-sm text-gray-900">2012</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Certification</dt>
          <dd class="mt-1 text-sm text-gray-900">Music Teacher</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Certified By</dt>
          <dd class="mt-1 text-sm text-gray-900">ACIS</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Certification Year</dt>
          <dd class="mt-1 text-sm text-gray-900">2018</dd>
        </div>
        <!-- <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Issued By</dt>
          <dd class="mt-1 text-sm text-gray-900">NA</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Type of Hukou</dt>
          <dd class="mt-1 text-sm text-gray-900">Resident</dd>
        </div>
        <div class="sm:col-span-1">
        <dt class="text-sm font-medium text-gray-500">Hukou Address</dt>
          <dd class="mt-1 text-sm text-gray-900">District Building 3818</dd>
          <dd class="mt-1 text-sm text-gray-900">Apartment 212</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Hukou City</dt>
          <dd class="mt-1 text-sm text-gray-900">Tianjin</dd>
        </div>
        <div class="sm:col-span-1">
          <dt class="text-sm font-medium text-gray-500">Hukou Issued By</dt>
          <dd class="mt-1 text-sm text-gray-900">Tianjin Government</dd>
        </div> -->
      </dl>
    </div>
</template>

<script setup>
import { MailIcon, PhoneIcon } from '@heroicons/vue/24/solid'
</script>
