{{-- The card that explains processing time, with a service split into busy and free steps. --}}
<!-- steps explainer -->
<aside class="top-6 w-full min-w-0 flex-[1_1_260px] space-y-3.5 rounded-[18px] bg-canvas p-5.5 ring-[1.5px] ring-hairline md:sticky md:max-w-85">
  <div class="flex items-center gap-2.5">
    <span class="rounded-md bg-accent-soft px-2 py-0.5 text-xs font-bold tracking-wide text-accent-ink uppercase">{{ trans_choice(':count step|:count steps', 3) }}</span>
    <h2 class="text-[17px] font-bold tracking-[-0.025em] text-ink">{{ __('Services with steps') }}</h2>
  </div>

  <p class="text-sm leading-normal text-pretty text-body">{{ __('Some services include processing time when your team member can attend to another client. Break a service into steps to manage availability more accurately.') }}</p>

  <div class="space-y-1.5">
    <div class="flex justify-between text-xs text-muted">
      <span>{{ __('Full Color') }}</span>
      <span class="tabular-nums">{{ trans_choice(':count min|:count min', 90) }}</span>
    </div>
    <div class="flex h-6.5 gap-0.75" aria-hidden="true">
      <span class="flex-1 rounded-md bg-ink"></span>
      <span class="flex-1 rounded-md bg-page ring-[1.5px] ring-hairline-strong ring-inset"></span>
      <span class="flex-1 rounded-md bg-ink"></span>
    </div>
    <div class="flex flex-wrap gap-3.5 text-xs text-body">
      <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-[3px] bg-ink" aria-hidden="true"></span>{{ __('Team member busy') }}</span>
      <span class="flex items-center gap-1.5"><span class="size-2.5 rounded-[3px] bg-page ring-[1.5px] ring-hairline-strong ring-inset" aria-hidden="true"></span>{{ __('Free for another client') }}</span>
    </div>
  </div>

  <p class="text-xs leading-normal text-pretty text-muted">{{ __('The steps on Full Color, Highlights and Deep Conditioning are examples. Check the processing times against how your salon works.') }}</p>
</aside>
