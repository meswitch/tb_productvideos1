{**
 * Product Video - front-office output inside the product image block.
 * Hook: displayProductImages
*}
{if $pv_video_html}
  <div class="product-video {if $pv_video_position == 'gallery'}product-video--gallery{else}product-video--below{/if}">
    <div class="product-video__frame">
      {$pv_video_html nofilter}
    </div>
  </div>
{/if}
