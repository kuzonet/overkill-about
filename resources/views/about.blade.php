{{-- Every value here was resolved and filtered by AboutAction; this view only lays it out. --}}
<div class="flex flex-col gap-4">
    @foreach ($sections as $section)
            <x-filament::callout icon="{{ $section['icon'] }}" color="info">

                <x-slot name="heading">
                    {{ $section['title'] }} 
                    @if( $section['description'])<p class="text-sm text-gray-500 dark:text-gray-400">{{ $section['description'] }}</p>@endif
                </x-slot>

                <x-slot name="description">
                    <div class="space-y-1 pb-5">
                        @foreach ($section['items'] as $item)
                            <p class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                                @if ($item['icon'])
                                    <x-filament::icon :icon="$item['icon']" class="size-4 shrink-0" />
                                @endif

                                <b class="font-semibold text-gray-950 dark:text-white">{{ $item['label'] }}:</b>

                                @if ($item['url'])
                                    <a href="{{ $item['url'] }}"@if ($item['new_tab']) target="_blank" rel="noopener noreferrer" @endif
                                        class="text-primary-600 hover:underline dark:text-primary-400">{{ $item['value'] }}</a>
                                @else
                                    <span>{{ $item['value'] }}</span>
                                @endif
                            </p>
                        @endforeach
                    </div>
                </x-slot>

            </x-filament::callout>
    @endforeach
</div>
