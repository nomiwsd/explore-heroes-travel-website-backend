@extends ('admin.layouts.app')
@section ('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{__('All Tailor Made Tour')}}</h1>
        </div>
        @include('admin.message')
        <div class="filter-div d-flex justify-content-between">
            <div class="col-left">

            </div>
            <div class="col-left">
                <form method="get" action="" class="filter-form filter-form-right d-flex justify-content-end">
                    <input type="text" name="s" value="{{ Request()->s }}" placeholder="{{__('Search by email')}}"
                           class="form-control">
                    <button class="btn-info btn btn-icon" type="submit">{{__('Filter')}}</button>
                </form>
            </div>
        </div>
        <div class="text-right">
            <p><i>{{__('Found :total items',['total'=>$rows->total()])}}</i></p>
        </div>
        <div class="panel booking-history-manager">
            <div class="panel-title">{{__('Tailor Made Tour')}}</div>
            <div class="panel-body">
                <form action="" class="bravo-form-item">
                    <table class="table table-hover bravo-list-item">
                        <thead>
                        <tr>
                            <th width="80px"><input type="checkbox" class="check-all"></th>
                            <th>{{__('Customer')}}</th>
                            <th width="180px">{{__('Created At')}}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($rows->total() > 0)
                            @foreach($rows as $row)
                                <tr>
                                    <td><input type="checkbox" class="check-item" name="ids[]" value="{{$row->id}}">
                                        #{{$row->id}}</td>
                                    <td>
                                        <ul>
                                            <li>{{ __("Salutation:") }} {{$row->salutation}}</li>
                                            <li>{{ __("First Name:") }} {{$row->first_name}}</li>
                                            <li>{{ __("Last Name:") }} {{$row->last_name}}</li>
                                            <li>{{ __("Email:") }} {{$row->email}}</li>
                                            <li>{{ __("Phone:") }} {{$row->phone}}</li>
                                            <li>{{ __("Country:") }} {{$row->country}}</li>
                                            <li>{{ __("Age (13-17):") }} {{$row->age_13_17}}</li>
                                            <li>{{ __("Age (18-25):") }} {{$row->age_18_25}}</li>
                                            <li>{{ __("Age (26-35):") }} {{$row->age_26_35}}</li>
                                            <li>{{ __("Age (36-45):") }} {{$row->age_36_45}}</li>
                                            <li>{{ __("Age (46-55):") }} {{$row->age_46_55}}</li>
                                            <li>{{ __("Age (56-69):") }} {{$row->age_56_69}}</li>
                                            <li>{{ __("Age (70+):") }} {{$row->age_70_above}}</li>
                                            <li>{{ __("Age (Below 2):") }} {{$row->age_below_2}}</li>
                                            <li>{{ __("Age (3-7):") }} {{$row->age_3_7}}</li>
                                            <li>{{ __("Age (8-12):") }} {{$row->age_8_12}}</li>
                                            <li>{{ __("Interests:") }} {{ $row->interests ? implode(', ', json_decode($row->interests)) : 'No interests specified' }}</li>
                                            <li>{{ __("Type of Accommodation:") }} {{$row->type_of_accommodation}}</li>
                                            <li>{{ __("Budget Currency:") }} {{$row->budget_currency}}</li>
                                            <li>{{ __("Budget per Person:") }} {{$row->budget_per_person}}</li>
                                            <li>{{ __("When to Go:") }} {{$row->when_to_go}}</li>
                                            <li>{{ __("Trip Date:") }} {{$row->trip_date}}</li>
                                            <li>{{ __("Number of Nights (Known):") }} {{$row->no_of_nights_known}}</li>
                                            <li>{{ __("Roughly Month:") }} {{$row->roughly_month}}</li>
                                            <li>{{ __("Roughly Year:") }} {{$row->roughly_year}}</li>
                                            <li>{{ __("Number of Nights (Unknown):") }} {{$row->no_of_nights_unknown}}</li>
                                            <li>{{ __("Comments:") }} {{$row->comments}}</li>
                                        </ul>
                                    </td>
                                    <td>{{display_datetime($row->updated_at)}}</td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6">{{__("No data")}}</td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        <div class="d-flex justify-content-end">
            {{$rows->links()}}
        </div>
    </div>
@endsection
