@extends('admin.layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mb20">
            <h1 class="title-bar">{{__("Edit Review")}}</h1>
            <div class="title-actions">
                <a href="{{ route('review.admin.index') }}" class="btn btn-warning"><i class="fa fa-arrow-left"></i> {{__('Back to Reviews')}}</a>
            </div>
        </div>
        @include('admin.message')
        
        <div class="panel">
            <div class="panel-title"><strong>{{__("Review Details")}}</strong></div>
            <div class="panel-body">
                <form action="{{ route('review.admin.store', $row->id) }}" method="post">
                    @csrf
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>{{__("Title")}}</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $row->title) }}" required>
                            </div>
                            <div class="form-group">
                                <label>{{__("Content")}}</label>
                                <textarea name="content" class="form-control" rows="5" required>{{ old('content', $row->content) }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>{{__("Author Name")}}</label>
                                @php 
                                    $authorNameMeta = $row->getReviewMeta()->where('name', 'author_name')->first();
                                    $authorName = $authorNameMeta ? $authorNameMeta->val : ($row->author ? $row->author->name : '');
                                @endphp
                                <input type="text" name="author_name" class="form-control" value="{{ old('author_name', $authorName) }}" required>
                            </div>
                            <div class="form-group">
                                <label>{{__("Author Email")}}</label>
                                @php $authorEmailMeta = $row->getReviewMeta()->where('name', 'author_email')->first(); @endphp
                                <input type="email" name="author_email" class="form-control" value="{{ old('author_email', $authorEmailMeta ? $authorEmailMeta->val : ($row->author ? $row->author->email : '')) }}">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{__("Rating")}}</label>
                                <select name="rating" class="form-control" required>
                                    @for($i = 1; $i <= 5; $i++)
                                        <option value="{{ $i }}" {{ $row->rate_number == $i ? 'selected' : '' }}>{{ $i }} {{ $i == 1 ? 'Star' : 'Stars' }}</option>
                                    @endfor
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{__("Status")}}</label>
                                <select name="status" class="form-control">
                                    <option value="approved" {{ $row->status == 'approved' ? 'selected' : '' }}>{{__("Approved")}}</option>
                                    <option value="pending" {{ $row->status == 'pending' ? 'selected' : '' }}>{{__("Pending")}}</option>
                                    <option value="spam" {{ $row->status == 'spam' ? 'selected' : '' }}>{{__("Spam")}}</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>{{__("Service Type")}}</label>
                                <input type="text" class="form-control" value="{{ $row->object_model }}" readonly>
                            </div>
                            <div class="form-group">
                                <label>{{__("Service ID")}}</label>
                                <input type="text" class="form-control" value="{{ $row->object_id }}" readonly>
                            </div>
                            @php $service = $row->getService @endphp
                            @if(!empty($service))
                            <div class="form-group">
                                <label>{{__("Related Service")}}</label>
                                <p><a href="{{ $service->getDetailUrl() }}" target="_blank">{{ $service->title }}</a></p>
                            </div>
                            @endif
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="show_on_homepage" value="1" {{ $row->show_on_homepage ? 'checked' : '' }}>
                                    {{__("Show on Homepage")}}
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="show_on_tour_page" value="1" {{ $row->show_on_tour_page ? 'checked' : '' }}>
                                    {{__("Show on Tour Page")}}
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_featured" value="1" {{ $row->is_featured ? 'checked' : '' }}>
                                    {{__("Featured Review")}}
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">
                            {{__("Created")}}: {{ display_datetime($row->created_at) }}
                        </span>
                        <button type="submit" class="btn btn-primary">{{__("Save Changes")}}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
