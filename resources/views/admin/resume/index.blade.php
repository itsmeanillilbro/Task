<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Manage Resumes') }}
            </h2>
            <div class="flex justify-between items-center gap-3">
            <a href="{{ route('resume.create') }}"
                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                {{ __('Upload New Resumes') }}
            </a>
            <a href="{{ route('resumes.export') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Export to CSV
            </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">


                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Rank
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Skills
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Score
                                    </th>
                                    <th scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($candidates as $candidate)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $loop->iteration }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $candidate->name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            {{ $candidate->email }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($candidate->skills as $skill)
                                                    <span
                                                        class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">
                                                        {{ $skill->skill }} ({{ $skill->points }})
                                                    </span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 bg-green-100 text-green-800 font-bold rounded-full">
                                                {{ $candidate->total_score }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium flex gap-3">
                                            <a href="{{ route('resume.show', $candidate->id) }}" title="view"><img
                                                    src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYCAYAAADgdz34AAAACXBIWXMAAAsTAAALEwEAmpwYAAABH0lEQVR4nO2UvU5CQRCFv4poxELFzkIT8TVAan9ewehDGJFefBsE30ILLbil2lzt4QEwk5xCNzOXxcJQ8CWT3MycM7uZ3buwYtnZBq6AIVAAU0Wh3KU0C7MO9NRsNidMcytPFnvAc0bjWRKvwMG85kfAl2MeAC1gQ9HWiFKdeZtR813gwzFdV2yo6+jfgYYnfgx2btSAe+ATKIG+csbI8T2QcB7MtaV636lZzjgOvKc/FygCUV310qlZztgMvON/XeAsELUrRnSnWifwnqTn4F27oWo1LVI6h1x1OX7R0BVLxV1ieo7+DdiJDM3gRxvpttQVnWDn5j0k46l4+sNT8QLsk8maRjPNaDwBbuRZmC3gQoc2VrOJvgeqmWbFEvMNriXJVcp1zQIAAAAASUVORK5CYII="
                                                    alt="visible"></a>
                                            <form action="{{ route('resume.destroy', $candidate->id) }}" method="POST"
                                                class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button title="delete" onclick="return confirm('Are you sure?')"
                                                    type="submit"><svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px"
                                                        width="20" height="20" viewBox="0 0 30 30">
                                                        <g fill="red">
                                                            <path
                                                                d="M 14.984375 2.4863281 A 1.0001 1.0001 0 0 0 14 3.5 L 14 4 L 8.5 4 A 1.0001 1.0001 0 0 0 7.4863281 5 L 6 5 A 1.0001 1.0001 0 1 0 6 7 L 24 7 A 1.0001 1.0001 0 1 0 24 5 L 22.513672 5 A 1.0001 1.0001 0 0 0 21.5 4 L 16 4 L 16 3.5 A 1.0001 1.0001 0 0 0 14.984375 2.4863281 z M 6 9 L 7.7929688 24.234375 C 7.9109687 25.241375 8.7633438 26 9.7773438 26 L 20.222656 26 C 21.236656 26 22.088031 25.241375 22.207031 24.234375 L 24 9 L 6 9 z">
                                                            </path>
                                                    </svg></button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            No resumes uploaded yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($candidates->hasPages())
                        <div class="mt-4">
                            {{ $candidates->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>