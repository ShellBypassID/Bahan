"""
TG Forwarder Pro — Backend API
=================================================
"""

import asyncio
import threading
import logging
import json
import os
import time
from datetime import datetime
from collections import deque, defaultdict
from functools import wraps

from flask import Flask, request, jsonify, session, send_from_directory
from flask_cors import CORS
from telethon import TelegramClient, events
from telethon.tl.functions.account import UpdateProfileRequest
from telethon.tl.functions.photos import UploadProfilePhotoRequest, DeletePhotosRequest
from telethon.tl.types import InputPhoto
import base64

# ─────────────────────────────────────────────────────────
#  KONFIGURASI
# ─────────────────────────────────────────────────────────

CONFIG_FILE  = "config.json"
SESSION_FILE = "userbot_session"

DASHBOARD_USER     = "admin"
DASHBOARD_PASSWORD = "buatsandi123"   # ← GANTI!
SECRET_KEY         = "apayaapajagitudeh"  # ← GANTI!

DEFAULT_CONFIG = {
    "api_id"    : "",
    "api_hash"  : "",
    "rules"     : [],           # [{source, targets:[{username,interval_min,delay_sec,enabled}], enabled, filter}]
    "global_filter": {
        "text": True, "photo": True, "video": True,
        "document": True, "audio": True, "sticker": False
    }
}

# ─────────────────────────────────────────────────────────
#  CONFIG PERSISTENCE
# ─────────────────────────────────────────────────────────

def load_config():
    if os.path.exists(CONFIG_FILE):
        with open(CONFIG_FILE, "r", encoding="utf-8") as f:
            return json.load(f)
    return dict(DEFAULT_CONFIG)

def save_config(cfg):
    with open(CONFIG_FILE, "w", encoding="utf-8") as f:
        json.dump(cfg, f, indent=2, ensure_ascii=False)

config = load_config()

# ─────────────────────────────────────────────────────────
#  STATE GLOBAL
# ─────────────────────────────────────────────────────────

class BotState:
    def __init__(self):
        self.running      = False
        self.connected    = False
        self.start_time   = None
        self.me           = None          # info akun login
        self.logs         = deque(maxlen=300)
        self.errors       = deque(maxlen=100)
        self.stats        = defaultdict(int)   # key: "rule_i_target_j"
        self.lock         = threading.Lock()
        self.resolved     = []            # rules yang sudah di-resolve

    def log(self, level, msg):
        entry = {"time": datetime.now().strftime("%H:%M:%S"), "level": level, "msg": msg}
        with self.lock:
            self.logs.appendleft(entry)
            if level == "ERROR":
                self.errors.appendleft(entry)

    def get_logs(self, n=100):
        with self.lock:
            return list(self.logs)[:n]

    def get_errors(self, n=50):
        with self.lock:
            return list(self.errors)[:n]

    def get_stats(self):
        with self.lock:
            return dict(self.stats)

state = BotState()

# ─────────────────────────────────────────────────────────
#  LOGGING BRIDGE
# ─────────────────────────────────────────────────────────

class StateLogHandler(logging.Handler):
    def emit(self, record):
        state.log(record.levelname, self.format(record))

root_log = logging.getLogger()
root_log.setLevel(logging.INFO)
root_log.addHandler(StateLogHandler())
root_log.addHandler(logging.FileHandler("forwarder.log", encoding="utf-8"))
log = logging.getLogger("TGForwarder")

# ─────────────────────────────────────────────────────────
#  USERBOT
# ─────────────────────────────────────────────────────────

client      = None
bot_thread  = None
bot_loop    = None

# scheduler: simpan waktu terakhir kirim per target
last_sent   = defaultdict(float)   # key: "rule_i_target_j" → timestamp

def should_send(rule_idx, tgt_idx, interval_min):
    key = f"rule_{rule_idx}_target_{tgt_idx}"
    now = time.time()
    if now - last_sent[key] >= interval_min * 60:
        last_sent[key] = now
        return True
    return False

