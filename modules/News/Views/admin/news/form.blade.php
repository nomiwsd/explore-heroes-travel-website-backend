<div class="form-group magic-field" data-id="title" data-type="title">
    <label class="control-label">{{ __('Title')}}</label>
    <input type="text" value="{{ $translation->title ?? 'New Post' }}" placeholder="News title" name="title" class="form-control">
</div>
<div class="form-group magic-field" data-id="content" data-type="content" data-editor="1">
    <label class="control-label">{{ __('Content')}} </label>
    <div class="">
        <textarea name="content" class="d-none has-ckeditor" id="content" cols="30" rows="10">{{$translation->content}}</textarea>
    </div>
</div>
@php
    $selectedTourIds = old('tour_ids', $row->exists ? $row->tours->pluck('tour_id')->toArray() : []);
@endphp

<div class="form-group">
    <label>{{ __('Tour') }}</label>
    <select name="tour_ids[]" multiple="multiple" class="form-control multiple-select">
        <?php
        $traverse = function ($tours, $prefix = '') use (&$traverse, $selectedTourIds) {
            foreach ($tours as $tour) {
                $selected = in_array($tour->id, $selectedTourIds) ? 'selected' : '';
                printf("<option value='%s' %s>%s</option>", $tour->id, $selected, $prefix . ' ' . $tour->title);
            }
        };
        $traverse($tours);
        ?>
    </select>
</div>
