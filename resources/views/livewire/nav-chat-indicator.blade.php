<div>
  @if ($variant === 'desktop')
  <a href="/chat" class="block px-4 py-2 text-sm bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">
    <span>Messagerie</span>
    @if ($unseen > 0)
    <span class="ml-2 inline-flex items-center justify-center align-middle h-5 min-w-[1.25rem] px-1 rounded-full text-[11px] font-semibold bg-red-600 text-white">{{ $unseen }}</span>
    @endif
  </a>
  @else
  <a href="/chat" class="relative flex items-center py-2 px-2 rounded-lg text-sm text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-blue-600">
    <i class="fas fa-envelope text-lg mr-2"></i>
    @if ($unseen > 0)
    <span class="absolute -top-1 -right-1 h-4 min-w-[1rem] px-1 rounded-full bg-red-600 text-white text-[10px] leading-4 text-center">{{ $unseen }}</span>
    @endif
  </a>
  @endif
</div>