def is_allowed(message, filt):
    if message.sticker:   return filt.get("sticker", False)
    if message.photo:     return filt.get("photo", True)
    if message.video or message.video_note: return filt.get("video", True)
    if message.document:  return filt.get("document", True)
    if message.audio or message.voice: return filt.get("audio", True)
    if message.text:      return filt.get("text", True)
    return True

async def resolve_rules():
    resolved = []
    rules = config.get("rules", [])
    for i, rule in enumerate(rules):
        if not rule.get("enabled", True):
            resolved.append({"index": i, "enabled": False, "source_title": rule.get("source","?"), "targets": []})
            continue
        try:
            src_entity = await client.get_entity(rule["source"])
            targets = []
            for j, tgt in enumerate(rule.get("targets", [])):
                try:
                    tgt_entity = await client.get_entity(tgt["username"])
                    targets.append({
                        "index"       : j,
                        "username"    : tgt["username"],
                        "title"       : getattr(tgt_entity, "title", tgt["username"]),
                        "entity"      : tgt_entity,
                        "interval_min": tgt.get("interval_min", 0),
                        "delay_sec"   : tgt.get("delay_sec", 0),
                        "enabled"     : tgt.get("enabled", True),
                    })
                    log.info(f"✅ Target OK: {tgt['username']}")
                except Exception as e:
                    log.error(f"❌ Target {tgt['username']} gagal: {e}")
            resolved.append({
                "index"       : i,
                "enabled"     : True,
                "source"      : rule["source"],
                "source_id"   : src_entity.id,
                "source_title": getattr(src_entity, "title", rule["source"]),
                "targets"     : targets,
                "filter"      : rule.get("filter", config.get("global_filter", {})),
            })
            log.info(f"📡 Rule {i+1} [{src_entity.title}] → {len(targets)} target")
        except Exception as e:
            log.error(f"❌ Rule {i+1} source gagal: {e}")
    with state.lock:
        state.resolved = resolved

async def run_bot():
    global client
    cfg = config
    client = TelegramClient(SESSION_FILE, int(cfg["api_id"]), cfg["api_hash"])
    try:
        await client.start()
        state.connected = True
        me = await client.get_me()
        state.me = {
            "id"        : me.id,
            "name"      : f"{me.first_name or ''} {me.last_name or ''}".strip(),
            "username"  : me.username or "",
            "phone"     : me.phone or "",
        }
        log.info(f"✅ Login: {state.me['name']} (@{state.me['username']})")
    except Exception as e:
        log.error(f"❌ Login gagal: {e}")
        state.running = False
        return

    await resolve_rules()

    @client.on(events.NewMessage())
    async def handler(event):
        for rule in state.resolved:
            if not rule.get("enabled"):
                continue
            if event.chat_id != rule.get("source_id"):
                continue
            filt = rule.get("filter", {})
            if not is_allowed(event.message, filt):
                continue
            for tgt in rule.get("targets", []):
                if not tgt.get("enabled"):
                    continue
                interval = tgt.get("interval_min", 0)
                if interval > 0 and not should_send(rule["index"], tgt["index"], interval):
                    log.info(f"⏩ Jeda interval [{tgt['username']}], skip")
                    continue
                delay = tgt.get("delay_sec", 0)
                if delay > 0:
                    await asyncio.sleep(delay)
                try:
                    await client.forward_messages(
                        entity=tgt["entity"],
                        messages=event.message,
                        from_peer=rule["source_id"],
                    )
                    key = f"rule_{rule['index']}_target_{tgt['index']}"
                    with state.lock:
                        state.stats[key] += 1
                    log.info(f"📨 [{rule['source_title']}] → [{tgt['title']}] msg={event.message.id}")
                except Exception as e:
                    log.error(f"❌ Forward gagal [{tgt['username']}]: {e}")

    log.info("👂 Bot aktif, menunggu pesan baru...")
    await client.run_until_disconnected()
    state.connected = False
    state.running   = False
    log.info("🔴 Bot dihentikan.")

