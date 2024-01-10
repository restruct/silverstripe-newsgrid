<div class="col-md-6 col-xl-4 newsitem-tile-holder p-3">
    <a class="card d-block text-decoration-none p-3 bg-primary-light _block-item-holder"  href="$Link">
<%--        <% if $FeaturedImage %>{$FeaturedImage.SetWidth(320).setAttribute('class', 'rounded w-100')}<% end_if %>--%>
        <% include FilterableProperties %>
        <h4 class="item-title mb-0" style="min-height: 8rem;">$Title</h4>
<%--        <p class="text-body">--%>
<%--            $Content.FirstParagraph--%>
<%--            <small class="text-muted">&rarr;</small>--%>
<%--        </p>--%>
        <span class="text-body-secondary">Lees meer &rarr;</span>
    </a>
</div>


