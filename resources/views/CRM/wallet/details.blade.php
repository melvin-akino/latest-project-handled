<div class="modal fade" id="modal-details" data-backdrop="static">
    <div class="modal-dialog">
        <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Details</h4>
                </div>
                <div class="modal-body" id="details_body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
                </div>

        </div>
    </div>
</div>
<!-- Trigger the Modal -->

<!-- The Modal -->
<div id="myModal" class="modal">

    <!-- The Close Button -->
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span></button>

    <!-- Modal Content (The Image) -->
    <img class="modal-content" id="img01">

    <!-- Modal Caption (Image Text) -->
    <div id="caption"></div>
</div>


@section('script')
    @parent



@endsection
