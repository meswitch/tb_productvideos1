{**
 * Product Video - back-office field rendered on the product page.
 * Hook: displayAdminProductsExtra
*}
<div class="panel" id="product-video-panel">
  <div class="panel-heading">
    {l s='Product Video' mod='product_video'}
  </div>
  <div class="form-group">
    <label>{l s='Video URL' mod='product_video'}</label>
    <input type="text"
           class="form-control"
           name="pv_video_url"
           id="pv_video_url"
           value="{$pv_video_url|escape:'html':'UTF-8'}"
           placeholder="https://www.youtube.com/watch?v=... | https://vimeo.com/... | https://.../video.mp4" />
    <span class="help-block">
      {l s='Supported: YouTube, Vimeo, Dailymotion links, or a direct .mp4 / .webm / .ogv file URL. Leave empty to remove the video.' mod='product_video'}
    </span>
  </div>
  <input type="hidden" name="pv_save_product_video" value="1" />
  <p class="submit">
    <button type="submit" name="submitAddProduct" class="btn btn-default pull-right">
      <i class="process-icon-save"></i> {l s='Save' mod='product_video'}
    </button>
  </p>
</div>
