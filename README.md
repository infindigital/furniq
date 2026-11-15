# Furniq Fusion — static HTML copy

A static HTML copy of https://furniqfusion.com/: 53 pages (home, about, blog, contact, FAQ, shop, products, categories, tags) with all their CSS, JS, images and fonts.

## Run locally

Asset paths are root-relative (`/wp-content/...`), so serve the folder from a web server rather than opening the files directly:

```bash
python3 -m http.server 8000
```

Then open http://localhost:8000/

## Notes

- Cart, checkout, the contact form and search need the WordPress backend, so in this static copy they don't work on their own. Cart and checkout links still go to the live site.
- Google Fonts, Google Maps and WordPress emoji load from their original hosts, as on the live site.
