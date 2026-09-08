@props ([
    'avatarPreviewUrl' => null,
    'coverPreviewUrl' => null,
    'initials' => '',
    'name' => '',
    'birthdateForm' => null,
    'coverAspectRatio',
    'coverFocalY' => 50,
    'avatarFocalY' => 50,
])

<div class="relative rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900">
    {{-- Cover: a proporcao vem do enum, para o recorte nao depender da viewport --}}
    <div
        class="group relative overflow-hidden rounded-t-xl"
        style="aspect-ratio: {{ $coverAspectRatio }}"
        x-data="{ hover: false }"
        @mouseenter="hover = true"
        @mouseleave="hover = false"
    >
        @if ($coverPreviewUrl)
            <img
                src="{{ $coverPreviewUrl }}"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
                style="object-position: center {{ $coverFocalY }}%"
            />
        @else
            <div class="absolute inset-0 bg-gradient-to-br from-purple-600 via-purple-500 to-indigo-600"></div>
        @endif

        <button
            type="button"
            wire:click="mountAction('editCover')"
            class="absolute inset-0 z-10 flex cursor-pointer items-center justify-center transition-colors duration-200"
            :class="hover ? 'bg-black/40' : 'bg-transparent'"
            title="{{ __('panel-app::profile.actions.change_cover') }}"
        >
            <span
                class="flex items-center gap-2 rounded-lg bg-white/90 px-4 py-2 text-sm font-medium text-gray-700 shadow-lg backdrop-blur-sm transition-all duration-200 dark:bg-gray-800/90 dark:text-gray-200"
                :class="hover ? 'opacity-100 scale-100' : 'opacity-0 scale-95'"
            >
                <x-heroicon-m-camera class="h-4 w-4" />
                {{ __('panel-app::profile.actions.change_cover') }}
            </span>
        </button>

        @if ($coverPreviewUrl)
            <button
                type="button"
                wire:click="mountAction('adjustCover')"
                title="{{ __('panel-app::profile.actions.adjust_cover') }}"
                class="absolute top-3 right-12 z-20 rounded-full bg-black/60 p-1.5 text-white transition-opacity hover:bg-black/80 focus-visible:opacity-100"
                :class="hover ? 'opacity-100' : 'opacity-0'"
            >
                <x-heroicon-m-arrows-up-down class="h-4 w-4" />
            </button>

            <button
                type="button"
                wire:click="removeCover"
                class="absolute top-3 right-3 z-20 rounded-full bg-black/60 p-1.5 text-white transition-opacity hover:bg-black/80 focus-visible:opacity-100"
                :class="hover ? 'opacity-100' : 'opacity-0'"
            >
                <x-heroicon-m-x-mark class="h-4 w-4" />
            </button>
        @endif
    </div>

    {{-- Bottom section: avatar + fields --}}
    <div class="relative z-10 px-6 pt-16 pb-6 sm:pt-18">
        {{-- Avatar (overlapping cover) --}}
        <div
            class="absolute -top-12 left-6 z-20 sm:-top-14"
            x-data="{ hover: false }"
            @mouseenter="hover = true"
            @mouseleave="hover = false"
        >
            <div class="relative h-24 w-24 sm:h-28 sm:w-28">
                @if ($avatarPreviewUrl)
                    <img
                        src="{{ $avatarPreviewUrl }}"
                        alt="{{ $name }}"
                        class="h-full w-full rounded-full border-4 border-white object-cover shadow-lg dark:border-gray-800"
                        style="object-position: center {{ $avatarFocalY }}%"
                    />
                @else
                    <div
                        class="flex h-full w-full items-center justify-center rounded-full border-4 border-white bg-purple-600 text-2xl font-bold text-white shadow-lg ring-1 ring-black/5 sm:text-3xl dark:border-gray-800 dark:ring-white/15"
                    >
                        {{ $initials }}
                    </div>
                @endif

                <button
                    type="button"
                    wire:click="mountAction('editAvatar')"
                    class="absolute inset-0 z-10 flex cursor-pointer items-center justify-center rounded-full transition-colors duration-200"
                    :class="hover ? 'bg-black/40' : 'bg-transparent'"
                    title="{{ __('panel-app::profile.actions.change_avatar') }}"
                >
                    <span
                        class="rounded-full bg-white/90 p-2 shadow-md transition-all duration-200 dark:bg-gray-800/90"
                        :class="hover ? 'opacity-100 scale-100' : 'opacity-0 scale-90'"
                    >
                        <x-heroicon-m-camera class="h-5 w-5 text-gray-700 dark:text-gray-200" />
                    </span>
                </button>

                @if ($avatarPreviewUrl)
                    <button
                        type="button"
                        wire:click="removeAvatar"
                        class="absolute -top-1 -right-1 z-20 rounded-full bg-red-500 p-1 text-white shadow-md transition-opacity hover:bg-red-600 focus-visible:opacity-100"
                        :class="hover ? 'opacity-100' : 'opacity-0'"
                    >
                        <x-heroicon-m-x-mark class="h-3.5 w-3.5" />
                    </button>
                @endif
            </div>
        </div>

        {{-- Nickname + Birthdate --}}
        <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2">
            <div>
                <label for="nickname" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('panel-app::profile.fields.nickname') }}
                </label>
                <input
                    id="nickname"
                    type="text"
                    wire:model.blur="data.nickname"
                    placeholder="{{ __('panel-app::profile.placeholders.nickname') }}"
                    maxlength="100"
                    class="fi-input block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm transition-colors focus:border-purple-500 focus:ring-1 focus:ring-purple-500 dark:border-white/10 dark:bg-white/5 dark:text-white dark:focus:border-purple-500"
                />
            </div>
            <div>
                {{ $birthdateForm }}
            </div>
        </div>
    </div>
</div>
