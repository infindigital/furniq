#!/usr/bin/env python3
"""
Build the "Furniq Fusion" WordPress theme from the static HTML copy of
furniqfusion.com that lives at the root of this repository.

What it does
  * cuts every captured page into its Elementor regions (header, page body,
    footer, popup) and writes them out as PHP template parts
  * turns every heading, paragraph, button, counter, image and icon-list item
    into a Customizer field whose default is the original content
  * replaces the WooCommerce product loops, the single product details and the
    blog post list with live WordPress / WooCommerce loops
  * copies only the CSS / JS / fonts / images the pages need into the theme

Usage:  python3 tools/build_theme.py
Output: wordpress-theme/furniq-fusion/  (hand-written PHP files in that folder
        are left alone; generated files live in parts/generated, inc/fields.php
        and assets/)
"""
import html
import json
import os
import re
import shutil
import sys
import urllib.parse

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
THEME = os.path.join(ROOT, 'wordpress-theme', 'furniq-fusion')
GEN = os.path.join(THEME, 'parts', 'generated')
ASSETS = os.path.join(THEME, 'assets')

PAGES = {
    # key: (source html, main region type, header variant)
    'home':    ('index.html', 'wp-page', 'overlay'),
    'about':   ('about/index.html', 'wp-page', 'overlay'),
    'blog':    ('blog/index.html', 'wp-page', 'overlay'),
    'faq':     ('faq/index.html', 'wp-page', 'overlay'),
    'contact': ('contact/index.html', 'wp-page', 'overlay'),
    'archive': ('shop/index.html', 'product-archive', 'solid'),
    'product': ('product/premium-solid-wood-cot/index.html', 'product', 'solid'),
}

PAGE_LABELS = {
    'home': 'Home page', 'about': 'About page', 'blog': 'Blog page',
    'faq': 'FAQ page', 'contact': 'Contact page', 'archive': 'Shop page',
    'product': 'Single product page', 'header': 'Header', 'footer': 'Footer',
    'popup': 'Enquiry popup', 'header_solid': 'Header (shop & product pages)',
}

# ---------------------------------------------------------------- helpers

def read(p):
    with open(os.path.join(ROOT, p), encoding='utf-8') as f:
        return f.read()


def write(p, s):
    os.makedirs(os.path.dirname(p), exist_ok=True)
    with open(p, 'w', encoding='utf-8') as f:
        f.write(s)


def cf_decode(h):
    k = int(h[:2], 16)
    return ''.join(chr(int(h[i:i + 2], 16) ^ k) for i in range(2, len(h), 2))


def php(code):
    return '<?php ' + code + ' ?>'


def sq(s):
    """PHP single quoted string literal."""
    return "'" + s.replace('\\', '\\\\').replace("'", "\\'") + "'"


A = php('echo FURNIQ_A;')          # asset base url
AJ = php('echo FURNIQ_AJ;')        # asset base url with escaped slashes (JSON)

# ------------------------------------------------------------ normalising

def decode_emails(s):
    s = re.sub(r'<a href="[^"]*cdn-cgi/l/email-protection" class="__cf_email__" data-cfemail="([0-9a-f]+)">\[email&#160;protected\]</a>',
               lambda m: cf_decode(m.group(1)), s)
    s = re.sub(r'href="[^"]*cdn-cgi/l/email-protection#([0-9a-f]+)"',
               lambda m: 'href="mailto:' + cf_decode(m.group(1)) + '"', s)
    return s


def normalise_assets(s):
    """Point every wp-content / wp-includes reference at the theme assets."""
    # escaped JSON form: https:\/\/furniqfusion.com\/wp-content\/  or  \/wp-content\/
    s = re.sub(r'(?:https?:\\/\\/(?:www\.)?furniqfusion\.com)?\\/(wp-content|wp-includes)\\/',
               lambda m: '%%AJ%%\\/' + m.group(1) + '\\/', s)
    s = re.sub(r'(?:\.\./)+(wp-content|wp-includes)/', r'%%A%%/\1/', s)
    s = re.sub(r'(?:https?://(?:www\.)?furniqfusion\.com)?(?<!%%A%%)/(wp-content|wp-includes)/', r'%%A%%/\1/', s)
    s = re.sub(r'(?<![\w/%.])(wp-content|wp-includes)/', r'%%A%%/\1/', s)
    return s


SLUG_PAGES = ['about', 'blog', 'faq', 'contact']


