#!/usr/bin/env python3
"""Generate the static www.instantbird.com / www.instantbird.org into docs/.

Renders every page of the old PHP site (in php/) once per locale and per OS
with PHP's built-in server, then copies the static assets.

Usage: build/build.py ib.com|ib.org
"""
import html as htmllib, json, os, re, shutil, socket, subprocess, sys, time, urllib.request, urllib.error

SITE = sys.argv[1]
BUILD = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(BUILD, '..', 'php')
OUT = os.path.join(BUILD, '..', 'docs')
with socket.socket() as sock:
    sock.bind(('127.0.0.1', 0))
    PORT = sock.getsockname()[1]
# Pages default to the Windows version; a script adapts them for Mac and Linux.
UAS = {'windows': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
       'mac': 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)',
       'linux': 'Mozilla/5.0 (X11; Linux x86_64)'}

LOCALES = sorted(d for d in os.listdir(os.path.join(SRC, 'l10n')) if not d.startswith('.')) + ['ct']
NOT_PAGES = {'header.inc', 'download.inc'}


def fetch(path, os_name='windows'):
    req = urllib.request.Request(f'http://127.0.0.1:{PORT}/{path}', headers={'User-Agent': UAS[os_name]})
    try:
        with urllib.request.urlopen(req) as r:
            return r.read().decode('utf-8')
    except urllib.error.HTTPError:
        return None


def download_url(m):
    # Same mapping as the old .htaccess redirects.
    path = m.group(2)
    if SITE == 'ib.com' and re.match(r'1\.[0-4]/', path) or SITE == 'ib.org' and path.startswith('1.0/'):
        url = 'http://instantbird.googlecode.com/files/' + path.split('/', 1)[1]
    elif re.match(r'0\.1(\.(1|2)(\.1)?)?/', path):
        url = 'http://ftp.instantbird.com/instantbird/' + path
    else:
        url = 'http://ftp.instantbird.com/instantbird/releases/' + path
    return m.group(1) + url + '"'


def fix_links(html, in_locale_dir):
    html = re.sub(r'((?:href|src)=")downloads/([^"]*)"', download_url, html)
    if in_locale_dir:
        # Assets live at the root; .html links and "." stay inside the locale directory.
        html = re.sub(r'((?:href|src)=")(?![a-z]+:|/|#|\.|[^"]*\.html\b)([^"]+)"', r'\1../\2"', html)
    return html


TAG = re.compile(r'<(a|img)\b([^>]*)>')
ATTR = re.compile(r'([a-z-]+)="([^"]*)"')


def os_table(variants):
    """Compare the Windows rendering of a page with the Mac and Linux ones.

    The PHP code only changes some attributes and texts in place, so the
    renderings differ line by line. Returns what the script must change."""
    lines = {k: v.split('\n') for k, v in variants.items()}
    others = ['mac', 'linux']
    assert all(len(lines[k]) == len(lines['windows']) for k in others)
    table = []
    seen = {'a': 0, 'img': 0}  # position of each element among same-tag elements
    for i, wline in enumerate(lines['windows']):
        line_tags = TAG.findall(wline)
        positions = []
        for name, _ in line_tags:
            positions.append(seen[name])
            seen[name] += 1
        if all(lines[k][i] == wline for k in others):
            continue
        tags = {k: TAG.findall(lines[k][i]) for k in variants}
        assert all([t[0] for t in tags[k]] == [t[0] for t in tags['windows']] for k in others)
        for j, (name, wattrs) in enumerate(tags['windows']):
            wa = {a: htmllib.unescape(v) for a, v in ATTR.findall(wattrs)}
            entry = {'tag': name, 'n': positions[j], 'key': 'src' if 'src' in wa else 'href'}
            entry['val'] = wa[entry['key']]
            for k in others:
                oa = {a: htmllib.unescape(v) for a, v in ATTR.findall(tags[k][j][1])}
                entry[k] = {a: oa.get(a) for a in sorted(set(wa) | set(oa)) if oa.get(a) != wa.get(a)}
            if entry['mac'] or entry['linux']:
                table.append(entry)
        texts = {k: [t.strip() for t in re.split(r'<[^>]*>', lines[k][i])] for k in variants}
        for j, wtext in enumerate(texts['windows']):
            if any(texts[k][j] != wtext for k in others):
                table.append({'text': htmllib.unescape(wtext), **{k: htmllib.unescape(texts[k][j]) for k in others}})
    return table


