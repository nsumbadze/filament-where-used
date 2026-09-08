@php
    $groups = $this->getGroups();
@endphp

<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('filament-where-used::where-used.widget.heading')"
        icon="heroicon-o-link"
        collapsible
    >
        @if (empty($groups))
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ __('filament-where-used::where-used.widget.empty') }}
            </p>
        @else
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($groups as $group)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium capitalize text-gray-950 dark:text-white">
                                {{ $group['label'] }}
                            </span>
                            <x-filament::badge color="gray">{{ $group['count'] }}</x-filament::badge>
                        </div>

                        <ul class="mt-3 space-y-1">
                            @foreach ($group['records'] as $item)
                                <li class="truncate text-sm">
                                    @if ($item['url'])
                                        <x-filament::link :href="$item['url']" size="sm">{{ $item['title'] }}</x-filament::link>
                                    @else
                                        <span class="text-gray-600 dark:text-gray-300">{{ $item['title'] }}</span>
                                    @endif
                                </li>
                            @endforeach

                            @if ($group['count'] > count($group['records']))
                                <li class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('filament-where-used::where-used.widget.more', ['count' => $group['count'] - count($group['records'])]) }}
                                </li>
                            @endif
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
