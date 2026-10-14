{{-- What the application says back after a save, at the foot of the screen. --}}
@php
  $toasts = collect([
      [
          'title' => session('status'),
          'description' => session('status_description'),
          'icon' => 'check',
          'tint' => 'bg-success/20 text-success',
          'bar' => 'bg-success',
      ],
      [
          'title' => session('error'),
          'description' => session('error_description'),
          'icon' => 'alert',
          'tint' => 'bg-error/20 text-error',
          'bar' => 'bg-error',
      ],
  ])->filter(fn (array $toast): bool => filled($toast['title']));
@endphp

<div class="pointer-events-none fixed inset-x-0 bottom-0 z-50 flex flex-col items-center px-4 pb-8.5" role="status" aria-live="polite">
  <div x-sync x-merge="replace" id="notifications" class="pointer-events-auto flex w-full max-w-115 flex-col gap-3">
    @foreach ($toasts as $toast)
      <div
        x-data="{
          shown: true,
          leaving: false,
          paused: false,
          remaining: 4500,
          started: 0,
          timer: null,
          run() { this.paused = false; this.started = Date.now(); this.timer = setTimeout(() => this.dismiss(), this.remaining) },
          pause() { if (this.paused) return; this.paused = true; clearTimeout(this.timer); this.remaining -= Date.now() - this.started },
          resume() { this.$nextTick(() => { if (! this.paused || this.$el.matches(':hover, :focus-within')) return; this.run() }) },
          dismiss() { this.leaving = true; setTimeout(() => this.shown = false, 200) },
        }"
        x-init="run()"
        x-on:mouseenter="pause()"
        x-on:mouseleave="resume()"
        x-on:focusin="pause()"
        x-on:focusout="resume()"
        x-show="shown"
        :class="leaving ? 'animate-toast-out' : 'animate-toast-in'"
        class="relative flex items-start gap-4 overflow-hidden rounded-2xl bg-canvas px-4.5 pt-4.5 pb-5 shadow-2xl ring-[1.5px] ring-hairline"
      >
        <div class="relative flex size-8.5 shrink-0 items-center justify-center rounded-full {{ $toast['tint'] }}">
          <span class="animate-toast-ring absolute inset-0 rounded-full {{ $toast['tint'] }}" aria-hidden="true"></span>

          @if ($toast['icon'] === 'check')
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="relative" aria-hidden="true">
              <path class="animate-toast-tick" d="M3.6 8.4 6.4 11.2 12.4 5.2" stroke-dasharray="22"></path>
            </svg>
          @else
            <svg width="18" height="18" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" class="relative" aria-hidden="true">
              <circle cx="8" cy="8" r="6"></circle>
              <line x1="8" y1="4.8" x2="8" y2="8.8"></line>
              <circle cx="8" cy="11.2" r="0.5" fill="currentColor" stroke="none"></circle>
            </svg>
          @endif
        </div>

        <div class="min-w-0 flex-1">
          <p class="text-[17px] font-bold tracking-tight text-ink">{{ $toast['title'] }}</p>

          @if ($toast['description'])
            <p class="mt-1 text-base leading-relaxed text-pretty text-body">{{ $toast['description'] }}</p>
          @endif
        </div>

        <button
          type="button"
          x-on:click="dismiss()"
          aria-label="{{ __('Dismiss') }}"
          class="flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-lg text-muted transition-colors hover:bg-hover hover:text-ink focus-visible:ring-2 focus-visible:ring-focus-ring focus-visible:outline-none"
        >
          <svg width="13" height="13" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true">
            <line x1="3.6" y1="3.6" x2="12.4" y2="12.4"></line>
            <line x1="12.4" y1="3.6" x2="3.6" y2="12.4"></line>
          </svg>
        </button>

        <span x-show="! leaving" :style="paused && 'animation-play-state: paused'" class="animate-toast-timer absolute inset-x-0 bottom-0 h-1 origin-left {{ $toast['bar'] }}" aria-hidden="true"></span>
      </div>
    @endforeach
  </div>
</div>