def normalise_links(s):
    """Turn links to other pages of the site into WordPress url calls."""
    def term(tax, slug):
        return php('echo esc_url( furniq_term_url( %s, %s ) );' % (sq(tax), sq(slug)))

    # escaped JSON (Happy Addons element links)
    s = re.sub(r'https:\\/\\/furniqfusion\.com\\/product-category\\/([\w-]+)\\/',
               lambda m: php('echo furniq_json_url( furniq_term_url( %s, %s ) );' % (sq('product_cat'), sq(m.group(1)))), s)
    s = re.sub(r'https:\\/\\/furniqfusion\.com\\/(cart|checkout)\\/',
               lambda m: php('echo furniq_json_url( furniq_wc_url( %s ) );' % sq(m.group(1))), s)
    s = re.sub(r'https:\\/\\/furniqfusion\.com\\/wp-admin\\/admin-ajax\.php',
               php("echo furniq_json_url( admin_url( 'admin-ajax.php' ) );"), s)
    s = re.sub(r'https:\\/\\/furniqfusion\.com\\/wp-json\\/',
               php("echo furniq_json_url( rest_url() );"), s)
    s = s.replace('https://furniqfusion.com/wp-admin/admin-ajax.php', php("echo esc_url( admin_url( 'admin-ajax.php' ) );"))
    s = s.replace('https://furniqfusion.com/wp-json/elementskit/v1/', php("echo esc_url( rest_url( 'elementskit/v1/' ) );"))

    s = re.sub(r'(?:\.\./)*product-category/([\w-]+)/index\.html',
               lambda m: term('product_cat', m.group(1)), s)
    s = re.sub(r'(?:\.\./)*product-tag/([\w-]+)/index\.html',
               lambda m: term('product_tag', m.group(1)), s)
    s = re.sub(r'https://furniqfusion\.com/product-category/([\w-]+)/',
               lambda m: term('product_cat', m.group(1)), s)
    s = re.sub(r'(?:\.\./)*shop/index\.html', php('echo esc_url( furniq_shop_url() );'), s)
    s = re.sub(r'https://furniqfusion\.com/shop/', php('echo esc_url( furniq_shop_url() );'), s)
    for slug in SLUG_PAGES:
        s = re.sub(r'(?:\.\./)*%s/index\.html' % slug, php('echo esc_url( furniq_page_url( %s ) );' % sq(slug)), s)
        s = s.replace('https://furniqfusion.com/%s/' % slug, php('echo esc_url( furniq_page_url( %s ) );' % sq(slug)))
    s = re.sub(r'(?:\.\./)*product/([\w-]+)/index\.html',
               lambda m: php('echo esc_url( furniq_product_url( %s ) );' % sq(m.group(1))), s)
    # home page links, including anchors like index.html#elementor-action...
    s = re.sub(r'href="(?:\.\./)*index\.html(#[^"]*)?"',
               lambda m: 'href="%s%s"' % (php("echo esc_url( home_url( '/' ) );") if not m.group(1) or m.group(1) == '#' else '', m.group(1) or ''), s)
    s = s.replace('href="https://furniqfusion.com/"', 'href="' + php("echo esc_url( home_url( '/' ) );") + '"')
    return s


# ----------------------------------------------------------- region cut

def region(s, typ):
    i = s.index('data-elementor-type="%s"' % typ)
    i = s.rfind('<', 0, i)
    depth = 0
    for m in re.finditer(r'<(/?)(div|header|footer|section)\b[^>]*>', s[i:]):
        depth += -1 if m.group(1) else 1
        if depth == 0:
            return s[i:i + m.end()]
    raise ValueError(typ)


# -------------------------------------------------------- fields engine

FIELDS = {}        # id -> dict(section, label, type, default, priority)
SECTIONS = {}      # section id -> dict(panel, title, priority)
ORDER = [0]


def add_field(fid, section, label, ftype, default):
    if fid in FIELDS:
        return fid
    ORDER[0] += 1
    FIELDS[fid] = dict(section=section, label=label, type=ftype, default=default, priority=ORDER[0])
    return fid


def add_section(sid, panel, title):
    if sid not in SECTIONS:
        SECTIONS[sid] = dict(panel=panel, title=title, priority=len(SECTIONS) + 1)
    return sid


def e(fid):
    return php('furniq_e( %s );' % sq(fid))


def short(t, n=48):
    t = re.sub(r'\s+', ' ', html.unescape(re.sub(r'<[^>]+>', ' ', t))).strip()
    return (t[:n] + '…') if len(t) > n else t


LINK_DEFAULT_RE = re.compile(r'<\?php echo esc_url\( (.*?) \); \?>')


def link_default(href):
    """Translate an already normalised href into a stored default token."""
    m = LINK_DEFAULT_RE.fullmatch(href)
    if m:
        call = m.group(1)
        mm = re.fullmatch(r"furniq_page_url\( '([\w-]+)' \)", call)
        if mm:
            return 'page:' + mm.group(1)
        if call == 'furniq_shop_url()':
            return 'shop'
        if call == "home_url( '/' )":
            return 'home'
        mm = re.fullmatch(r"furniq_term_url\( '(\w+)', '([\w-]+)' \)", call)
        if mm:
            return 'term:%s:%s' % mm.groups()
        mm = re.fullmatch(r"furniq_product_url\( '([\w-]+)' \)", call)
        if mm:
            return 'product:' + mm.group(1)
    if '<?php' in href:
        return None
    return html.unescape(href)


