@php
    $details = $getState() ?? [];
    $hasRenderableContent = false;
@endphp

@if (is_array($details) && isset($details['issues']) && is_array($details['issues']) && ! empty($details['issues']))
    <div
        class="check-details-view space-y-2">
        @foreach ($details['issues'] as $keyOrIndex => $issueData)
            @if (is_array($issueData) && isset($issueData['src']) && isset($issueData['type']))
                @php
                    $hasRenderableContent = true;
                @endphp

                <div
                    @class([
                        'rounded p-2 text-sm',
                        'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300' =>
                            ($issueData['level'] ?? null) === 'error',
                        'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300' =>
                            ($issueData['level'] ?? null) === 'warning',
                        'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' =>
                            ! isset($issueData['level']) ||
                            ! in_array($issueData['level'], ['error', 'warning']),
                    ])>
                    <strong>Image Alt Text Issue:</strong>
                    {{ Str::ucfirst($issueData['type']) }} alt attribute
                    <div class="mt-1 truncate text-xs">
                        Src:
                        <code>{{ $issueData['src'] }}</code>
                    </div>
                </div>

            @elseif (is_string($keyOrIndex) && is_array($issueData) && ! empty($issueData))
                @php
                    $hasRenderableContent = true;
                @endphp

                <div class="text-sm">
                    <strong class="text-gray-900 dark:text-white">
                        {{ Str::headline($keyOrIndex) }} Issues:
                    </strong>
                    <ul class="ml-4 list-inside list-disc">
                        @foreach ($issueData as $subIssue)
                            <li class="text-gray-700 dark:text-gray-300">
                                {{ $subIssue }}
                            </li>
                        @endforeach
                    </ul>
                </div>

            @elseif (is_int($keyOrIndex) && is_string($issueData))
                @php
                    $hasRenderableContent = true;
                @endphp

                <div class="text-sm text-gray-700 dark:text-gray-300">
                    - {{ $issueData }}
                </div>

            @elseif (is_array($issueData))
                @php
                    $hasRenderableContent = true;
                @endphp

                <div class="text-sm">
                    <strong class="text-gray-900 dark:text-white">
                        {{ is_string($keyOrIndex) ? Str::headline($keyOrIndex) : "Item {$keyOrIndex}" }}
                        :
                    </strong>
                    <pre
                        class="mt-1 w-full overflow-x-auto rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">
{{ json_encode($issueData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre
                    >
                </div>
            @endif
        @endforeach

        @if (! $hasRenderableContent)
            <span class="text-sm text-gray-500 dark:text-gray-400">
                Unrecognized details structure:
            </span>
            <pre
                class="mt-1 w-full overflow-x-auto rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">
{{ json_encode($details['issues'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre
            >
            @php
                $hasRenderableContent = true;
            @endphp
        @endif
    </div>

@elseif (is_array($details))
    <div
        class="check-details-view">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-400">
            Raw Details:
        </span>
        <pre
            class="mt-1 w-full overflow-x-auto rounded bg-gray-100 p-2 text-xs dark:bg-gray-800">
{{ json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre
        >
        @php
            $hasRenderableContent = true;
        @endphp
    </div>
@endif
