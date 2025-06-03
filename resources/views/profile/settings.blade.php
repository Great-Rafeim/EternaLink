<x-app-layout>
    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 text-white">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <!-- Sidebar Tabs -->
            <div class="bg-gray-800 p-4 rounded-xl shadow-lg">
                <ul class="space-y-2">
                    <li><a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded hover:bg-gray-700 {{ request()->routeIs('profile.edit') ? 'bg-gray-700 font-semibold' : '' }}">Profile Info</a></li>
                    <li><a href="{{ route('profile.security') }}" class="block px-3 py-2 rounded hover:bg-gray-700 {{ request()->routeIs('profile.security') ? 'bg-gray-700 font-semibold' : '' }}">Security</a></li>
                </ul>
            </div>

            <!-- Main Content Area -->
            <div class="md:col-span-3 bg-gray-900 p-6 rounded-xl shadow-lg">
                @yield('content')
            </div>
        </div>
    </div>
</x-app-layout>
