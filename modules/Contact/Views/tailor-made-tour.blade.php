@extends('layouts.app')
@push('js')
    <script>
        $(document).ready(function(){
            $('input[type="radio"]').click(function(){
                var inputValue = $(this).attr("value");
                var targetBox = $("." + inputValue);
                $(".box").not(targetBox).hide();
                $(targetBox).show();
            });
        });
    </script>
@endpush

@push('css')
    <style type="text/css">
        .bravo-contact-block .section{
            padding: 80px 0 !important;
        }
        .input-group-btn{
            border: 1px solid #c4c4c4;
        }
        input.form-control {
            height: 45px; !important;
        }
    </style>
@endpush
@section('content')
    <div id="bravo_content-wrapper">
        @include("Contact::frontend.blocks.tailorMadeTour.index")
    </div>
@endsection
