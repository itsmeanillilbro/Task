<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Resume Details') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- resume Basic Info -->
                    <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                        <div class="flex items-center space-x-4">
                            <div class="flex-shrink-0">
                                <svg class="h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h1 class="text-2xl font-bold text-gray-900 truncate">
                                    {{ $resume->name }}
                                </h1>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <svg class="-ml-0.5 mr-1.5 h-2 w-2 text-blue-400" fill="currentColor" viewBox="0 0 8 8">
                                            <circle cx="4" cy="4" r="3"></circle>
                                        </svg>
                                        Score: {{ $resume->total_score }}
                                    </span>
                                    <a href="mailto:{{ $resume->email }}" class="inline-flex items-center text-sm text-gray-500 hover:text-gray-700">
                                        <svg class="mr-1.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        {{ $resume->email }}
                                    </a>
                                </div>
                            </div>
                            
                        </div>
                    </div>

                    <!-- Skills Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Skills</h3>
                        <div class="flex flex-wrap gap-2">
                            @forelse($resume->skills as $skill)
                                <span class="px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800 flex items-center">
                                    {{ $skill->skill }}
                                    <span class="ml-1 text-xs bg-indigo-200 rounded-full px-2 py-0.5">
                                        {{ $skill->points }}
                                    </span>
                                </span>
                            @empty
                                <p class="text-gray-500">No skills listed</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Experience Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Experience</h3>
                        @if($resume->experience)
                            <div class="prose max-w-none">
                                {!! nl2br(e($resume->experience)) !!}
                            </div>
                        @else
                            <p class="text-gray-500">No experience listed</p>
                        @endif
                    </div>

                    <!-- Education Section -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 border-b pb-2 mb-4">Education</h3>
                        @if($resume->education)
                            <div class="prose max-w-none">
                                {!! nl2br(e($resume->education)) !!}
                            </div>
                        @else
                            <p class="text-gray-500">No education listed</p>
                        @endif
                    </div>

                    <!-- Additional Actions -->
                    <div class="mt-6 pt-6 border-t flex justify-end space-x-3">
                        <form action="{{ route('resume.destroy', $resume->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this resume?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Delete resume
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>