def fieldify(s, page, skip_widgets=()):
    """Make widgets in a region editable. Returns new markup."""
    out = []
    pos = 0
    sec_n = 0
    cur_section = None
    widget_re = re.compile(
        r'<div (?:data-ha-element-link="([^"]*)" style="cursor: pointer" )?class="elementor-element elementor-element-(\w+)[^"]*" data-id="\w+" data-element_type="(widget|section|container)"[^>]*?(?:data-widget_type="([^"]+)")?>')
    # top-level section detection: e-parent containers or top-sections
    top_re = re.compile(r'<(?:section|div) class="elementor-section elementor-top-section elementor-element elementor-element-(\w+)|<div class="elementor-element elementor-element-(\w+) [^"]*e-parent')
    tops = [(m.start(), m.group(1) or m.group(2)) for m in top_re.finditer(s)]

    def section_for(at):
        idx = 0
        for i, (p, _) in enumerate(tops):
            if p <= at:
                idx = i
        return idx

    # we process widgets sequentially; each widget's markup runs until the next widget/element start
    matches = list(re.finditer(r'<div (?:data-ha-element-link="[^"]*" style="cursor: pointer" )?class="elementor-element elementor-element-(\w+)[^"]*" data-id="\w+" data-element_type="widget"[^>]*data-widget_type="([^"]+)"[^>]*>', s))
    section_titles = {}
    for k, m in enumerate(matches):
        wid, wtype = m.group(1), m.group(2).replace('.default', '')
        start, end = m.start(), (matches[k + 1].start() if k + 1 < len(matches) else len(s))
        out.append(s[pos:start])
        chunk = s[start:end]
        si = section_for(start) if tops else 0
        sid = '%s_s%d' % (page, si + 1)
        if sid not in section_titles:
            section_titles[sid] = None
        if wid in skip_widgets:
            out.append(chunk)
            pos = end
            continue
        new, title = transform_widget(chunk, wid, wtype, page, sid)
        if title and not section_titles[sid]:
            section_titles[sid] = title
        out.append(new)
        pos = end
    out.append(s[pos:])
    for sid, title in section_titles.items():
        n = int(sid.rsplit('_s', 1)[1])
        if not title:
            labels = [f['label'] for f in FIELDS.values() if f['section'] == sid]
            title = short(labels[0].split(': ', 1)[-1], 40) if labels else 'Section'
        add_section(sid, PAGE_LABELS[page], '%d. %s' % (n, title))
    return ''.join(out)


def text_or_html(inner):
    inner_s = inner.strip()
    if re.search(r'<(?!br\s*/?>)[a-z]', inner_s, re.I):
        return 'html'
    return 'text'


def widget_end(chunk):
    """Index just past the closing tag of the widget root div at chunk[0]."""
    depth = 0
    for m in re.finditer(r'<(/?)div\b[^>]*>', chunk):
        depth += -1 if m.group(1) else 1
        if depth == 0:
            return m.end()
    return len(chunk)


DYN_IGNORE = re.compile(r'<\?php echo esc_url\(.*?\?>|<\?php echo furniq_json_url\(.*?\?>|<\?php echo (?:FURNIQ_A|FURNIQ_AJ);\s*\?>')


def has_php(s):
    return '<?php' in DYN_IGNORE.sub('', s)


def transform_widget(chunk, wid, wtype, page, sid):
    end = widget_end(chunk)
    new, title = _transform_widget(chunk[:end], wid, wtype, page, sid)
    return new + chunk[end:], title


