<div class="container">
    <div class="row">

        <div class="col-md-8 col-lg-9 py-3">

<%--            <% if $HolderPage.ArchiveActive %>--%>
<%--                <small class="text-body-secondary fst-italic me-2">$DateField.Format('d MMMM yyyy')</small>--%>
<%--            <% end_if %>--%>
<%--            <% if $HolderPage.CategoriesActive %>--%>
<%--            <% loop $Categories %>--%>
<%--                <small class="badge fw-normal text-bg-light text-muted border"><a href="$Link" class="text-decoration-none">$Title</a></small>--%>
<%--            <% end_loop %>--%>
<%--            <% end_if %>--%>
<%--            <% if $HolderPage.TagsActive %>--%>
<%--            <% loop $Tags %>--%>
<%--                <small class="badge rounded-pill fw-normal text-bg-light text-muted border text-decoration-none"><a href="$Link" class="text-decoration-none">$Title</a></small>--%>
<%--            <% end_loop %>--%>
<%--            <% end_if %>--%>

            <% include FilterableProperties LinkFilterProps=true %>

            <h1>$Title</h1>
            <% if $FeaturedImage %>{$FeaturedImage.SetWidth(440).setAttribute('class', 'rounded img-fluid float-start col-12 col-lg-7 me-lg-3 mb-2')}<% end_if %>
            $Content
            $Form

            <div class="newsuplink"><a href="$HolderPage.Link" class="text-decoration-none">&larr; $HolderPage.MenuTitle</a></div>

        </div>

        <div class="col-md-4 col-lg-3 py-4">

            <h3>Related</h3>

            <% loop $RelatedItems.limit(3) %>

            <a class="d-flex flex-column gap-3 align-items-top pt-3 pb-2 text-decoration-none border-top" href="$Link">
                <% if $FeaturedImage %>{$FeaturedImage.SetWidth(320).setAttribute('class', 'rounded img-fluid')}<% end_if %>
                <div>
                    <% include FilterableProperties DateBelow=1 %>
                    <h4 class="mb-0">$Title</h4>
                    <p class="text-body">
                        $Content.FirstParagraph
                        <small class="text-muted">&rarr;</small>
                    </p>
                </div>
            </a>

            <% end_loop %>

        </div>

    </div>
</div>
