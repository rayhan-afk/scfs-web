<div x-data="{ open: false }" class="relative">

    <button 
        @click="open = !open"
        class="relative p-2 rounded-xl hover:bg-gray-100 transition"
    >
        <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11
                a6.002 6.002 0 00-4-5.659V5
                a2 2 0 10-4 0v.341C7.67 6.165
                6 8.388 6 11v3.159c0 .538-.214 1.055-.595
                1.436L4 17h5m6 0v1a3 3 0 11-6
                0v-1m6 0H9"/>
        </svg>

        @if(auth()->user()->unreadNotifications->count())
            <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] min-w-[18px] h-[18px] rounded-full flex items-center justify-center font-bold">
                {{ auth()->user()->unreadNotifications->count() }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        @click.away="open = false"
        x-transition
        class="absolute right-0 mt-3 w-[380px] bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden z-50"
        style="display:none;"
    >

        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">

            <div>
                <h3 class="font-bold text-gray-800">
                    Notifications
                </h3>

                <p class="text-xs text-gray-400">
                    Realtime activity SCFS
                </p>
            </div>

            @if(auth()->user()->unreadNotifications->count())

                <form method="POST" action="{{ route('notifications.readAll') }}">
                    @csrf

                    <button 
                        class="text-xs text-blue-600 font-semibold hover:text-blue-800"
                    >
                        Tandai semua
                    </button>
                </form>

            @endif

        </div>

        <div class="max-h-[450px] overflow-y-auto">

            @forelse(auth()->user()->notifications->take(10) as $notification)

                <div
                     onclick="window.location='{{ $notification->data['url'] ?? '#' }}'"
                    class="px-5 py-4 border-b border-gray-50 hover:bg-blue-50 transition cursor-pointer {{ is_null($notification->read_at) ? 'bg-blue-50/40' : 'bg-white' }}"
                >

                    <div class="flex gap-3">

                        <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0 text-lg">
                            🔔
                        </div>

                        <div class="flex-1">

                            <div class="flex justify-between items-start gap-3">

                                <div>
                                   <h4 class="text-sm font-bold text-gray-800">
                                        {{ $notification->data['title'] ?? 'Notifikasi Sistem' }}
                                    </h4>

                                    <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                        {{ $notification->data['message'] ?? 'Aktivitas baru tersedia.' }}
                                    </p>
                                </div>

                                @if(is_null($notification->read_at))
                                    <span class="w-2 h-2 rounded-full bg-blue-500 mt-1"></span>
                                @endif

                            </div>

                            <div class="flex items-center justify-between mt-3">

                                <span class="text-[11px] text-gray-400">
                                    {{ $notification->created_at->diffForHumans() }}
                                </span>

                                @if(is_null($notification->read_at))

                                    <form 
                                        method="POST"
                                        action="{{ route('notifications.read', $notification->id) }}"
                                        onclick="event.stopPropagation()"
                                    >
                                        @csrf

                                        <button 
                                            class="text-[11px] text-blue-600 font-semibold hover:text-blue-800"
                                        >
                                            Tandai dibaca
                                        </button>
                                    </form>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="py-16 text-center">

                    <div class="text-5xl mb-3">
                        🔕
                    </div>

                    <p class="text-sm font-medium text-gray-500">
                        Belum ada notifikasi
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>