def _transform_widget(chunk, wid, wtype, page, sid):
    pre = '%s_%s' % (page, wid)
    title = None

    if wtype in ('heading', 'woocommerce-product-title'):
        m = re.search(r'(<(h[1-6]|p|div|span)[^>]*class="[^"]*elementor-heading-title[^"]*"[^>]*>)(.*?)(</\2>)', chunk, re.S)
        if m and wtype == 'heading' and not has_php(m.group(3)):
            inner = m.group(3)
            link = re.fullmatch(r'\s*<a href="([^"]*)"[^>]*>(.*?)</a>\s*', inner, re.S)
            if link:
                inner = link.group(2)
            typ = text_or_html(inner)
            default = html.unescape(inner.strip()) if typ == 'text' else inner.strip()
            fid = add_field(pre, sid, 'Heading: ' + short(inner), typ, default)
            title = short(inner, 40)
            if link:
                new_inner = m.group(3).replace(link.group(2), e(fid), 1)
            else:
                new_inner = e(fid)
            chunk = chunk[:m.start(3)] + new_inner + chunk[m.end(3):]
        return chunk, title

    if wtype == 'text-editor':
        m = re.search(r'(data-widget_type="text-editor\.default"[^>]*>)(.*)(</div>\s*)$', chunk, re.S)
        if m and not has_php(m.group(2)):
            body = m.group(2)
            inner = body.strip()
            # strip the optional widget-container wrapper
            wrap = re.fullmatch(r'(<div class="elementor-widget-container">)(.*)(</div>)', inner, re.S)
            core = wrap.group(2).strip() if wrap else inner
            core = re.sub(r'\s*data-(start|end)="\d+"', '', core)
            fid = add_field(pre, sid, 'Text: ' + short(core), 'html', core)
            new = (wrap.group(1) + e(fid) + wrap.group(3)) if wrap else e(fid)
            lead = body[:len(body) - len(body.lstrip())]
            chunk = chunk[:m.start(2)] + lead + new + chunk[m.end(2) - (len(body) - len(body.rstrip())):]
        return chunk, None

    if wtype == 'button':
        m = re.search(r'<span class="elementor-button-text">(.*?)</span>', chunk, re.S)
        if m and not has_php(m.group(1)):
            fid = add_field(pre + '_text', sid, 'Button text: ' + short(m.group(1)), 'text', html.unescape(m.group(1).strip()))
            chunk = chunk[:m.start(1)] + e(fid) + chunk[m.end(1):]
        h = re.search(r'<a class="elementor-button[^"]*" href="([^"]*)"', chunk)
        if h and 'elementor-action' not in h.group(1):
            d = link_default(h.group(1))
            if d is not None:
                fid = add_field(pre + '_link', sid, 'Button link: ' + (short(m.group(1)) if m else wid), 'url', d)
                chunk = chunk[:h.start(1)] + e(fid) + chunk[h.end(1):]
        return chunk, None

    if wtype == 'counter':
        m = re.search(r'data-to-value="([^"]*)"', chunk)
        t = re.search(r'<div class="elementor-counter-title">(.*?)</div>', chunk, re.S)
        label = short(t.group(1)) if t else wid
        if t:
            fid = add_field(pre + '_title', sid, 'Counter title: ' + label, 'text', html.unescape(t.group(1).strip()))
            chunk = chunk[:t.start(1)] + e(fid) + chunk[t.end(1):]
        m = re.search(r'data-to-value="([^"]*)"', chunk)
        if m:
            fid = add_field(pre + '_number', sid, 'Counter number: ' + label, 'number', m.group(1))
            chunk = chunk[:m.start(1)] + e(fid) + chunk[m.end(1):]
        for cls, nm in (('prefix', 'prefix'), ('suffix', 'suffix')):
            p = re.search(r'<span class="elementor-counter-number-%s">(.*?)</span>' % cls, chunk, re.S)
            if p:
                fid = add_field(pre + '_' + nm, sid, 'Counter %s: %s' % (nm, label), 'text', html.unescape(p.group(1)))
                chunk = chunk[:p.start(1)] + e(fid) + chunk[p.end(1):]
        return chunk, None

    if wtype in ('image', 'theme-site-logo'):
        chunk = image_field(chunk, pre, sid, 'Image')
        h = re.search(r'<a href="([^"]*)"', chunk)
        if h:
            d = link_default(h.group(1))
            if d is not None:
                fid = add_field(pre + '_link', sid, 'Image link', 'url', d)
                chunk = chunk[:h.start(1)] + e(fid) + chunk[h.end(1):]
        return chunk, None

    if wtype == 'icon-list':
        items = list(re.finditer(r'<li class="elementor-icon-list-item[^"]*">(.*?)</li>', chunk, re.S))
        out, pos = [], 0
        for n, it in enumerate(items, 1):
            out.append(chunk[pos:it.start(1)])
            li = it.group(1)
            tm = re.search(r'<span class="elementor-icon-list-text">(.*?)</span>', li, re.S)
            if tm and not has_php(tm.group(1)):
                fid = add_field('%s_item%d' % (pre, n), sid, 'List item %d: %s' % (n, short(tm.group(1))), 'text', html.unescape(tm.group(1).strip()))
                li = li[:tm.start(1)] + e(fid) + li[tm.end(1):]
                hm = re.search(r'<a href="([^"]*)"', li)
                if hm:
                    d = link_default(hm.group(1))
                    if d is not None:
                        lid = add_field('%s_item%d_link' % (pre, n), sid, 'List item %d link' % n, 'url', d)
                        li = li[:hm.start(1)] + e(lid) + li[hm.end(1):]
            out.append(li)
            pos = it.end(1)
        out.append(chunk[pos:])
        return ''.join(out), None

    if wtype == 'ha-flip-box':
        n = 0
        def rep(rx, label, typ='text'):
            nonlocal chunk
            m = re.search(rx, chunk, re.S)
            if m:
                fid = add_field('%s_%s' % (pre, label.lower().replace(' ', '_')), sid, 'Card: %s' % label, typ, html.unescape(m.group(1).strip()))
                chunk = chunk[:m.start(1)] + e(fid) + chunk[m.end(1):]
                return fid
        front = re.search(r'<h2 class="ha-flip-box-heading">(.*?)</h2>', chunk, re.S)
        name = short(front.group(1)) if front else wid
        for rx, label in ((r'<h2 class="ha-flip-box-heading">(.*?)</h2>', 'Front title'),
                          (r'<h2 class="ha-flip-box-heading-back">(.*?)</h2>', 'Back title'),
                          (r'<p class="ha-desc">(.*?)</p>', 'Description')):
            m = re.search(rx, chunk, re.S)
            if m:
                fid = add_field('%s_%s' % (pre, label.lower().replace(' ', '_')), sid, '%s – %s' % (name, label), 'text', html.unescape(m.group(1).strip()))
                chunk = chunk[:m.start(1)] + e(fid) + chunk[m.end(1):]
        chunk = image_field(chunk, pre, sid, name + ' – Icon')
        return chunk, None

    if wtype == 'ha-justified-gallery':
        # gallery images -> one image field each
        imgs = list(re.finditer(r'<a href="[^"]*" data-mfp-src="([^"]*)"[^>]*>\s*<img[^>]*>', chunk, re.S))
        out, pos = [], 0
        for n, it in enumerate(imgs, 1):
            out.append(chunk[pos:it.start()])
            block = it.group(0)
            src = it.group(1)
            fid = add_field('%s_img%d' % (pre, n), sid, 'Gallery image %d' % n, 'image', asset_rel(src))
            block = block.replace(src, e(fid), 1)
            block = re.sub(r'(<img[^>]*?)\s(?:srcset|sizes)="[^"]*"', r'\1', block)
            block = re.sub(r'(<img[^>]*?)\s(?:srcset|sizes)="[^"]*"', r'\1', block)
            block = re.sub(r'(<img[^>]*src=")[^"]*(")', lambda mm: mm.group(1) + e(fid) + mm.group(2), block, 1)
            out.append(block)
            pos = it.end()
        out.append(chunk[pos:])
        return ''.join(out), None

    if wtype == 'elementskit-video':
        m = re.search(r'href="([^"]*)"', chunk)
        return chunk, None

    return chunk, None


