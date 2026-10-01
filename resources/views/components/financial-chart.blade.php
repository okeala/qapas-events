@props(['title','labels','series','bars'=>false])
@php
 $values=collect($series)->flatMap(fn($s)=>$s['values']);$lo=min(0,$values->min()??0);$hi=max(1,$values->max()??1);$span=max(1,$hi-$lo);$count=max(1,count($labels));
 $y=fn($n)=>round(180-($n-$lo)/$span*155,2);$x=fn($i)=>round(50+($i+.5)*650/$count,2);$zero=$y(0);$width=min(22,520/$count/max(1,count($series)));
@endphp
<figure class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 bg-white text-gray-900 dark:bg-gray-900 dark:text-white">
<figcaption class="font-semibold mb-3">{{ $title }}</figcaption>
<svg viewBox="0 0 740 220" role="img" aria-label="{{ $title }} · valeurs détaillées dans les tableaux" class="w-full" style="min-height:180px">
<line x1="48" x2="710" y1="{{ $zero }}" y2="{{ $zero }}" stroke="currentColor" opacity=".35"/>
@foreach([$lo,($lo+$hi)/2,$hi] as $tick)<text x="2" y="{{ $y($tick)+3 }}" fill="currentColor" font-size="10">{{ number_format($tick/100,0,',',' ') }} €</text>@endforeach
@foreach($series as $s)
@if($bars)
@foreach($s['values'] as $i=>$value)<rect x="{{ $x($i)+($loop->parent->index-(count($series)-1)/2)*($width+2)-$width/2 }}" y="{{ min($zero,$y($value)) }}" width="{{ $width }}" height="{{ max(.5,abs($zero-$y($value))) }}" fill="{{ $s['color'] }}"><title>{{ $labels[$i] }} · {{ $s['label'] }} : {{ \App\Domain\Finance\Money::format($value) }}</title></rect>@endforeach
@else
<polyline fill="none" stroke="{{ $s['color'] }}" stroke-width="2.5" points="{{ collect($s['values'])->map(fn($n,$i)=>$x($i).','.$y($n))->implode(' ') }}"/>
@foreach($s['values'] as $i=>$value)<circle cx="{{ $x($i) }}" cy="{{ $y($value) }}" r="3" fill="{{ $s['color'] }}"><title>{{ $labels[$i] }} · {{ $s['label'] }} : {{ \App\Domain\Finance\Money::format($value) }}</title></circle>@endforeach
@endif
@endforeach
@foreach($labels as $i=>$label)<text x="{{ $x($i) }}" y="205" text-anchor="middle" fill="currentColor" font-size="10">{{ $label }}</text>@endforeach
</svg><ul class="flex flex-wrap gap-4 text-sm">@foreach($series as $s)<li><span aria-hidden="true" style="display:inline-block;width:12px;height:12px;background:{{ $s['color'] }}"></span> {{ $s['label'] }}</li>@endforeach</ul>
</figure>
