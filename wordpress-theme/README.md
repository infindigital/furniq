# Furniq Fusion – WordPress theme

The furniqfusion.com design as a normal WordPress theme. Pages look exactly like the
original site. Products come from **WooCommerce**, blog posts are normal WordPress
**Posts**, and every text, image, button and link can be changed in
**Appearance → Customize**. Elementor is **not** needed.

`furniq-fusion.zip` in this folder is the ready-to-upload theme.

## Install

1. **Plugins → Add New** → install and activate **WooCommerce** (free).
2. **Appearance → Themes → Add New → Upload Theme** → choose `furniq-fusion.zip` → **Install** → **Activate**.

   When the theme is activated it creates (or reuses) the Home, About, Blog, FAQ and
   Contact pages, sets Home as the front page and Blog as the posts page, and builds the
   header menu (Home, About, Shop, Blog, FAQ, Contact). Nothing existing is deleted.
3. **Optional – start with the original 147 products:** the dashboard shows a
   *Furniq Fusion* notice with a **Download products file** button. Then go to
   **Products → Import**, upload that file, click **Continue** and **Run the importer**.
   Product photos are downloaded from furniqfusion.com, so that site must still be
   online while you import.
4. **Settings → Permalinks** → choose **Post name** → **Save** (gives `/shop/`, `/about/` … URLs).

## Switching your existing furniqfusion.com site to this theme

Your products, categories, posts, pages, media and orders stay exactly as they are –
the theme never adds, changes or deletes products (the "Download products file" notice
only appears on a site with no products at all).

1. **Back up the site first** (your host's backup or a plugin like UpdraftPlus).
2. Upload and activate the theme. Your existing Home, About, Blog, FAQ and Contact
   pages are reused and shown in the Furniq design; the Blog page starts listing your
   posts. WooCommerce stays as it is.
3. Check the site, then you can deactivate **Elementor, Pro Elements, ElementsKit and
   Happy Addons** – the theme contains everything it needs from them. (Keep them if you
   use them for other pages.) Deactivating does not delete anything.
4. Analytics plugins (Site Kit, MonsterInsights, Meta Pixel…) keep working unchanged.

## Analytics

**Appearance → Customize → Furniq: Analytics & tracking**

- **Google Analytics 4 Measurement ID** (G-XXXXXXXXXX). Leave it empty if a plugin
  such as Site Kit already adds Google Analytics, so visits are not counted twice.
- **Code in &lt;head&gt; / before &lt;/body&gt;** – paste Google Tag Manager, Meta Pixel,
  Microsoft Clarity, Search Console verification tags, etc.
- Automatic events: `generate_lead` (with the product name) when any form is sent and
  `whatsapp_click` when a WhatsApp button is clicked – mark `generate_lead` as a *key
  event* in Google Analytics to count enquiries.

## Add a product

**Products → Add New**

| Original page element      | Where you set it in WooCommerce                          |
|----------------------------|-----------------------------------------------------------|
| Product name (big heading) | Product name                                              |
| Text under the name        | *Product short description* (box under the main editor; if empty, the main description is used) |
| Main photo                 | *Product image* (right column)                            |
| Extra photos / thumbnails  | *Product gallery* (right column)                          |
| Categories / Tags          | *Product categories* / *Product tags* (right column)      |
| Shown on the home page     | Click the ☆ star in **Products → All Products** to make it *featured* |

Prices are optional. With no price the button says **Read more** (like the original);
with a price WooCommerce shows **Add to cart** and the cart/checkout work.

The **Enquire Now** button opens the enquiry popup with the product name filled in.
**Whatsapp Us** goes to the link set in *Customize → Furniq: Contact & social*.

The home page "Our Collections" cards link to the product categories with the slugs
`cot`, `wardrobe`, `chair`, `fusion`, `blinds-and-curtains`, `sofa`, `dining-table`
and `glass-work`. Card texts and icons are editable in the Customizer.

## Add a blog post

**Posts → Add New** – write the title and content and set a **Featured image**.
The post appears as a card on the Blog page (image, date, title, short excerpt,
"Read More") and opens in the same banner + content design. Post categories, tags,
search results and comments work too.

## Change texts, images and links

**Appearance → Customize** has one panel per page:

- **Furniq: Home page / About page / Blog page / FAQ page / Contact page / Shop page /
  Single product page** – one section per page section, every heading, paragraph, button
  text and link, counter number and image.
- **Furniq: Header / Footer / Enquiry popup**.
- **Furniq: Contact & social** – phone, emails, WhatsApp number and message, Facebook,
  Instagram, X and LinkedIn links, and where form enquiries are sent.
- **Site Identity → Logo** – replaces the logo in the header.
- **Menus → Main menu (header)** – the header links (dropdown sub-items are supported).

Leaving a field empty brings back the original text or image.

## Forms

The contact, FAQ and enquiry popup forms are emailed to the site admin (or the address
in *Contact & social*) and also saved in **Tools → Furniq enquiries** with name, email,
phone and product in separate columns. **Download CSV** there exports all of them
(opens in Excel / Google Sheets, or import it into Mailchimp etc.). Nothing is lost if
the host's email isn't set up, but an SMTP plugin (e.g. WP Mail SMTP) is recommended
for reliable email.

Developers can pass each submission elsewhere (e.g. a newsletter list) with the
`furniq_form_submitted` action: `add_action( 'furniq_form_submitted', function ( $fields ) { … } );`

## For developers

The theme is generated from the static copy at the root of this repository:

```bash
python3 tools/build_theme.py          # re-creates parts/generated, inc/fields.php, assets/
python3 tools/export_products_csv.py  # re-exports demo/furniq-products.csv from the live site
```

Hand-written files: `functions.php`, `inc/` (except `fields.php`), the template files,
`parts/*.php` and `assets/css/furniq.css`.