def asset_rel(url):
    """'%%A%%/wp-content/x.png' -> 'wp-content/x.png' (or '' if not ours)."""
    url = url.replace(A, '%%A%%')
    if url.startswith('%%A%%/'):
        return url[len('%%A%%/'):]
    return url


def image_field(chunk, pre, sid, label):
    m = re.search(r'<img\b[^>]*>', chunk)
    if not m:
        return chunk
    tag = m.group(0)
    src = re.search(r'\ssrc="([^"]*)"', tag)
    if not src or has_php(src.group(1)):
        return chunk
    fid = add_field(pre + '_image', sid, label, 'image', asset_rel(src.group(1)))
    new = tag.replace(src.group(0), ' src="%s"' % e(fid))
    new = re.sub(r'\s(?:srcset|sizes)="[^"]*"', '', new)
    return chunk[:m.start()] + new + chunk[m.end():]


# ------------------------------------------------------ global settings

GLOBALS = [
    # id, section, label, type, default
    ('phone', 'furniq_contact', 'Phone number (as shown)', 'text', '+91 6361896366'),
    ('email', 'furniq_contact', 'Email (header, contact & FAQ pages)', 'text', 'info@furniqfusion.com'),
    ('email_footer', 'furniq_contact', 'Email (footer)', 'text', 'furniqfusion@gmail.com'),
    ('whatsapp_number', 'furniq_contact', 'WhatsApp number (digits only, with country code)', 'text', '916361896366'),
    ('whatsapp_message', 'furniq_contact', 'WhatsApp greeting message', 'text', 'Hello Furniq Fusion Furniture, '),
    ('product_whatsapp_link', 'furniq_contact', 'Product page "Whatsapp Us" link (leave empty to use the WhatsApp number)', 'url', 'https://wa.link/runf92'),
    ('facebook', 'furniq_contact', 'Facebook URL', 'url', 'https://www.facebook.com/profile.php?id=100063745702502'),
    ('instagram', 'furniq_contact', 'Instagram URL', 'url', 'https://www.instagram.com/furniq_fusion'),
    ('twitter', 'furniq_contact', 'X / Twitter URL (footer)', 'url', '#'),
    ('linkedin', 'furniq_contact', 'LinkedIn URL (footer)', 'url', '#'),
    ('form_recipient', 'furniq_contact', 'Send form enquiries to (email, empty = site admin email)', 'text', ''),
]


def apply_globals(s):
    s = re.sub(r'https://api\.whatsapp\.com/send\?phone=916361896366&(?:#038;|amp;)?text=Hello%20Furniq%20Fusion%20Furniture%2C%20',
               php('echo esc_url( furniq_whatsapp_url() );'), s)
    s = s.replace('https://wa.link/runf92', php('echo esc_url( furniq_product_whatsapp_url() );'))
    for url, fid in (('https://www.facebook.com/profile.php?id=100063745702502', 'facebook'),
                     ('https://facebook.com/profile.php?id=100063745702502', 'facebook'),
                     ('https://www.instagram.com/furniq_fusion', 'instagram'),
                     ('https://instagram.com/furniq_fusion', 'instagram')):
        s = s.replace('href="%s"' % url, 'href="%s"' % e(fid))
    s = re.sub(r'(<span class="elementor-icon-list-text">)\s*\+91\s+6361896366\s*(</span>)', lambda m: m.group(1) + e('phone') + m.group(2), s)
    s = re.sub(r'\+91\s+6361896366', e('phone'), s)
    s = s.replace('info@furniqfusion.com', e('email'))
    s = s.replace('furniqfusion@gmail.com', e('email_footer'))
    return s


# ---------------------------------------------------------- assets

DROP_HEAD_IDS = re.compile(r"^(woocommerce-|wc-|wp-|global-styles|classic-theme|jquery|mediaelement|wp-mediaelement|photoswipe|wp-block-library|sourcebuster|imagesloaded)")
NATIVE_SCRIPTS = {
    'jquery-ui-core-js': 'jquery-ui-core',
    'imagesloaded-js': 'imagesloaded',
    'wp-hooks-js': 'wp-hooks',
    'wp-i18n-js': 'wp-i18n',
    'mediaelement-core-js': 'wp-mediaelement',
    'wp-mediaelement-js': 'wp-mediaelement',
}
NATIVE_STYLES = {'mediaelement-css': 'wp-mediaelement', 'wp-mediaelement-css': 'wp-mediaelement'}