def _bot_thread_fn():
    global bot_loop
    bot_loop = asyncio.new_event_loop()
    asyncio.set_event_loop(bot_loop)
    bot_loop.run_until_complete(run_bot())

def stop_bot_now():
    global client, bot_loop
    if client and bot_loop:
        asyncio.run_coroutine_threadsafe(client.disconnect(), bot_loop)
    state.running   = False
    state.connected = False

# ─────────────────────────────────────────────────────────
#  FLASK
# ─────────────────────────────────────────────────────────

app = Flask(__name__)
app.secret_key = SECRET_KEY
CORS(app, supports_credentials=True, origins="*")
@app.route('/')
def index():
    return send_from_directory('static', 'index.html')

def auth(f):
    @wraps(f)
    def wrap(*a, **kw):
        tok = request.headers.get("X-Auth-Token")
        if tok != SECRET_KEY + DASHBOARD_PASSWORD:
            return jsonify({"ok": False, "msg": "Unauthorized"}), 401
        return f(*a, **kw)
    return wrap

# ── Auth ────────────────────────────────────────────────

@app.route("/api/login", methods=["POST"])
def api_login():
    d = request.json or {}
    if d.get("username") == DASHBOARD_USER and d.get("password") == DASHBOARD_PASSWORD:
        token = SECRET_KEY + DASHBOARD_PASSWORD
        return jsonify({"ok": True, "token": token})
    return jsonify({"ok": False, "msg": "Username atau password salah"}), 401

# ── Status ──────────────────────────────────────────────

@app.route("/api/status")
@auth
def api_status():
    uptime = None
    if state.start_time and state.running:
        d = datetime.now() - state.start_time
        h, r = divmod(int(d.total_seconds()), 3600)
        m, s = divmod(r, 60)
        uptime = f"{h:02d}:{m:02d}:{s:02d}"

    rules_out = []
    for rule in state.resolved:
        targets_out = []
        for tgt in rule.get("targets", []):
            key = f"rule_{rule['index']}_target_{tgt['index']}"
            targets_out.append({
                "username"    : tgt["username"],
                "title"       : tgt["title"],
                "enabled"     : tgt["enabled"],
                "interval_min": tgt["interval_min"],
                "delay_sec"   : tgt["delay_sec"],
                "forwarded"   : state.stats.get(key, 0),
            })
        rules_out.append({
            "index"       : rule["index"],
            "enabled"     : rule.get("enabled", False),
            "source"      : rule.get("source",""),
            "source_title": rule.get("source_title",""),
            "targets"     : targets_out,
        })

    return jsonify({
        "running"      : state.running,
        "connected"    : state.connected,
        "uptime"       : uptime,
        "me"           : state.me,
        "total_forward": sum(state.stats.values()),
        "error_count"  : len(state.errors),
        "rules"        : rules_out,
    })

# ── Bot Control ─────────────────────────────────────────

@app.route("/api/bot/start", methods=["POST"])
@auth
def api_start():
    global bot_thread
    if state.running:
        return jsonify({"ok": False, "msg": "Bot sudah berjalan."})
    if not config.get("api_id") or not config.get("api_hash"):
        return jsonify({"ok": False, "msg": "API ID / Hash belum diisi di pengaturan."})
    state.start_time = datetime.now()
    state.running    = True
    bot_thread = threading.Thread(target=_bot_thread_fn, daemon=True)
    bot_thread.start()
    return jsonify({"ok": True, "msg": "Bot dimulai."})

@app.route("/api/bot/stop", methods=["POST"])
@auth
def api_stop():
    if not state.running:
        return jsonify({"ok": False, "msg": "Bot tidak berjalan."})
    stop_bot_now()
    return jsonify({"ok": True, "msg": "Bot dihentikan."})

@app.route("/api/bot/restart", methods=["POST"])
@auth
def api_restart():
    stop_bot_now()
    time.sleep(1.5)
    global bot_thread
    state.start_time = datetime.now()
    state.running    = True
    bot_thread = threading.Thread(target=_bot_thread_fn, daemon=True)
    bot_thread.start()
    return jsonify({"ok": True, "msg": "Bot direstart."})

