<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <h2 class="text-xl font-semibold mb-4">Upload Progress</h2>
                    
                    <div class="mb-6">
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">Processing resumes...</span>
                            <span class="text-sm font-medium">
                                {{ $batch->processedJobs() }} / {{ $batch->totalJobs }} ({{ $batch->progress() }}%)
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full" 
                                 style="width: {{ $batch->progress() }}%"></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-green-50 p-4 rounded-lg">
                            <p class="text-sm font-medium text-green-800">Processed</p>
                            <p class="text-2xl font-bold text-green-600">{{ $batch->processedJobs() }}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg">
                            <p class="text-sm font-medium text-red-800">Failed</p>
                            <p class="text-2xl font-bold text-red-600">{{ $batch->failedJobs }}</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <p class="text-sm font-medium text-gray-800">Status</p>
                            <p class="text-2xl font-bold text-gray-600">
                                {{ ucfirst($batch->finished() ? 'completed' : 'processing') }}
                            </p>
                        </div>
                    </div>

                    @if($batch->finished())
                        <a href="{{ route('resume.index') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            View Candidates
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @unless($batch->finished())
        <script>
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        </script>
    @endunless
</x-app-layout>