def tag_id(tag):
    m = re.search(r"\bid=['\"]([^'\"]+)['\"]", tag)
    return m.group(1) if m else ''


def split_assets(full, page):
    """Return (head_html, footer_html, native_scripts, native_styles)."""
    head = full[full.index('<head>') + 6: full.index('</head>')]
    body_tail = full[full.index('data-elementor-type="footer"'):]
    tokens_re = re.compile(r'<link\b[^>]*rel=[\'"]stylesheet[\'"][^>]*>|<style\b[^>]*>.*?</style>|<script\b[^>]*>.*?</script>', re.S)
    native_js, native_css = [], []

    def keep(tag, where):
        tid = tag_id(tag)
        if tag.startswith('<link'):
            if tid in NATIVE_STYLES:
                native_css.append(NATIVE_STYLES[tid]); return False
            if 'fonts.googleapis.com' in tag:
                return True
            return not DROP_HEAD_IDS.match(tid)
        if tag.startswith('<style'):
            if tid in ('wp-img-auto-sizes-contain-inline-css', 'wp-emoji-styles-inline-css', 'global-styles-inline-css',
                       'woocommerce-inline-inline-css', 'classic-theme-styles-inline-css', 'wp-block-library-inline-css'):
                return False
            if 'woocommerce-product-gallery' in tag and len(tag) < 120:
                return False
            return True
        # scripts
        if 'cloudflare' in tag or 'speculationrules' in tag or 'wp-emoji' in tag or tag.startswith('<script type="module"'):
            return False
        base = re.sub(r'-(extra|before|after)$', '', tid)
        if base in NATIVE_SCRIPTS:
            if not re.search(r'-(extra|before|after)$', tid):
                native_js.append(NATIVE_SCRIPTS[base])
            return False
        if tid.startswith(('wc-', 'woocommerce', 'jquery-core', 'jquery-migrate', 'sourcebuster', 'mediaelement', 'wp-')):
            return False
        if 'woocommerce-no-js' in tag:
            return False  # Woo prints this itself
        return True

    head_keep = [t.group(0) for t in tokens_re.finditer(head) if keep(t.group(0), 'head')]
    tail_scripts_at = body_tail.index('<script')
    foot_keep = [t.group(0) for t in tokens_re.finditer(body_tail[tail_scripts_at:]) if keep(t.group(0), 'foot')]
    # popup markup is printed separately; the inline scripts after it are kept in foot list
    native_js = list(dict.fromkeys(native_js))
    native_css = list(dict.fromkeys(native_css))
    return '\n'.join(head_keep), '\n'.join(foot_keep), native_js, native_css


def dynamic_configs(s):
    # elementor frontend "post" object -> current post
    s = re.sub(r'"post":\{"id":\d+,"title":"[^"]*","excerpt":"[^"]*","featuredImage":[^}]*\}',
               php('echo furniq_elementor_post_json();').replace('<?php', '"post":<?php'), s)
    s = re.sub(r'("nonces":\{"floatingButtonsClickTracking":")\w+(","atomicFormsSendForm":")\w+',
               lambda m: m.group(1) + php("echo esc_js( wp_create_nonce( 'furniq' ) );") + m.group(2) + php("echo esc_js( wp_create_nonce( 'furniq-form' ) );"), s)
    s = re.sub(r'("nonce":")\w+(")', lambda m: m.group(1) + php("echo esc_js( wp_create_nonce( 'furniq' ) );") + m.group(2), s)
    s = re.sub(r'("fragments_nonce":")\w+(")', lambda m: m.group(1) + php("echo esc_js( wp_create_nonce( 'furniq' ) );") + m.group(2), s)
    return s


def finalize(s):
    s = s.replace('%%AJ%%', AJ).replace('%%A%%', A)
    return s


# ------------------------------------------------------- dynamic parts

def products_loop_home(s):
    i = s.index('<ul class="products elementor-grid columns-4">')
    j = s.index('</ul>', i) + 5
    return s[:i] + php("furniq_home_products();") + s[j:]


def archive_loop(s):
    i = s.index('<div class="woocommerce columns-4 ">')
    # ends at the closing of the "woocommerce columns-4" div
    j = s.index('</nav>\n</div>', i) + len('</nav>\n</div>')
    return s[:i] + php('furniq_archive_products();') + s[j:]


def archive_title(s):
    return re.sub(r'(<h1 class="elementor-heading-title elementor-size-default">)Shop(</h1>)',
                  lambda m: m.group(1) + php('echo esc_html( furniq_archive_title() );') + m.group(2), s, 1)


