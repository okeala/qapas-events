@props(['model', 'label', 'type'=>'text', 'options'=>null])
@php($fieldId = 'planner-'.str_replace('.', '-', $model))
<label class="planner-field" for="{{ $fieldId }}">
    <span>{{ $label }}</span>
    @if($type === 'checkbox')
        <input id="{{ $fieldId }}" type="checkbox" wire:model="{{ $model }}" {{ $attributes }}>
    @elseif($options !== null)
        <select id="{{ $fieldId }}" wire:model.live="{{ $model }}" {{ $attributes }}>
            @foreach($options as $value=>$text)<option value="{{ $value }}">{{ $text }}</option>@endforeach
        </select>
    @elseif($type === 'textarea')
        <textarea id="{{ $fieldId }}" rows="3" wire:model="{{ $model }}" {{ $attributes }}></textarea>
    @else
        <input id="{{ $fieldId }}" type="{{ $type }}" wire:model="{{ $model }}" {{ $attributes }}>
    @endif
    @error($model)<small class="planner-error">{{ $message }}</small>@enderror
</label>
