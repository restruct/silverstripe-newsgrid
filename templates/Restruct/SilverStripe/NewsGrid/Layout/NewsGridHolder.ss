<div class="container">
    <div class="row">

        <div class="col-sm-12">

            <div class="my-3 p-4 p-md-5 rounded text-emphasis bg-light">
                <div class="col-lg-6 px-0">
                    <h1 class="display-4">$Title</h1>
                    <div class="lead my-3">$Content</div>
                </div>
            </div>

            <% include FilterableArchiveFilter %>

            <ul class="list-unstyled my-3">
                <% loop $PaginatedItems %>
                    <li>
                      <a class="d-flex flex-column flex-md-row gap-3 align-items-top align-items-lg-center py-3 text-decoration-none border-top" href="$Link">
                        <% if $FeaturedImage %>{$FeaturedImage.SetWidth(240).setAttribute('class', 'rounded')}<% end_if %>
                        <div class="col-lg-8">

                            <% include FilterableProperties %>

                          <h4 class="mb-0">$Title</h4>
                          <p class="text-body">
                            $Content.FirstParagraph
                            <small class="text-muted">&rarr;</small>
                        </p>
                        </div>
                      </a>
                    </li>
                <% end_loop %>
              </ul>

            <% include FilterableArchiveBootstrapPagination %>

        </div>

    </div>
</div>