def product_dynamic(s):
    # wrapper classes of the product template
    s = re.sub(r'(data-elementor-id="502" class=")elementor elementor-502 elementor-location-single [^"]*(")',
               lambda m: m.group(1) + php("echo esc_attr( furniq_product_wrapper_class() );") + m.group(2), s)
    # gallery
    i = s.index('<div class="woocommerce-product-gallery')
    j = s.index('</div>\n</div>', i) + len('</div>\n</div>')
    s = s[:i] + php('woocommerce_show_product_images();') + s[j:]
    s = re.sub(r'(<h1 class="product_title entry-title elementor-heading-title elementor-size-default">).*?(</h1>)',
               lambda m: m.group(1) + php('the_title();') + m.group(2), s, 1, re.S)
    i = s.index('<div class="woocommerce-product-details__short-description">')
    j = s.index('</div>', i) + 6
    s = s[:i] + php('furniq_product_short_description();') + s[j:]
    i = s.index('<div class="product_meta">')
    j = s.index('</div>', i) + 6
    s = s[:i] + php('furniq_product_meta();') + s[j:]
    return s


def blog_dynamic(s):
    s = s.replace('<div id="post-items--3c6c2926" class="ekit-row post-items">\n                </div>',
                  '<div id="post-items--3c6c2926" class="ekit-row post-items">' + php('furniq_blog_posts();') + '</div>')
    assert 'furniq_blog_posts' in s
    return s


def page_title_banner(s):
    """Blog page top banner heading becomes the current page title."""
    return s


# ------------------------------------------------------------- build

def build():
    if os.path.isdir(GEN):
        shutil.rmtree(GEN)
    for sub in ('wp-content', 'wp-includes'):
        if os.path.isdir(os.path.join(ASSETS, sub)):
            shutil.rmtree(os.path.join(ASSETS, sub))
    os.makedirs(GEN)

    asset_meta = {}
    used_text = []
    body_classes = {}

    for key, (src, rtype, variant) in PAGES.items():
        full = read(src)
        full = decode_emails(full)
        full = normalise_assets(full)
        full = normalise_links(full)
        full = apply_globals(full)
        body_classes[key] = re.search(r'<body class="([^"]*)"', full).group(1)

        main = region(full, rtype)
        if key == 'home':
            main = products_loop_home(main)
            for n, name in enumerate(('7-seater-sofa.png', 'wooden-Dining-set.png'), 1):
                fid = add_field('home_hero_slide%d' % n, 'home_s1', 'Hero background slide %d' % n, 'image', 'wp-content/uploads/2026/05/' + name)
                main = main.replace('%%AJ%%\\/wp-content\\/uploads\\/2026\\/05\\/' + name,
                                    php("echo furniq_json_url( furniq_mod( %s ) );" % sq(fid)))
            assert 'home_hero_slide2' in main
        if key == 'archive':
            main = archive_loop(main)
            main = archive_title(main)
        if key == 'product':
            main = product_dynamic(main)
        if key == 'blog':
            main = blog_dynamic(main)
        main = fieldify(main, key)
        write(os.path.join(GEN, 'main-%s.php' % key), finalize(main))
        used_text.append(main)

        if key == 'home':
            hdr = fieldify(nav_dynamic(logo_dynamic(region(full, 'header'))), 'header')
            write(os.path.join(GEN, 'header-overlay.php'), finalize(hdr))
            ftr = fieldify(region(full, 'footer'), 'footer')
            write(os.path.join(GEN, 'footer.php'), finalize(ftr))
            pop = fieldify(region(full, 'popup'), 'popup')
            pop = pop.replace('<input type="hidden" name="queried_id" value="151"/>',
                              '<input type="hidden" name="queried_id" value="' + php('echo (int) get_queried_object_id();') + '"/>')
            write(os.path.join(GEN, 'popup.php'), finalize(pop))
            used_text += [hdr, ftr, pop]
        if key == 'archive':
            hdr = fieldify(nav_dynamic(logo_dynamic(region(full, 'header'))), 'header_solid')
            write(os.path.join(GEN, 'header-solid.php'), finalize(hdr))
            used_text.append(hdr)

        head, foot, njs, ncss = split_assets(full, key)
        head, foot = dynamic_configs(head), dynamic_configs(foot)
        write(os.path.join(GEN, 'assets-%s-head.php' % key), finalize(head))
        write(os.path.join(GEN, 'assets-%s-footer.php' % key), finalize(foot))
        asset_meta[key] = dict(scripts=njs, styles=ncss)
        used_text += [head, foot]

    # queried id inside contact / faq forms
    for key in ('contact', 'faq'):
        p = os.path.join(GEN, 'main-%s.php' % key)
        s = open(p, encoding='utf-8').read()
        s = re.sub(r'<input type="hidden" name="queried_id" value="\d+"/>',
                   '<input type="hidden" name="queried_id" value="' + php('echo (int) get_queried_object_id();') + '"/>', s)
        write(p, s)

    copy_assets('\n'.join(used_text))
    write_fields(asset_meta, body_classes)
    print('fields:', len(FIELDS), 'sections:', len(SECTIONS))


def logo_dynamic(s):
    # the site logo (header image widget + off-canvas logo) follows the WordPress "Site Logo"
    s = s.replace('%%A%%/wp-content/uploads/2026/05/cropped-Untitled-design-2026-02-02T101005.227-e1776752494293.png',
                  php('echo esc_url( furniq_logo_url() );'))
    s = re.sub(r'\s(srcset|sizes)="[^"]*furniq_logo_url[^"]*"', '', s)
    return s