OS_JS = '''<script>
(function () {
  var ua = navigator.userAgent;
  var os = ua.indexOf('Mac') != -1 ? 'mac' : ua.indexOf('Linux') != -1 ? 'linux' : null;
  if (!os) return;
  %s.forEach(function (e) {
    if (e.text) {
      var walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
      while (walker.nextNode())
        if (walker.currentNode.nodeValue.trim() == e.text)
          walker.currentNode.nodeValue = walker.currentNode.nodeValue.replace(e.text, e[os]);
      return;
    }
    var el = document.getElementsByTagName(e.tag)[e.n];
    if (!el || el.getAttribute(e.key) != e.val) return;
    for (var a in e[os])
      e[os][a] === null ? el.removeAttribute(a) : el.setAttribute(a, e[os][a]);
  });
})();
</script>
</body>'''


def render(path, in_locale_dir):
    variants = {k: fix_links(fetch(path, k), in_locale_dir) for k in UAS}
    table = os_table(variants)
    html = variants['windows']
    if table:
        data = json.dumps(table, ensure_ascii=False).replace('</', '<\\/')
        html = html.replace('</body>', OS_JS % data, 1)
    return html


REDIRECT_JS = '''<script>
(function () {
  var available = %s, page = location.pathname.split('/').pop();
  var byLower = {};
  available.forEach(function (l) { byLower[l.toLowerCase()] = l; var s = l.split('-')[0].toLowerCase(); if (!byLower[s]) byLower[s] = l; });
  var langs = navigator.languages || [navigator.language || ''], fallback = null;
  for (var i = 0; i < langs.length; i++) {
    var l = langs[i].toLowerCase(), s = l.split('-')[0];
    if (byLower[l]) { fallback = byLower[l]; break; }
    if (!fallback && byLower[s]) fallback = byLower[s];
  }
  if (fallback && fallback != 'en-US') location.replace(fallback + '/' + page + location.hash);
})();
</script>
</head>'''


def main():
    if os.path.exists(OUT):
        shutil.rmtree(OUT)
    os.makedirs(OUT)

    names = sorted(f[:-4] for f in os.listdir(SRC) if f.endswith('.php') and f[:-4] not in NOT_PAGES)
    pages = []
    for name in names:
        if name == 'index':
            pages.append(('', 'index.html'))
        elif fetch(name + '.html') is not None:
            pages.append((name + '.html', name + '.html'))
    if SITE == 'ib.com':
        pages = [p for p in pages if p[0] != 'instantbird.org.html']  # served by ib.org
    print(f'{len(pages)} pages x {len(LOCALES)} locales')

    js = REDIRECT_JS % ('[' + ','.join(f'"{l}"' for l in LOCALES) + ']')
    for url, out in pages:
        html = render(url, False).replace('</head>', js, 1)
        open(os.path.join(OUT, out), 'w').write(html)
        for loc in LOCALES:
            os.makedirs(os.path.join(OUT, loc), exist_ok=True)
            html = render(f'{loc}/{url}', True)
            open(os.path.join(OUT, loc, out), 'w').write(html)

    # Redirects from the old .htaccess ("Rewrite urls from the old site").
    redirects = {'contact.html': 'about.html', 'download.html': 'features.html',
                 'screenshots.html': 'features.html', 'screenshots-windows.html': 'features.html',
                 'screenshots-macosx.html': 'features.html', 'screenshots-linux.html': 'features.html',
                 'support.html': 'faq.html', 'update-0.1.3.1.html': 'download-0.1.3.1.html',
                 'about_translations.html': 'http://wiki.instantbird.org/Instantbird:Translation'}
    for name, target in (redirects.items() if SITE == 'ib.com' else []):
        page = (f'<!DOCTYPE html><meta charset="utf-8"><title>Redirecting…</title>'
                f'<meta http-equiv="refresh" content="0; url={target}"><a href="{target}">{target}</a>\n')
        for d in [''] + LOCALES:
            open(os.path.join(OUT, d, name), 'w').write(page)

    # Static assets, as they were on the server.
    skip_dirs = {'.hg', 'l10n'}
    for d, dirs, files in os.walk(SRC, followlinks=True):
        dirs[:] = [x for x in dirs if x not in skip_dirs]
        for f in files:
            if f.endswith(('.php', '.inc', '~', '-old', '.orig')) or f.startswith('.'):
                continue
            src = os.path.join(d, f)
            dst = os.path.join(OUT, os.path.relpath(src, SRC))
            os.makedirs(os.path.dirname(dst), exist_ok=True)
            shutil.copy2(src, dst)
    open(os.path.join(OUT, '.nojekyll'), 'w').close()
    open(os.path.join(OUT, 'CNAME'), 'w').write(f'www.instantbird.{SITE[3:]}\n')


server = subprocess.Popen(['php', '-d', 'display_errors=0', '-d', 'log_errors=0',
                           '-S', f'127.0.0.1:{PORT}', os.path.join(BUILD, 'router.php')],
                          stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
try:
    for _ in range(50):
        try:
            socket.create_connection(('127.0.0.1', PORT)).close()
            break
        except OSError:
            time.sleep(0.1)
    main()
finally:
    server.terminate()
