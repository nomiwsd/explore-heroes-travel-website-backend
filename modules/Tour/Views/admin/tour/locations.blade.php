<div class="form-group-item">
    <label class="control-label">{{ __('Locations') }}</label>

    <div class="g-items-header">
        <div class="row">
            <div class="col-md-2 text-left">{{ __("Image") }}</div>
            <div class="col-md-9 text-left">{{ __("Location") }}</div>
            <div class="col-md-1"></div>
        </div>
    </div>

    <div class="g-items">
        @if(!empty($all_locations))
            @foreach($all_locations as $key => $all_location)
                <div class="item" data-number="{{ $key }}">
                    <div class="row">
                        <div class="col-md-2">
                            {!! \Modules\Media\Helpers\FileHelper::fieldUpload('locations['.$key.'][image_id]', $all_location->image_id ?? '') !!}
                        </div>
                        <div class="col-md-9">
                            <select name="locations[{{ $key }}][location_id]" class="form-control">
                                <option value="">{{ __("-- Please Select --") }}</option>
                                @php
                                    $traverse = function ($locations, $prefix = '') use (&$traverse, $all_location) {
                                        foreach ($locations as $location) {
                                            $selected = $all_location->location_id == $location->id ? 'selected' : '';
                                            echo "<option value='{$location->id}' {$selected}>{$prefix} {$location->name}</option>";
                                            $traverse($location->children, $prefix . '-');
                                        }
                                    };
                                    $traverse($tour_location);
                                @endphp
                            </select>
                        </div>
                        <div class="col-md-1">
                            <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    <div class="text-right mt-2">
        <span class="btn btn-info btn-sm btn-add-item">
            <i class="icon ion-ios-add-circle-outline"></i> {{ __('Add item') }}
        </span>
    </div>

    {{-- Hidden template --}}
    <div class="g-more hide">
        <div class="item" data-number="__number__">
            <div class="row">
                <div class="col-md-2">
                    {!! \Modules\Media\Helpers\FileHelper::fieldUpload('locations[__number__][image_id]', '', '__name__') !!}
                </div>
                <div class="col-md-9">
                    <select name="locations[__number__][location_id]" class="form-control">
                        <option value="">{{ __("-- Please Select --") }}</option>
                        @php
                            $traverse = function ($locations, $prefix = '') use (&$traverse) {
                                foreach ($locations as $location) {
                                    echo "<option value='{$location->id}'>{$prefix} {$location->name}</option>";
                                    $traverse($location->children, $prefix . '-');
                                }
                            };
                            $traverse($tour_location);
                        @endphp
                    </select>
                </div>
                <div class="col-md-1">
                    <span class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></span>
                </div>
            </div>
        </div>
    </div>
</div>
