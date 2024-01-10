<% if $RecentNewsItems.count %>
<div class="block-item-wrapper" <% if $BackgroundImage %>style="background-image: url('$BackgroundImage.URL')"<% end_if %>>
    <div class="$SiteConfig.ContainerClass">
        <div class="row">

            <div class="col-12">
                <p class="lead text-uppercase text-center">Gerelateerd nieuws</p>
            </div>

            <% loop $RecentNewsItems %>
            <% include NewsItemTile %>
            <% end_loop %>

        </div>
    </div>
</div>
<% end_if %>