# ── Logs ────────────────────────────────────────────────

@app.route("/api/logs")
@auth
def api_logs():
    n = int(request.args.get("n", 100))
    return jsonify(state.get_logs(n))

@app.route("/api/errors")
@auth
def api_errors():
    return jsonify(state.get_errors())

# ── Config ──────────────────────────────────────────────

@app.route("/api/config", methods=["GET"])
@auth
def api_config_get():
    safe = dict(config)
    safe.pop("api_hash", None)   # jangan expose hash
    return jsonify(safe)

@app.route("/api/config/credentials", methods=["POST"])
@auth
def api_config_creds():
    d = request.json or {}
    config["api_id"]   = str(d.get("api_id", config.get("api_id","")))
    config["api_hash"] = d.get("api_hash", config.get("api_hash",""))
    save_config(config)
    return jsonify({"ok": True})

@app.route("/api/config/filter", methods=["POST"])
@auth
def api_config_filter():
    d = request.json or {}
    config["global_filter"] = d.get("filter", config.get("global_filter", {}))
    save_config(config)
    return jsonify({"ok": True})

# ── Rules CRUD ──────────────────────────────────────────

@app.route("/api/rules", methods=["GET"])
@auth
def api_rules_get():
    return jsonify(config.get("rules", []))

@app.route("/api/rules", methods=["POST"])
@auth
def api_rules_save():
    d = request.json or {}
    config["rules"] = d.get("rules", [])
    save_config(config)
    return jsonify({"ok": True, "msg": "Rules disimpan. Restart bot agar berlaku."})

# ── Profile Edit ─────────────────────────────────────────

@app.route("/api/profile/update", methods=["POST"])
@auth
def api_profile_update():
    if not state.connected or not client or not bot_loop:
        return jsonify({"ok": False, "msg": "Bot tidak terhubung."})
    d = request.json or {}
    first = d.get("first_name", "")
    last  = d.get("last_name", "")
    bio   = d.get("bio", "")
    async def _update():
        await client(UpdateProfileRequest(first_name=first, last_name=last, about=bio))
        me = await client.get_me()
        state.me = {
            "id"      : me.id,
            "name"    : f"{me.first_name or ''} {me.last_name or ''}".strip(),
            "username": me.username or "",
            "phone"   : me.phone or "",
        }
    fut = asyncio.run_coroutine_threadsafe(_update(), bot_loop)
    try:
        fut.result(timeout=10)
        return jsonify({"ok": True, "msg": "Profil diperbarui."})
    except Exception as e:
        return jsonify({"ok": False, "msg": str(e)})

@app.route("/api/profile/photo", methods=["POST"])
@auth
def api_profile_photo():
    if not state.connected or not client or not bot_loop:
        return jsonify({"ok": False, "msg": "Bot tidak terhubung."})
    d    = request.json or {}
    b64  = d.get("image_base64", "")
    if not b64:
        return jsonify({"ok": False, "msg": "Tidak ada gambar."})
    img_bytes = base64.b64decode(b64.split(",")[-1])
    async def _upload():
        file = await client.upload_file(img_bytes, file_name="photo.jpg")
        await client(UploadProfilePhotoRequest(file=file))
    fut = asyncio.run_coroutine_threadsafe(_upload(), bot_loop)
    try:
        fut.result(timeout=15)
        return jsonify({"ok": True, "msg": "Foto profil diperbarui."})
    except Exception as e:
        return jsonify({"ok": False, "msg": str(e)})

# ─────────────────────────────────────────────────────────
#  MAIN
# ─────────────────────────────────────────────────────────

if __name__ == "__main__":
    print("=" * 55)
    print("  TG Forwarder Pro — Backend API")
    print("  Running at http://0.0.0.0:5000")
    print("=" * 55)
    app.run(host="0.0.0.0", port=5000, debug=False, threaded=True)
