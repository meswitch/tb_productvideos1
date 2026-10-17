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

  <div class="form-group">
    <label>{l s='...or upload a video file' mod='product_video'}</label>
    <input type="file" name="pv_video_file" id="pv_video_file" class="form-control" accept=".mp4,.webm,.ogv,video/*" />
    <span class="help-block">
      {l s='Uploads are stored on your server under img/pv/ and replace the URL above. Allowed formats: mp4, webm, ogv — max %d MB (also limited by your PHP settings: upload_max_filesize / post_max_size).' sprintf=$pv_max_size_mb mod='product_video'}
    </span>
    {if $pv_is_local}
      <p class="bg-success" style="padding:6px 10px; margin-top:6px;">
        <i class="icon-film"></i>
        {l s='Current video (uploaded file):' mod='product_video'} <code>{$pv_video_url|escape:'html':'UTF-8'}</code>
      </p>
    {/if}
  </div>

  <input type="hidden" name="pv_save_product_video" value="1" />
  <p class="submit">
    <button type="submit" name="submitAddProduct" class="btn btn-default pull-right">
      <i class="process-icon-save"></i> {l s='Save' mod='product_video'}
    </button>
  </p>
</div>
