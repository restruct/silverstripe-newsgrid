<% if $RecentNewsItems.count %>
<div class="block-item-wrapper" <% if $BackgroundImage %>style="background-image: url('$BackgroundImage.URL')"<% end_if %>>
    <div class="$SiteConfig.ContainerClass">
        <div class="row">

            <% if $Heading %>
            <div class="col-12">
                <p class="lead text-uppercase text-center">{$Heading}</p>
            </div>
            <% end_if %>

            <% loop $RecentNewsItems %>
            <% include NewsItemTile %>
            <% end_loop %>

            <% if $NewsSectionLink %>
            <% with $NewsSectionLink %>
            <div class="col-12 text-center">
                <a class="btn btn-secondary px-3" href="$Link">$LinkLabel &rarr;</a>
            </div>
            <% end_with %>
            <% end_if %>

        </div>
    </div>
</div>
<% end_if %>
