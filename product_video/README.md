# Product Video (ThirtyBeeS CMS module)

Shows a **video inside the product image block** on the product page.

## What it does
- Adds a **"Product Video"** panel on the product edit page in the back office
  (`displayAdminProductsExtra` hook), where you paste a video URL per product.
- Saves the URL in its own table `ps_product_video` (per product / per shop).
- Renders the video **inside the product image block** via the native
  `displayProductImages` hook — as an extra slide next to the product images
  (or below the images, configurable in module settings).
- Supported sources:
  - **YouTube** (watch / youtu.be / embed links → privacy-friendly iframe)
  - **Vimeo**
  - **Dailymotion**
  - **Direct files**: `.mp4`, `.webm`, `.ogv` (HTML5 `<video controls>`)
- Fully responsive 16:9 embed wrapper.

## Installation
1. Zip this folder (or upload it directly): copy `product_video/` into
   `modules/` of your ThirtyBeeS installation.
2. Back office → Modules → search **"Product Video"** → Install.
3. Configure position in the module settings ("In gallery" or "Below images").
4. Open any product → paste a video URL in the *Product Video* panel → Save.

## Hooks used
| Hook | Purpose |
|------|---------|
| `displayProductImages` | Injects the video into the FO product image block |
| `displayAdminProductsExtra` | Adds the video field to the BO product page |
| `actionAdminProductsControllerSaveBefore` | Validates & saves the URL |
| `actionProductDelete` | Cleans up rows when a product is deleted |
| `header` | Loads module CSS on product/category pages |

## Theme compatibility note
`displayProductImages` is fired by ThirtyBeeS core inside the product-images
container. If your custom theme removed that hook from `product.tpl`, add it
back where the images are rendered:

```smarty
{hook h='displayProductImages' id_product=$product.id products=$product}
```

## Files
```
product_video/
├── product_video.php
├── README.md
└── views/
    ├── css/product_video.css
    └── templates/
        ├── admin/video_tab.tpl   (BO field)
        └── hook/product_video.tpl (FO output in image block)
```