def nav_dynamic(s):
    i = s.index('<ul id="menu-main"')
    j = s.index('</ul>', i) + 5
    return s[:i] + php("furniq_primary_menu();") + s[j:]


def copy_assets(text):
    text = text.replace(A, '%%A%%').replace(AJ, '%%AJ%%')
    refs = set()
    for m in re.finditer(r'%%A%%(/(?:wp-content|wp-includes)/[^"\'\s)<>?#&,]+)', text):
        refs.add(m.group(1))
    for m in re.finditer(r'%%AJ%%((?:\\/)(?:wp-content|wp-includes)(?:\\/[^"\'\s)<>?#&,\\]+)+)', text):
        refs.add(m.group(1).replace('\\/', '/'))
    # site logo (replaced by furniq_logo_url() in the markup)
    for f in os.listdir(os.path.join(ROOT, 'wp-content/uploads/2026/05')):
        if f.startswith(('cropped-Untitled-design-2026-02-02T101005.227', 'cropped-cropped-Untitled-design')):
            refs.add('/wp-content/uploads/2026/05/' + f)
    # field image defaults
    for f in FIELDS.values():
        if f['type'] == 'image' and f['default'].startswith(('wp-content', 'wp-includes')):
            refs.add('/' + f['default'])
    # whole plugin asset folders that are loaded dynamically (webpack chunks, fonts, icons)
    dirs = ['wp-content/plugins/elementor/assets/js', 'wp-content/plugins/pro-elements/assets/js',
            'wp-content/plugins/elementor/assets/lib', 'wp-content/plugins/elementor/assets/css',
            'wp-content/plugins/pro-elements/assets/css', 'wp-content/plugins/elementskit-lite',
            'wp-content/plugins/happy-elementor-addons', 'wp-content/themes/hello-elementor/assets',
            'wp-content/uploads/elementor', 'wp-content/uploads/happyaddons']
    copied = 0
    for d in dirs:
        src = os.path.join(ROOT, d)
        if not os.path.isdir(src):
            continue
        for dp, _, files in os.walk(src):
            for fn in files:
                rel = os.path.relpath(os.path.join(dp, fn), ROOT)
                copy_one('/' + rel); copied += 1
    todo = list(refs)
    seen = set()
    while todo:
        r = urllib.parse.unquote(todo.pop())
        if r in seen:
            continue
        seen.add(r)
        if copy_one(r):
            copied += 1
            if r.endswith('.css'):
                css = open(os.path.join(ROOT, r.lstrip('/')), encoding='utf-8', errors='ignore').read()
                for u in re.findall(r'url\(\s*[\'"]?([^\'")]+)[\'"]?\s*\)', css):
                    if u.startswith(('data:', 'http', '#')):
                        continue
                    target = os.path.normpath(os.path.join(os.path.dirname(r), u.split('?')[0].split('#')[0]))
                    todo.append(target)
    # google font css files (saved by wget) – keep local copies too
    print('asset files copied:', copied)


def copy_one(rel):
    src = os.path.join(ROOT, rel.lstrip('/'))
    if not os.path.isfile(src):
        return False
    dst = os.path.join(ASSETS, rel.lstrip('/'))
    if not os.path.exists(dst):
        os.makedirs(os.path.dirname(dst), exist_ok=True)
        if src.endswith('.css'):
            # root-relative urls (/wp-content/...) -> relative to the css file
            css = open(src, encoding='utf-8', errors='surrogateescape').read()
            here = os.path.dirname('/' + rel.lstrip('/'))
            css = re.sub(r'(url\(\s*[\'"]?)(?:https?://(?:www\.)?furniqfusion\.com)?(/(?:wp-content|wp-includes)/[^\'")]+)',
                         lambda m: m.group(1) + os.path.relpath(m.group(2), here), css)
            open(dst, 'w', encoding='utf-8', errors='surrogateescape').write(css)
        else:
            shutil.copy2(src, dst)
    return True


def write_fields(asset_meta, body_classes):
    for fid, sec, label, typ, default in GLOBALS:
        add_field(fid, sec, label, typ, default)
    add_section('furniq_contact', 'Contact & social', 'Contact details & social links')
    data = {'fields': FIELDS, 'sections': SECTIONS, 'assets': asset_meta, 'body_classes': body_classes}
    out = "<?php\n/**\n * Generated by tools/build_theme.py – do not edit by hand.\n * Every editable piece of text / image / link on the site with its original default.\n */\nreturn " + php_export(data) + ";\n"
    write(os.path.join(THEME, 'inc', 'fields.php'), out)


def php_export(v, ind=0):
    pad = '\t' * ind
    if isinstance(v, dict):
        if not v:
            return 'array()'
        items = ['%s\t%s => %s' % (pad, sq(str(k)), php_export(x, ind + 1)) for k, x in v.items()]
        return 'array(\n' + ',\n'.join(items) + ',\n' + pad + ')'
    if isinstance(v, (list, tuple)):
        return 'array(' + ', '.join(php_export(x, ind + 1) for x in v) + ')'
    if isinstance(v, bool):
        return 'true' if v else 'false'
    if isinstance(v, int):
        return str(v)
    return sq(str(v))


if __name__ == '__main__':
    build()
