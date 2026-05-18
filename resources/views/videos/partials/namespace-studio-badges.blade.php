@php
    /** @var array{badge_wp:bool,badge_db:bool,badge_index:bool} $r */
@endphp
<span class="inline-flex flex-wrap gap-1">
    @if($r['badge_wp'])
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800" title="Has WordPress post id">WP</span>
    @else
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500" title="No WordPress post id">WP</span>
    @endif
    @if($r['badge_db'])
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800" title="In pipeline videos table">DB</span>
    @else
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-400" title="Unknown">DB</span>
    @endif
    @if($r['badge_index'])
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800" title="Embedding row for this namespace">Idx</span>
    @else
        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500" title="No embedding row for this namespace">Idx</span>
    @endif
</span>
