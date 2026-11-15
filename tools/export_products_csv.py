#!/usr/bin/env python3
"""
Export every product of furniqfusion.com (public WooCommerce Store API) into a
WooCommerce product CSV that can be imported in Products → Import.
Images are referenced by their furniqfusion.com URL; the importer downloads them.

Usage: python3 tools/export_products_csv.py
Output: wordpress-theme/furniq-fusion/demo/furniq-products.csv
"""
import csv
import html
import json
import os
import re
import urllib.request

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT = os.path.join(ROOT, 'wordpress-theme', 'furniq-fusion', 'demo', 'furniq-products.csv')
API = 'https://furniqfusion.com/wp-json/wc/store/v1/'
UA = {'User-Agent': 'Mozilla/5.0 (furniq theme export)'}


def get(path):
    out, page = [], 1
    while True:
        req = urllib.request.Request('%s%s%sper_page=100&page=%d' % (API, path, '&' if '?' in path else '?', page), headers=UA)
        with urllib.request.urlopen(req, timeout=60) as r:
            data = json.load(r)
            total_pages = int(r.headers.get('X-WP-TotalPages', '1'))
        out += data
        if page >= total_pages:
            return out
        page += 1


def main():
    cats = {c['id']: c for c in get('products/categories')}

    def cat_path(cid):
        names = []
        while cid:
            c = cats[cid]
            names.insert(0, html.unescape(c['name']).replace(',', '\\,'))
            cid = c.get('parent') or 0
        return ' > '.join(names)

    home = open(os.path.join(ROOT, 'index.html'), encoding='utf-8').read()
    ul = home[home.index('<ul class="products elementor-grid'):]
    ul = ul[:ul.index('</ul>')]
    featured = set(int(x) for x in re.findall(r'<li class="product type-product post-(\d+)', ul))

    products = get('products?orderby=menu_order&order=asc')
    os.makedirs(os.path.dirname(OUT), exist_ok=True)
    cols = ['Type', 'SKU', 'Name', 'Published', 'Is featured?', 'Visibility in catalog', 'Short description',
            'Description', 'Tax status', 'In stock?', 'Regular price', 'Categories', 'Tags', 'Images', 'Position']
    with open(OUT, 'w', newline='', encoding='utf-8') as f:
        w = csv.writer(f)
        w.writerow(cols)
        # Position keeps the shop order; rows go oldest first so newer products get
        # newer ids/dates after import (the home page shows the newest featured first).
        positions = {p['id']: i for i, p in enumerate(products)}
        for p in sorted(products, key=lambda x: x['id']):
            pos = positions[p['id']]
            price = p['prices'].get('regular_price') or ''
            minor = int(p['prices'].get('currency_minor_unit') or 0)
            if price and price != '0':
                price = str(int(price) / (10 ** minor)) if minor else price
            else:
                price = ''
            cat_ids = [c['id'] for c in p.get('categories', [])]
            w.writerow([
                'simple', p.get('sku', ''), html.unescape(p['name']), 1,
                1 if p['id'] in featured else 0, 'visible', p.get('short_description', ''), p.get('description', ''),
                'taxable', 1 if p.get('is_in_stock', True) else 0, price,
                ', '.join(cat_path(c) for c in cat_ids if c in cats),
                ', '.join(html.unescape(t['name']).replace(',', '\\,') for t in p.get('tags', [])),
                ', '.join(i['src'] for i in p.get('images', [])), pos,
            ])
    print('products:', len(products), 'featured:', len(featured), '->', OUT)


if __name__ == '__main__':
    main()
